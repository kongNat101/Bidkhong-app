# 📊 BidKhong Auction Platform — Project Presentation

**Framework:** Laravel 12 · **Database:** MySQL 8.0 · **Auth:** Laravel Sanctum  
**วันที่นำเสนอ:** 22 กุมภาพันธ์ 2026

---

## 📌 ที่มาของโปรเจค (Background)

**ปัญหา:** ตลาดซื้อขายของสะสม/มือสองในไทยขาดแพลตฟอร์มประมูลออนไลน์ที่น่าเชื่อถือ — ปัญหาสินค้าปลอม, การฉ้อโกง, ไม่มีระบบ escrow ป้องกันผู้ซื้อ

**วัตถุประสงค์:** พัฒนา RESTful API สำหรับแพลตฟอร์มประมูลออนไลน์ที่มีระบบ:
1. **ประมูลเรียลไทม์** — bid, buy-now, auto-close
2. **ระบบ Wallet & Escrow** — กันเงิน, คืนเงิน, ป้องกันการฉ้อโกง
3. **ใบรับรองสินค้า (Certificate)** — admin ตรวจสอบความเป็นของแท้
4. **รีวิว & คะแนนผู้ขาย** — เพิ่มความน่าเชื่อถือ
5. **ระบบ Report & Dispute** — แจ้งปัญหา, ตัดสินข้อพิพาท

---

## 🛠 เครื่องมือที่ใช้ (Tech Stack)

| ด้าน | เครื่องมือ | เหตุผล |
|------|-----------|--------|
| **Backend Framework** | Laravel 12 (PHP 8.2) | MVC, Eloquent ORM, Migration system |
| **Database** | MySQL 8.0 | ACID transactions, FK constraints, ENUM |
| **Authentication** | Laravel Sanctum | Token-based API auth, lightweight |
| **Containerization** | Docker + Docker Compose | Environment consistency |
| **API Testing** | Postman Collection | 50+ test cases |
| **Version Control** | Git + GitHub | Collaboration |
| **Design** | Figma (Frontend) | UI/UX mockups |
| **Frontend** | React Native + Expo | Cross-platform mobile app |
| **Documentation** | Markdown + Mermaid | ER diagrams, sequence diagrams |

---

## 📈 การพัฒนา (Development Progress)

### ✅ สิ่งที่ทำแล้ว (Completed Features)

| # | ระบบ | Endpoints | สถานะ | รายละเอียด |
|---|------|-----------|-------|-----------|
| 1 | 🔐 Authentication & Profile | 9 | ✅ เสร็จ | Register, Login, Logout, Profile, Change Password, Forgot/Reset Password |
| 2 | 📦 Product Management | 5 | ✅ เสร็จ | CRUD, Multi-image (max 8), Search/Filter/Sort |
| 3 | 📂 Categories & Subcategories | 3 | ✅ เสร็จ | 6 หมวดหลัก, 36 หมวดย่อย |
| 4 | 🔨 ระบบประมูล (Bidding) | 4 | ✅ เสร็จ | Bid, Buy Now, Bid History, Auto-outbid refund |
| 5 | 💰 ระบบ Wallet (เติม/ถอนเงิน) | 3 | ✅ เสร็จ | Topup, Withdraw, Transaction History |
| 6 | 🛒 ระบบ Orders | 3 | ✅ เสร็จ | My Orders, Close Auction, Verify Order |
| 7 | 🤝 Post-Auction (Escrow) | 5 | ✅ เสร็จ | Confirm → Ship → Receive, Dispute |
| 8 | 🔔 Notifications | 4 | ✅ เสร็จ | 7 ประเภทแจ้งเตือน |
| 9 | 📝 Reviews (รีวิวผู้ขาย) | 2 | ✅ เสร็จ | ให้คะแนน 1-5, ดูรีวิวผู้ขาย + สรุปคะแนน |
| 10 | 🏅 Product Certificate | 3 | ✅ เสร็จ | Upload ใบเซอร์, Admin ตรวจ, อนุมัติ/ปฏิเสธ |
| 11 | 🚨 Report System | 2 | ✅ เสร็จ | แจ้งเตือนผู้ใช้/สินค้า + หลักฐาน |
| 12 | 🛡 Admin Panel | 8 | ✅ เสร็จ | Dashboard, Reports, Disputes, Users, Certificates |
| 13 | ⏰ Auto-close Auctions | Scheduler | ✅ เสร็จ | ปิดประมูลอัตโนมัติทุก 1 นาที |
| 14 | 🔒 Security & Rate Limiting | Middleware | ✅ เสร็จ | Rate limit, IDOR protection, DB transactions |

