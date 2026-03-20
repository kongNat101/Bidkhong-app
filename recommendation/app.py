import os
import pickle
import logging
from typing import Optional

import numpy as np
import pandas as pd
import pymysql
from fastapi import FastAPI, HTTPException
from lightfm import LightFM
from lightfm.data import Dataset
from scipy.sparse import csr_matrix

app = FastAPI(title="BidKhong Recommendation Service")
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

MODEL_PATH = "/app/model.pkl"
DATASET_PATH = "/app/dataset.pkl"

# Global state
model: Optional[LightFM] = None
dataset: Optional[Dataset] = None
interactions: Optional[csr_matrix] = None
item_features_matrix: Optional[csr_matrix] = None
user_id_map: dict = {}
item_id_map: dict = {}
reverse_item_map: dict = {}
reverse_user_map: dict = {}
product_ids_list: list = []


def get_db_connection():
    return pymysql.connect(
        host=os.getenv("DB_HOST", "db"),
        port=int(os.getenv("DB_PORT", 3306)),
        user=os.getenv("DB_USERNAME", "root"),
        password=os.getenv("DB_PASSWORD", "secret"),
        database=os.getenv("DB_DATABASE", "auction_app"),
        charset="utf8mb4",
    )


def load_model():
    """Load pre-trained model if exists."""
    global model, dataset, interactions, item_features_matrix
    global user_id_map, item_id_map, reverse_item_map, reverse_user_map, product_ids_list

    if os.path.exists(MODEL_PATH) and os.path.exists(DATASET_PATH):
        with open(MODEL_PATH, "rb") as f:
            model = pickle.load(f)
        with open(DATASET_PATH, "rb") as f:
            data = pickle.load(f)
            dataset = data["dataset"]
            interactions = data["interactions"]
            item_features_matrix = data["item_features"]
            user_id_map = data["user_id_map"]
            item_id_map = data["item_id_map"]
            reverse_item_map = data["reverse_item_map"]
            reverse_user_map = data["reverse_user_map"]
            product_ids_list = data["product_ids_list"]
        logger.info("Model loaded successfully")
        return True
    return False


@app.on_event("startup")
async def startup():
    loaded = load_model()
    if not loaded:
        logger.info("No pre-trained model found. Call POST /train first.")


@app.get("/health")
async def health():
    return {
        "status": "ok",
        "model_loaded": model is not None,
        "total_users": len(user_id_map),
        "total_products": len(item_id_map),
    }


@app.post("/train")
async def train():
    """Train LightFM model from database."""
    global model, dataset, interactions, item_features_matrix
    global user_id_map, item_id_map, reverse_item_map, reverse_user_map, product_ids_list

    conn = get_db_connection()
    try:
        # ดึงข้อมูล bids (implicit feedback)
        bids_df = pd.read_sql(
            """
            SELECT b.user_id, b.product_id, COUNT(*) as bid_count
            FROM bids b
            JOIN products p ON b.product_id = p.id
            GROUP BY b.user_id, b.product_id
            """,
            conn,
        )

        if bids_df.empty:
            raise HTTPException(
                status_code=400,
                detail="No bid data available for training. Need at least some bids.",
            )

        # ดึงข้อมูล products + features
        products_df = pd.read_sql(
            """
            SELECT id, category_id, subcategory_id, starting_price, location, status
            FROM products
            """,
            conn,
        )

        # ดึงข้อมูล users
        users_df = pd.read_sql("SELECT id FROM users", conn)

    finally:
        conn.close()

    logger.info(
        f"Training with {len(bids_df)} interactions, "
        f"{products_df['id'].nunique()} products, "
        f"{bids_df['user_id'].nunique()} users"
    )

    # สร้าง item features
    # Price binning (convert decimal string to float first)
    products_df["starting_price"] = pd.to_numeric(products_df["starting_price"], errors="coerce").fillna(0)
    try:
        price_bins = pd.qcut(
            products_df["starting_price"],
            q=3,
            labels=["price_low", "price_mid", "price_high"],
            duplicates="drop",
        )
        products_df["price_bin"] = price_bins.astype(str)
    except ValueError:
        # ถ้าข้อมูลน้อยเกินไปสำหรับ qcut → ใช้ cut แทน
        products_df["price_bin"] = "price_mid"

    # สร้าง feature labels
    item_feature_labels = set()
    item_features_list = []

    for idx in range(len(products_df)):
        row = products_df.iloc[idx]
        features = []
        if pd.notna(row["category_id"]):
            feat = f"cat_{int(row['category_id'])}"
            features.append(feat)
            item_feature_labels.add(feat)
        if pd.notna(row["subcategory_id"]):
            feat = f"subcat_{int(row['subcategory_id'])}"
            features.append(feat)
            item_feature_labels.add(feat)
        price_bin = str(row["price_bin"])
        if price_bin and price_bin != "nan":
            features.append(price_bin)
            item_feature_labels.add(price_bin)
        if pd.notna(row["location"]) and row["location"]:
            feat = f"loc_{row['location']}"
            features.append(feat)
            item_feature_labels.add(feat)

        item_features_list.append((int(row["id"]), features))

    # สร้าง Dataset
    dataset = Dataset()
    dataset.fit(
        users=users_df["id"].tolist(),
        items=products_df["id"].tolist(),
        item_features=list(item_feature_labels),
    )

    # สร้าง interactions matrix
    interactions_data = [
        (row["user_id"], row["product_id"], row["bid_count"])
        for _, row in bids_df.iterrows()
    ]
    interactions, _ = dataset.build_interactions(interactions_data)

    # สร้าง item features matrix
    item_features_matrix = dataset.build_item_features(item_features_list)

    # Mappings
    user_id_map, _, item_id_map, _ = dataset.mapping()
    reverse_item_map = {v: k for k, v in item_id_map.items()}
    reverse_user_map = {v: k for k, v in user_id_map.items()}
    product_ids_list = products_df["id"].tolist()

    # Train model
    model = LightFM(
        no_components=32,
        loss="warp",
        learning_rate=0.05,
        item_alpha=1e-6,
        user_alpha=1e-6,
    )
    model.fit(
        interactions,
        item_features=item_features_matrix,
        epochs=30,
        num_threads=2,
        verbose=True,
    )

    # Save model
    with open(MODEL_PATH, "wb") as f:
        pickle.dump(model, f)
    with open(DATASET_PATH, "wb") as f:
        pickle.dump(
            {
                "dataset": dataset,
                "interactions": interactions,
                "item_features": item_features_matrix,
                "user_id_map": user_id_map,
                "item_id_map": item_id_map,
                "reverse_item_map": reverse_item_map,
                "reverse_user_map": reverse_user_map,
                "product_ids_list": product_ids_list,
            },
            f,
        )

    logger.info("Model trained and saved successfully")

    return {
        "message": "Model trained successfully",
        "total_users": len(user_id_map),
        "total_products": len(item_id_map),
        "total_interactions": len(bids_df),
        "total_item_features": len(item_feature_labels),
    }


