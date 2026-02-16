# 📊 ER Diagram Review — BidKhong

**อัปเดตล่าสุด:** 15 ก.พ. 2026

## สรุปผลการตรวจ ER Diagram เทียบกับ Database จริง

---

## ✅ ถูกต้องแล้ว

- User entity — fields ครบ (name, email, password, phone_number, join_date, role)
- Wallet entity — fields ครบ (balance_available, balance_total, balance_pending, withdraw, deposit)
- User → Wallet (1:1) ✅
- User → Bid (1:M) ✅
- User → Products (1:M) ✅

---

## ❌ Table ที่ขาด (มีใน code แต่ไม่มีใน ER)

### 1. Categories

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| name | string | ชื่อหมวดหมู่ |
| icon | string (nullable) | ไอคอน |

### 2. Subcategories

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| category_id | FK → Categories | หมวดหมู่หลัก |
| name | string | ชื่อหมวดหมู่ย่อย |

### 3. ProductImages

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| product_id | FK → Products | สินค้า |
| image_url | string | path รูป |
| sort_order | integer | ลำดับรูป |

### 4. WalletTransactions

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| user_id | FK → Users | |
| wallet_id | FK → Wallets | |
| type | string | topup / withdraw / bid_placed / bid_refund / auction_won / auction_sold / escrow_hold / escrow_release |
| amount | decimal | จำนวนเงิน |
| description | string | รายละเอียด |
| balance_after | decimal | ยอดคงเหลือหลังทำรายการ |

### 5. Notifications

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| user_id | FK → Users | |
| title | string | หัวข้อ |
| message | string | ข้อความ |
| type | string | outbid / won / lost / sold / order |
| is_read | boolean | อ่านแล้วหรือยัง |
| product_id | FK → Products (nullable) | |

### 6. OrderConfirmations (ใหม่ — Post-Auction)

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| order_id | FK → Orders | |
| user_id | FK → Users | |
| role | enum | buyer / seller |
| phone | string | เบอร์โทร |
| line_id | string (nullable) | LINE ID |
| facebook | string (nullable) | Facebook |
| note | text (nullable) | ข้อความเพิ่มเติม |

### 7. Disputes (ใหม่ — Post-Auction)

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| order_id | FK → Orders | |
| reporter_id | FK → Users | ผู้แจ้ง (buyer) |
| reason | text | เหตุผล |
| evidence_images | JSON (nullable) | รูปหลักฐาน |
| status | enum | open / resolved_buyer / resolved_seller |
| admin_note | text (nullable) | |
| resolved_at | datetime (nullable) | |

### 8. UserStrikes (ใหม่ — Post-Auction)

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| id | PK | |
| user_id | FK → Users | |
| reason | string | เหตุผล |
| order_id | FK → Orders (nullable) | |
| banned_until | datetime (nullable) | แบนถึงเมื่อไหร่ |

---

## ⚠️ จุดที่ต้องแก้

### Products — field ที่ไม่ตรง

| ใน ER | ใน Code จริง | ต้องแก้ |
| --- | --- | --- |
| p_start | starting_price | แค่ชื่อต่าง ✅ |
| p_end | auction_end_time | แค่ชื่อต่าง ✅ |
| p_remain | current_price | ⚠️ ควรเปลี่ยนชื่อ — คือราคาปัจจุบัน |
| p_category | category_id (FK) | ⚠️ ควรเป็น FK ไม่ใช่ text |
| — | subcategory_id (FK) | ❌ ขาดใน ER ต้องเพิ่ม |

### Order — Relationship + Columns ใหม่

| ใน ER | ใน Code จริง |
| --- | --- |
| Bid → Choose → Order | Products → has one → Order |

Order ผูกกับ **Product** ไม่ใช่ Bid

**Order columns ที่ต้องเพิ่มใน ER:**

| Column | Type | หมายเหตุ |
| --- | --- | --- |
| seller_id | FK → Users | ผู้ขาย |
| status | enum | pending_confirm / confirmed / shipped / completed / disputed / cancelled |
| buyer_confirmed_at | datetime | |
| seller_confirmed_at | datetime | |
| shipped_at | datetime | |
| received_at | datetime | |
| confirm_deadline | datetime | 48 ชม. |
| ship_deadline | datetime | 3 วัน |
| receive_deadline | datetime | 7 วัน |

---

## 📐 Relationship ที่ถูกต้อง

```
User (1) ──── (1) Wallet ──── (M) WalletTransactions
User (1) ──── (M) Products
User (1) ──── (M) Bids
User (1) ──── (M) Orders (as buyer)
User (1) ──── (M) Orders (as seller)
User (1) ──── (M) Notifications
User (1) ──── (M) UserStrikes

Products (M) ──── (1) Category
Products (M) ──── (1) Subcategory
Products (1) ──── (M) ProductImages
Products (1) ──── (M) Bids
Products (1) ──── (1) Order

Order (1) ──── (M) OrderConfirmations
Order (1) ──── (1) Dispute

Category (1) ──── (M) Subcategory
```

---

## 📝 สิ่งที่ต้องทำ (อัปเดต ER Diagram)

- [ ] เพิ่ม Table: Categories, Subcategories
- [ ] เพิ่ม Table: ProductImages
- [ ] เพิ่ม Table: WalletTransactions
- [ ] เพิ่ม Table: Notifications
- [ ] เพิ่ม Table: OrderConfirmations, Disputes, UserStrikes
- [ ] แก้ Products — เพิ่ม subcategory_id, เปลี่ยน p_category เป็น FK
- [ ] แก้ Order — ผูกกับ Product ไม่ใช่ Bid, เพิ่ม columns ใหม่