**รวม:** 51 API Endpoints · 15 Application Tables · 16 Models · 11 Controllers · 26 Migrations

---

### 🔄 สิ่งที่ยังอยู่ระหว่างพัฒนา / ยังไม่ได้ทำ

| # | Feature | สถานะ | หมายเหตุ |
|---|---------|-------|---------|
| 1 | 💳 Payment Gateway จริง (PromptPay, QR) | ❌ ยังไม่ได้ทำ | ปัจจุบันเป็น simulated — logic พร้อมเชื่อม gateway จริง |
| 2 | 📱 Push Notifications (FCM) | ❌ ยังไม่ได้ทำ | ปัจจุบันเป็น in-app notification เท่านั้น |
| 3 | 💬 ระบบ Chat (buyer-seller) | ❌ ยังไม่ได้ทำ | ยังไม่มี real-time messaging |
| 4 | 🔍 Advanced Search (Elasticsearch) | ❌ ยังไม่ได้ทำ | ปัจจุบันใช้ SQL LIKE |
| 5 | 📊 Seller Analytics Dashboard | ❌ ยังไม่ได้ทำ | ยอดขาย, สถิติสินค้า |
| 6 | 🌐 Social Login (Google, Facebook) | ❌ ยังไม่ได้ทำ | |
| 7 | 📧 Email Verification (confirm email) | ❌ ยังไม่ได้ทำ | มี forgot password แล้ว แต่ยังไม่มี verify email |
| 8 | 🔄 WebSocket (real-time bid updates) | ❌ ยังไม่ได้ทำ | ปัจจุบัน polling แทน |

---

## 🗄 Database Design (เน้น)

### ภาพรวม

| รายการ | จำนวน |
|--------|-------|
| Application Tables | **15** |
| System/Framework Tables | 5 |
| Total Tables | **20** |
| Foreign Keys | **28** |
| UNIQUE Constraints | 4 |
| ENUM Columns | 8 |
| Migration Files | 26 |

---

### ER Diagram (Entity Relationship Diagram)

```mermaid
erDiagram
    USERS {
        bigint id PK
        varchar name
        varchar email UK
        varchar phone_number UK
        varchar password
        enum role "user/admin"
        varchar profile_image
        timestamp join_date
    }

    WALLETS {
        bigint id PK
        bigint user_id FK
        decimal balance_available
        decimal balance_total
        decimal balance_pending
        decimal withdraw
        decimal deposit
    }

    WALLET_TRANSACTIONS {
        bigint id PK
        bigint user_id FK
        bigint wallet_id FK
        enum type "9 types"
        decimal amount
        varchar description
        varchar reference_type
        bigint reference_id
        decimal balance_after
    }

    PRODUCTS {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        bigint subcategory_id FK
        varchar name
        text description
        decimal starting_price
        decimal current_price
        decimal bid_increment
        decimal buyout_price
        timestamp auction_start_time
        timestamp auction_end_time
        enum status "active/completed/cancelled"
    }

    PRODUCT_IMAGES {
        bigint id PK
        bigint product_id FK
        varchar image_url
        int sort_order
    }

    PRODUCT_CERTIFICATES {
        bigint id PK
        bigint product_id FK
        varchar file_path
        varchar original_name
        enum status "pending/approved/rejected"
        text admin_note
        bigint verified_by FK
        timestamp verified_at
    }

    CATEGORIES {
        bigint id PK
        varchar name
        text description
    }

    SUBCATEGORIES {
        bigint id PK
        bigint category_id FK
        varchar name
        text description
    }

    BIDS {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        decimal price
        timestamp time
        enum status "active/outbid/won/lost"
    }

    ORDERS {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        bigint seller_id FK
        decimal final_price
        enum status "7 statuses"
        timestamp confirm_deadline
        timestamp ship_deadline
        timestamp receive_deadline
    }

    ORDER_CONFIRMATIONS {
        bigint id PK
        bigint order_id FK
        bigint user_id FK
        enum role "buyer/seller"
        varchar phone
        varchar line_id
        varchar facebook
    }

    DISPUTES {
        bigint id PK
        bigint order_id FK
        bigint reporter_id FK
        text reason
        json evidence_images
        enum status "open/resolved_buyer/resolved_seller"
    }

    USER_STRIKES {
        bigint id PK
        bigint user_id FK
        varchar reason
        bigint order_id FK
        timestamp banned_until
    }

    REVIEWS {
        bigint id PK
        bigint order_id FK "UNIQUE"
        bigint reviewer_id FK
        bigint seller_id FK
        tinyint rating "1-5"
        text comment
    }

    REPORTS {
        bigint id PK
        bigint reporter_id FK
        bigint reported_user_id FK
        bigint reported_product_id FK
        enum type "5 types"
        text description
        json evidence_images
        enum status "4 statuses"
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        enum type "7 types"
        varchar title
        text message
        bigint product_id FK
        boolean is_read
    }

    USERS ||--o| WALLETS : "has one"
    USERS ||--o{ PRODUCTS : "sells"
    USERS ||--o{ BIDS : "places"
    USERS ||--o{ ORDERS : "buys"
    USERS ||--o{ ORDERS : "sells"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ USER_STRIKES : "gets"
    USERS ||--o{ REPORTS : "reports"
    USERS ||--o{ REVIEWS : "receives as seller"

    WALLETS ||--o{ WALLET_TRANSACTIONS : "logs"

    CATEGORIES ||--o{ SUBCATEGORIES : "contains"
    CATEGORIES ||--o{ PRODUCTS : "classifies"
    SUBCATEGORIES ||--o{ PRODUCTS : "classifies"

    PRODUCTS ||--o{ PRODUCT_IMAGES : "has many"
    PRODUCTS ||--o| PRODUCT_CERTIFICATES : "has one"
    PRODUCTS ||--o{ BIDS : "receives"
    PRODUCTS ||--o| ORDERS : "generates"

    ORDERS ||--o{ ORDER_CONFIRMATIONS : "confirmed by"
    ORDERS ||--o| DISPUTES : "may have"
    ORDERS ||--o| REVIEWS : "reviewed in"
```