@app.get("/recommend/{user_id}")
async def recommend(user_id: int, limit: int = 10):
    """Get personalized product recommendations for a user."""
    if model is None:
        raise HTTPException(status_code=503, detail="Model not trained yet. Call POST /train first.")

    if user_id not in user_id_map:
        # User ไม่อยู่ใน training data → return popular items
        return await get_popular(limit)

    internal_user_id = user_id_map[user_id]

    # Predict scores for all items
    n_items = len(item_id_map)
    scores = model.predict(
        internal_user_id,
        np.arange(n_items),
        item_features=item_features_matrix,
    )

    # ดึง items ที่ user เคย interact แล้ว → filter ออก
    interactions_csr = interactions.tocsr()
    user_interactions = interactions_csr[internal_user_id].toarray().flatten()
    already_interacted = set(np.where(user_interactions > 0)[0])

    # Sort by score, filter out already interacted
    scored_items = [
        (idx, score)
        for idx, score in enumerate(scores)
        if idx not in already_interacted and idx in reverse_item_map
    ]
    scored_items.sort(key=lambda x: x[1], reverse=True)

    # Filter only active products
    conn = get_db_connection()
    try:
        active_ids_result = pd.read_sql(
            "SELECT id FROM products WHERE status = 'active'", conn
        )
        active_ids = set(active_ids_result["id"].tolist())
    finally:
        conn.close()

    recommended_ids = []
    for idx, score in scored_items:
        product_id = reverse_item_map[idx]
        if product_id in active_ids:
            recommended_ids.append(product_id)
        if len(recommended_ids) >= limit:
            break

    return {"product_ids": recommended_ids, "source": "lightfm"}


@app.get("/similar/{product_id}")
async def similar(product_id: int, limit: int = 10):
    """Get similar products based on item embeddings."""
    if model is None:
        raise HTTPException(status_code=503, detail="Model not trained yet. Call POST /train first.")

    if product_id not in item_id_map:
        raise HTTPException(status_code=404, detail="Product not found in model")

    internal_item_id = item_id_map[product_id]

    # Get item embeddings (biases + representations)
    item_representations = model.get_item_representations(features=item_features_matrix)
    item_biases, item_embeddings = item_representations

    # คำนวณ cosine similarity
    target_embedding = item_embeddings[internal_item_id]
    target_norm = np.linalg.norm(target_embedding)

    if target_norm == 0:
        raise HTTPException(status_code=400, detail="Cannot compute similarity for this product")

    similarities = []
    for idx in range(len(item_embeddings)):
        if idx == internal_item_id:
            continue
        if idx not in reverse_item_map:
            continue
        other_norm = np.linalg.norm(item_embeddings[idx])
        if other_norm == 0:
            continue
        cos_sim = np.dot(target_embedding, item_embeddings[idx]) / (target_norm * other_norm)
        similarities.append((idx, float(cos_sim)))

    similarities.sort(key=lambda x: x[1], reverse=True)

    # Filter only active products
    conn = get_db_connection()
    try:
        active_ids_result = pd.read_sql(
            "SELECT id FROM products WHERE status = 'active'", conn
        )
        active_ids = set(active_ids_result["id"].tolist())
    finally:
        conn.close()

    similar_ids = []
    for idx, score in similarities:
        pid = reverse_item_map[idx]
        if pid in active_ids:
            similar_ids.append(pid)
        if len(similar_ids) >= limit:
            break

    return {"product_ids": similar_ids, "source": "lightfm_similarity"}


@app.get("/popular")
async def get_popular(limit: int = 10):
    """Get popular products (fallback when no personalized data)."""
    conn = get_db_connection()
    try:
        result = pd.read_sql(
            """
            SELECT p.id, COUNT(b.id) as bid_count
            FROM products p
            LEFT JOIN bids b ON p.id = b.product_id
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY bid_count DESC, p.created_at DESC
            LIMIT %s
            """,
            conn,
            params=(limit,),
        )
    finally:
        conn.close()

    return {"product_ids": result["id"].tolist(), "source": "popular"}