---

### Sequence Diagram — ระบบ Certificate (Admin ตรวจสอบ)

```mermaid
sequenceDiagram
    actor Seller
    participant API as Laravel API
    participant DB as MySQL
    actor Admin

    Note over Seller, Admin: 1. Seller อัปโหลดใบ Certificate

    Seller->>API: POST /api/products (+ certificate file)
    API->>DB: INSERT products
    API->>DB: INSERT product_certificates (status=pending)
    API-->>Seller: 201 Created (product + certificate pending)

    Note over Seller, Admin: 2. Admin เข้ามาตรวจสอบ

    Admin->>API: GET /api/admin/certificates?status=pending
    API->>DB: SELECT * FROM product_certificates WHERE status=pending
    DB-->>API: [list of pending certificates]
    API-->>Admin: 200 OK (certificates list)

    Admin->>API: GET /api/admin/certificates/{id}
    API->>DB: SELECT certificate + download file
    API-->>Admin: ดาวน์โหลดไฟล์ certificate

    Note over Seller, Admin: 3. Admin อนุมัติ/ปฏิเสธ

    alt อนุมัติ (Approved)
        Admin->>API: PATCH /api/admin/certificates/{id}/verify {status: approved}
        API->>DB: UPDATE status=approved, verified_by=admin_id, verified_at=now()
        API->>DB: INSERT notification (แจ้ง seller ว่าผ่าน)
        API-->>Admin: 200 OK
        Note over Seller: Product ได้ badge "Certified ✅"
    else ปฏิเสธ (Rejected)
        Admin->>API: PATCH /api/admin/certificates/{id}/verify {status: rejected, note: "เหตุผล"}
        API->>DB: UPDATE status=rejected, admin_note="เหตุผล"
        API->>DB: INSERT notification (แจ้ง seller ว่าไม่ผ่าน)
        API-->>Admin: 200 OK
    end
```

---

### Sequence Diagram — ระบบรีวิว (Review System)

```mermaid
sequenceDiagram
    actor Buyer
    participant API as Laravel API
    participant DB as MySQL
    actor Public

    Note over Buyer, Public: 1. หลังจาก Order completed

    Buyer->>API: POST /api/orders/{id}/review {rating: 5, comment: "สินค้าดีมาก"}
    API->>DB: CHECK order.status = 'completed'
    API->>DB: CHECK order.user_id = buyer_id
    API->>DB: CHECK no existing review for this order
    API->>DB: INSERT reviews (order_id, reviewer_id, seller_id, rating, comment)
    API-->>Buyer: 201 Created (review submitted)

    Note over Buyer, Public: 2. ใครก็ดูรีวิวผู้ขายได้

    Public->>API: GET /api/users/{seller_id}/reviews
    API->>DB: SELECT reviews WHERE seller_id + AVG(rating) + COUNT
    DB-->>API: reviews + summary
    API-->>Public: 200 OK {summary: {avg: 4.5, total: 12, breakdown: {5: 8, 4: 3, ...}}, reviews: [...]}
```

---

### Sequence Diagram — ระบบ Wallet (เติมเงิน/ถอนเงิน)

```mermaid
sequenceDiagram
    actor User
    participant API as Laravel API
    participant DB as MySQL

    Note over User, DB: เติมเงิน (Top Up)

    User->>API: POST /api/wallet/topup {amount: 1000}
    API->>DB: BEGIN TRANSACTION
    API->>DB: SELECT wallet WHERE user_id (FOR UPDATE)
    API->>DB: UPDATE wallet: balance_available += 1000, balance_total += 1000, deposit += 1000
    API->>DB: INSERT wallet_transaction (type: topup, amount: +1000, balance_after)
    API->>DB: COMMIT
    API-->>User: 200 OK {message, wallet balance}

    Note over User, DB: ถอนเงิน (Withdraw)

    User->>API: POST /api/wallet/withdraw {amount: 500}
    API->>DB: BEGIN TRANSACTION
    API->>DB: SELECT wallet (FOR UPDATE)
    API->>DB: CHECK balance_available >= 500
    alt เงินพอ
        API->>DB: UPDATE wallet: balance_available -= 500, balance_total -= 500, withdraw += 500
        API->>DB: INSERT wallet_transaction (type: withdraw, amount: -500)
        API->>DB: COMMIT
        API-->>User: 200 OK {message, new balance}
    else เงินไม่พอ
        API->>DB: ROLLBACK
        API-->>User: 400 Bad Request {message: "Insufficient balance"}
    end
```

---

### Sequence Diagram — ระบบประมูล (Full Auction Flow)

```mermaid
sequenceDiagram
    actor Seller
    actor Buyer1
    actor Buyer2
    participant API as Laravel API
    participant DB as MySQL
    participant Scheduler

    Note over Seller, Scheduler: 1. Seller ลงสินค้า

    Seller->>API: POST /api/products {name, starting_price, duration, certificate}
    API->>DB: INSERT product (status=active)
    API->>DB: INSERT product_images
    API->>DB: INSERT product_certificate (if attached)

    Note over Seller, Scheduler: 2. Buyers ประมูล

    Buyer1->>API: POST /api/products/{id}/bid {price: 1500}
    API->>DB: TRANSACTION: check balance, deduct wallet, create bid
    API->>DB: UPDATE product.current_price = 1500
    API->>DB: INSERT notification → Seller (new_bid)

    Buyer2->>API: POST /api/products/{id}/bid {price: 2000}
    API->>DB: TRANSACTION: check balance, deduct wallet, create bid
    API->>DB: UPDATE Buyer1 bid status = 'outbid' + REFUND
    API->>DB: INSERT notification → Buyer1 (outbid)
    API->>DB: INSERT notification → Seller (new_bid)

    Note over Seller, Scheduler: 3. ประมูลจบ (Auto-close)

    Scheduler->>API: auctions:close-expired (ทุก 1 นาที)
    API->>DB: SELECT products WHERE auction_end_time < now() AND status=active
    API->>DB: UPDATE product status = 'completed'
    API->>DB: CREATE order (buyer=Buyer2, final_price=2000)
    API->>DB: INSERT notification → Buyer2 (won)
    API->>DB: INSERT notification → Buyer1 (lost)
    API->>DB: INSERT notification → Seller (sold)

    Note over Seller, Scheduler: 4. Post-Auction (Escrow Flow)

    Buyer2->>API: POST /api/orders/{id}/confirm {phone, line_id}
    Seller->>API: POST /api/orders/{id}/confirm {phone, line_id}
    Seller->>API: POST /api/orders/{id}/ship
    Buyer2->>API: POST /api/orders/{id}/receive
    API->>DB: RELEASE escrow → Seller wallet
    API-->>Buyer2: Order completed ✅

    Note over Seller, Scheduler: 5. รีวิว

    Buyer2->>API: POST /api/orders/{id}/review {rating: 5}
```

---

## 🏗 สถาปัตยกรรม (Architecture)

### MVC Pattern

```mermaid
flowchart LR
    Client["📱 React Native App"]
    Router["🔀 Routes (api.php)"]
    Middleware["🛡 Middleware\nauth:sanctum\nadmin\nthrottle"]
    Controller["🧠 Controllers (11)"]
    Model["📦 Models (16)"]
    DB["🗄 MySQL"]

    Client -->|"HTTP Request"| Router
    Router --> Middleware
    Middleware --> Controller
    Controller -->|"Eloquent ORM"| Model
    Model -->|"SQL Query"| DB
    DB -->|"Result"| Model
    Model -->|"Collection"| Controller
    Controller -->|"JSON Response"| Client
```

### Directory Structure

```
auction-api/
├── app/
│   ├── Http/Controllers/    # 11 Controllers (API Logic)
│   │   ├── AuthController         # Auth + Profile + Wallet
│   │   ├── ProductController      # Product CRUD + Certificate upload
│   │   ├── BidController          # Bidding + Buy Now
│   │   ├── OrderController        # Orders + Close Auction
│   │   ├── PostAuctionController  # Escrow: Confirm/Ship/Receive/Dispute
│   │   ├── CategoryController     # Categories listing
│   │   ├── NotificationController # Notifications
│   │   ├── ReportController       # User Reports
│   │   ├── ReviewController       # Reviews (ให้ + ดูรีวิว)
│   │   └── AdminController        # Admin Panel (all admin APIs)
│   ├── Models/              # 16 Eloquent Models
│   └── Console/             # Scheduler (auto-close auctions)
├── database/
│   ├── migrations/          # 26 migration files
│   └── seeders/             # Test data (14 users, 27 products, 100+ bids)
├── routes/
│   └── api.php              # 51 API endpoints
├── docs/                    # Documentation
└── docker-compose.yml       # Docker setup
```

---

## 🔐 Security Design

| ด้าน | วิธีจัดการ |
|------|----------|
| **Authentication** | Token-based (Laravel Sanctum) — SHA-256 hash ใน DB |
| **Password** | bcrypt hash (cost=10, 60 chars) — irreversible |
| **SQL Injection** | Eloquent ORM (parameterized queries) |
| **IDOR Protection** | ทุก query scope ตาม user_id + role check |
| **Race Condition** | DB::transaction() + Row Locking |
| **Rate Limiting** | 10/60/100 req/min ตาม route group |
| **File Upload** | Validate mime type + max size + private storage |
| **ENUM Validation** | DB ENUM + Application validation 2 ชั้น |

---

## 📐 Database Normalization

| Normal Form | สถานะ | หมายเหตุ |
|-------------|-------|---------|
| **1NF** | ✅ | ทุก column atomic (ยกเว้น JSON สำหรับ evidence_images — intentional trade-off) |
| **2NF** | ✅ | ไม่มี partial dependency (PK เป็น single column `id` ทุกตาราง) |
| **3NF** | ✅ | ไม่มี transitive dependency + Intentional Denormalization 2 จุด |

### Intentional Denormalization

| Column | คำนวณจาก | เหตุผล |
|--------|---------|--------|
| `products.current_price` | MAX(bids.price) | ไม่ต้อง JOIN + aggregate ทุก request |
| `wallets.balance_total` | available + pending | ไม่ต้องคำนวณทุกครั้ง |

---

## 🔑 ระบบ Certificate (เพิ่มความน่าเชื่อถือ)

### Flow การทำงาน

```
Seller Upload Certificate → status: pending
       ↓
Admin Review (GET /admin/certificates)
       ↓
   ┌─────────┐
   │ Approve │ → status: approved → Product badge "Certified ✅"
   └─────────┘
   ┌─────────┐
   │ Reject  │ → status: rejected → แจ้ง seller เหตุผล
   └─────────┘
```

### API Endpoints (Certificate)

| Method | Endpoint | Role | คำอธิบาย |
|--------|----------|------|---------|
| `POST` | `/api/products` | Seller | อัปโหลดใบ certificate พร้อมสร้างสินค้า |
| `GET` | `/api/admin/certificates` | Admin | ดูรายการ certificate ทั้งหมด (filter by status) |
| `GET` | `/api/admin/certificates/{id}` | Admin | ดาวน์โหลด/ดูไฟล์ certificate |
| `PATCH` | `/api/admin/certificates/{id}/verify` | Admin | อนุมัติ/ปฏิเสธ + หมายเหตุ |

### Database Schema (product_certificates)

```sql
CREATE TABLE product_certificates (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  BIGINT UNSIGNED NOT NULL,  -- FK → products.id (CASCADE)
    file_path   VARCHAR(255) NOT NULL,     -- path ไฟล์ PDF/รูป
    original_name VARCHAR(255) NOT NULL,   -- ชื่อไฟล์ต้นฉบับ
    status      ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_note  TEXT NULL,                 -- หมายเหตุจาก admin
    verified_by BIGINT UNSIGNED NULL,      -- FK → users.id (SET NULL)
    verified_at TIMESTAMP NULL,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP
);
```

---

## ⭐ ระบบรีวิว (Review System)

### คุณสมบัติ

- Buyer ให้คะแนน **1-5 ดาว** + comment หลัง order completed
- **1 order = 1 review** (UNIQUE constraint)
- ดูรีวิวผู้ขาย **ไม่ต้อง login** (public API)
- สรุปคะแนน: average rating, total reviews, rating breakdown

### Database Schema (reviews)

```sql
CREATE TABLE reviews (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    BIGINT UNSIGNED NOT NULL,    -- FK → orders.id (CASCADE) + UNIQUE
    reviewer_id BIGINT UNSIGNED NOT NULL,    -- FK → users.id (buyer)
    seller_id   BIGINT UNSIGNED NOT NULL,    -- FK → users.id (seller)
    rating      TINYINT NOT NULL,            -- 1-5 (ใช้ TINYINT เพื่อ AVG ได้ตรง)
    comment     TEXT NULL,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP
);
```

---

## 💰 ระบบ Wallet (เติมเงิน/ถอนเงิน)

### ประเภทยอดเงิน

| Field | คำอธิบาย |
|-------|---------|
| `balance_available` | เงินที่ใช้ได้ทันที |
| `balance_pending` | เงินที่ถูกกัน (bidding/escrow) |
| `balance_total` | available + pending |
| `deposit` | ยอดเติมสะสม |
| `withdraw` | ยอดถอนสะสม |

### Transaction Types (9 ประเภท)

| Type | ทิศทาง | เมื่อไหร่ |
|------|--------|----------|
| `topup` | +💰 | เติมเงินเข้า wallet |
| `withdraw` | -💰 | ถอนเงินออก (ขั้นต่ำ 100 บาท) |
| `bid_placed` | -💰 | หักเงินตอน bid |
| `bid_refund` | +💰 | คืนเงินตอนโดน outbid |
| `auction_won` | -💰 | หัก pending ตอนชนะ |
| `auction_sold` | +💰 | รับเงินจากการขาย |
| `escrow_hold` | -💰 | กันเงิน escrow |
| `escrow_release` | +💰 | ปล่อยเงินให้ seller |
| `escrow_refund` | +💰 | คืนเงิน (dispute resolved) |

> 💡 **หมายเหตุ:** ปัจจุบันระบบ Wallet เป็น **simulated** — Logic ฝั่ง backend พร้อมเชื่อมกับ Payment Gateway จริง (PromptPay, QR Code) ได้ทันที โดยไม่ต้องแก้ logic เดิม

---

## 📂 หมวดหมู่สินค้า (Categories)

### 6 หมวดหลัก + 36 หมวดย่อย

| หมวดหลัก | หมวดย่อย |
|----------|---------|
| 📱 Electronics | Smartphones, Laptops, Cameras, Audio, Gaming, Accessories |
| 👗 Fashion | Shoes, Bags, Watches, Jewelry, Clothing, Accessories |
| 🎨 Collectibles | Art, Coins, Stamps, Toys, Cards, Memorabilia |
| 🏡 Home & Living | Furniture, Kitchen, Decor, Garden, Tools, Appliances |
| 🚗 Vehicles | Cars, Motorcycles, Bicycles, Parts, Boats, Others |
| 📦 Others | Books, Sports, Music, Antiques, Handmade, Miscellaneous |

---

## 📊 สรุปภาพรวมโปรเจค

```mermaid
pie title BidKhong API — Feature Completion
    "✅ Completed (14 Features)" : 14
    "❌ Not Started (8 Features)" : 8
```

### API Endpoints Summary (51 Total)

| กลุ่ม | จำนวน Endpoints |
|-------|----------------|
| Authentication & Profile | 9 |
| Products | 5 |
| Categories | 3 |
| Bidding | 4 |
| Wallet | 3 |
| Orders | 3 |
| Post-Auction (Escrow) | 5 |
| Notifications | 4 |
| Reports | 2 |
| Reviews | 2 |
| Admin Panel | 8 |
| Scheduler | 1 (cron) |
| **Total** | **51** |
