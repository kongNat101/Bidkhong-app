# Database Documentation - Auction API (BidKhong)

---

## ER Diagram (Text-based)

```
+------------------+          +------------------+
|   categories     |          |  subcategories   |
+------------------+          +------------------+
| PK id            |--1----N--| PK id            |
|    name          |          | FK category_id   |
|    description   |          |    name          |
+--------+---------+          +--------+---------+
         |                             |
         | 1:N                         | 1:N
         |                             |
+--------v-----------------------------v---------+
|                   products                      |
+-------------------------------------------------+
| PK id                                           |
| FK user_id ---------> users(id) [seller]        |
| FK category_id -----> categories(id)            |
| FK subcategory_id --> subcategories(id)          |
|    name, description                            |
|    starting_price, current_price, bid_increment |
|    buyout_price, auction_start/end_time         |
|    location, picture                            |
|    status ENUM(active,completed,cancelled)      |
+-----+-------+------+------+--------------------+
      |       |      |      |
      |       |      |      +-----> product_images (1:N)
      |       |      |
      |       |      +-----------> product_certificates (1:1)
      |       |
      |  +----v----+
      |  |  bids   |
      |  +---------+
      |  | PK id            |
      |  | FK user_id -------> users
      |  | FK product_id ----> products
      |  | price, time       |
      |  | status ENUM(active,outbid,won,lost)
      |  +------------------+
      |
+-----v-------------------------------------------------+
|                      orders                            |
+--------------------------------------------------------+
| PK id                                                  |
| FK user_id --------> users(id)  [buyer]                |
| FK seller_id ------> users(id)  [seller]               |
| FK product_id -----> products(id)                      |
| final_price, order_date                                |
| status ENUM(pending_buyer_confirm, confirmed,          |
|             shipped, completed, disputed, cancelled)   |
| buyer_confirmed_at, shipped_at, received_at            |
| confirm_deadline, ship_deadline, receive_deadline      |
+-----+---------+--+-----------+-------------------------+
      |         |  |           |
      |    +----+  |      +----v-----------------+
      |    |       |      | order_confirmations  |
      |    |       |      +----------------------+
      |    |       |      | FK order_id, user_id |
      |    |       |      | role (buyer/seller)  |
      |    |       |      | phone, line, fb      |
      |    |       |      +----------------------+
      |    |       |
      |    |  +----v--------+
      |    |  |   reviews   |
      |    |  +-------------+
      |    |  | FK order_id (UQ)
      |    |  | FK reviewer_id --> users
      |    |  | FK seller_id ----> users
      |    |  | rating (1-5), comment
      |    |  +-------------+
      |    |
      |    +------------+
      |                 |
+-----v-----------+     |
|    users        |     |
+-----------------+     |
| PK id           |     |
| name, email(UQ) |     |
| password        |     |
| phone_number(UQ)|     |
| role (user/admin)|    |
| profile_image   |     |
+--+--+--+--+-----+     |
   |  |  |  |           |
   |  |  |  |    +------v-----------+
   |  |  |  +--->|   wallets (1:1)  |
   |  |  |      +------------------+
   |  |  |      | FK user_id       |
   |  |  |      | balance_available|
   |  |  |      | balance_pending  |
   |  |  |      | balance_total    |
   |  |  |      +--------+---------+
   |  |  |               |
   |  |  |      +--------v-----------+
   |  |  |      | wallet_transactions|
   |  |  |      +--------------------+
   |  |  |      | FK user_id, wallet |
   |  |  |      | type ENUM (9 types)|
   |  |  |      | amount, bal_after  |
   |  |  |      +--------------------+
   |  |  |
   |  |  +-------> notifications (1:N)
   |  |            | FK user_id, product_id
   |  |            | type ENUM (7 types)
   |  |            | title, message, is_read
   |  |
   |  +----------> user_strikes (1:N)
   |               | FK user_id, order_id
   |               | reason, banned_until
   |
   +-------------> reports (1:N)  *** UNIFIED: reports + disputes ***
                   | FK reporter_id -----> users
                   | FK reported_user_id -> users (nullable)
                   | FK reported_product_id -> products (nullable)
                   | FK order_id ---------> orders (nullable, for disputes)
                   | FK admin_reply_by ---> users (nullable)
                   | type ENUM (6 types: scam..dispute)
                   | status ENUM (7 types: pending..resolved_seller)
                   | description, evidence_images (JSON)
                   | admin_note, admin_reply
                   | reviewing_at, resolved_at, admin_reply_at
                   | report_code (computed: RPT-001)
                   | timeline (computed: array of status changes)
```

---

## Data Dictionary (พจนานุกรมข้อมูล)

### ตาราง 1: users - ผู้ใช้งาน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัสผู้ใช้ |
| name | VARCHAR(255) | NOT NULL | ชื่อผู้ใช้ |
| email | VARCHAR(255) | NOT NULL, UQ | อีเมล (ใช้ login) |
| password | VARCHAR(255) | NOT NULL | รหัสผ่าน (hashed bcrypt) |
| phone_number | VARCHAR(255) | NOT NULL, UQ | เบอร์โทรศัพท์ |
| join_date | TIMESTAMP | DEFAULT NOW | วันที่สมัคร |
| role | ENUM('user','admin') | DEFAULT 'user' | บทบาท |
| profile_image | VARCHAR(255) | NULLABLE | path รูปโปรไฟล์ |
| email_verified_at | TIMESTAMP | NULLABLE | วันยืนยันอีเมล |
| remember_token | VARCHAR(100) | NULLABLE | Token จำ session |

---

### ตาราง 2: categories - หมวดหมู่สินค้า
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัสหมวดหมู่ |
| name | VARCHAR(255) | NOT NULL | ชื่อ (Electronics, Fashion, etc.) |
| description | TEXT | NULLABLE | คำอธิบาย |

### ตาราง 3: subcategories - หมวดหมู่ย่อย
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัสหมวดหมู่ย่อย |
| category_id | BIGINT UNSIGNED | FK -> categories, CASCADE | หมวดหมู่หลัก |
| name | VARCHAR(255) | NOT NULL | ชื่อหมวดหมู่ย่อย |
| description | TEXT | NULLABLE | คำอธิบาย |

---

### ตาราง 4: products - สินค้าประมูล
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัสสินค้า |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | เจ้าของ/ผู้ขาย |
| category_id | BIGINT UNSIGNED | FK -> categories, SET NULL | หมวดหมู่ |
| subcategory_id | BIGINT UNSIGNED | FK -> subcategories, SET NULL | หมวดหมู่ย่อย |
| name | VARCHAR(255) | NOT NULL | ชื่อสินค้า |
| description | TEXT | NULLABLE | คำอธิบาย |
| starting_price | DECIMAL(10,2) | NOT NULL | ราคาเริ่มต้น |
| current_price | DECIMAL(10,2) | NOT NULL | ราคาปัจจุบัน (bid ล่าสุด) |
| bid_increment | DECIMAL(10,2) | NOT NULL | จำนวน bid ขั้นต่ำ |
| buyout_price | DECIMAL(10,2) | NULLABLE | ราคา Buy Now |
| auction_start_time | TIMESTAMP | NULLABLE | เวลาเริ่มประมูล |
| auction_end_time | TIMESTAMP | NOT NULL | เวลาสิ้นสุดประมูล |
| location | VARCHAR(255) | NULLABLE | สถานที่สินค้า |
| picture | VARCHAR(255) | NULLABLE | รูปหลัก |
| status | ENUM | DEFAULT 'active' | สถานะ |

**status values:** `active`, `completed`, `cancelled`

### ตาราง 5: product_images - รูปสินค้าเพิ่มเติม
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัสรูป |
| product_id | BIGINT UNSIGNED | FK -> products, CASCADE | สินค้า |
| image_url | VARCHAR(255) | NOT NULL | path ของรูป |
| sort_order | INT | DEFAULT 0 | ลำดับการแสดง |

### ตาราง 6: product_certificates - ใบรับรองสินค้าแท้
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส |
| product_id | BIGINT UNSIGNED | FK -> products, CASCADE | สินค้า |
| file_path | VARCHAR(255) | NOT NULL | ไฟล์ใบเซอร์ |
| original_name | VARCHAR(255) | NOT NULL | ชื่อไฟล์เดิม |
| status | ENUM | DEFAULT 'pending' | สถานะการตรวจสอบ |
| admin_note | TEXT | NULLABLE | บันทึก admin |
| verified_by | BIGINT UNSIGNED | FK -> users, SET NULL | admin ที่ตรวจ |
| verified_at | TIMESTAMP | NULLABLE | วันที่ตรวจ |

**status values:** `pending`, `approved`, `rejected`

---

### ตาราง 7: bids - การประมูล
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส bid |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้ประมูล |
| product_id | BIGINT UNSIGNED | FK -> products, CASCADE | สินค้า |
| price | DECIMAL(10,2) | NOT NULL | ราคาที่ประมูล |
| time | TIMESTAMP | DEFAULT NOW | เวลาที่ประมูล |
| status | ENUM | DEFAULT 'active' | สถานะ bid |

**status values:** `active` -> `outbid` -> `won` / `lost`

---

### ตาราง 8: orders - คำสั่งซื้อ
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส order |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้ซื้อ (buyer) |
| seller_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้ขาย (seller) |
| product_id | BIGINT UNSIGNED | FK -> products, CASCADE | สินค้า |
| final_price | DECIMAL(10,2) | NOT NULL | ราคาสุดท้าย |
| order_date | TIMESTAMP | DEFAULT NOW | วันที่สั่งซื้อ |
| status | ENUM | DEFAULT 'pending_buyer_confirm' | สถานะ |
| buyer_confirmed_at | TIMESTAMP | NULLABLE | วันที่ buyer confirm |
| shipped_at | TIMESTAMP | NULLABLE | วันที่จัดส่ง |
| received_at | TIMESTAMP | NULLABLE | วันที่รับสินค้า |
| confirm_deadline | TIMESTAMP | NULLABLE | กำหนด confirm (48 ชม.) |
| ship_deadline | TIMESTAMP | NULLABLE | กำหนดจัดส่ง (3 วัน) |
| receive_deadline | TIMESTAMP | NULLABLE | กำหนดรับสินค้า (7 วัน) |

**status values:** `pending_buyer_confirm`, `confirmed`, `shipped`, `completed`, `disputed`, `cancelled`

**Order Flow:**
```
pending_buyer_confirm ---> confirmed ---> shipped ---> completed
         |                                   |
         v                                   v
     cancelled                           disputed
                                     (admin ตัดสิน)
                                    /             \
                          resolved_buyer    resolved_seller
                          (คืนเงิน buyer)  (จ่ายเงิน seller)
```

### ตาราง 9: order_confirmations - ข้อมูลติดต่อ
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส |
| order_id | BIGINT UNSIGNED | FK -> orders, CASCADE | order |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | user |
| role | ENUM('buyer','seller') | NOT NULL | บทบาท |
| phone | VARCHAR(20) | NOT NULL | เบอร์โทร |
| line_id | VARCHAR(100) | NULLABLE | LINE ID |
| facebook | VARCHAR(255) | NULLABLE | Facebook |
| note | TEXT | NULLABLE | หมายเหตุ |

**UNIQUE:** (order_id, role)

---

### ตาราง 10: wallets - กระเป๋าเงิน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส wallet |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | เจ้าของ |
| balance_available | DECIMAL(10,2) | DEFAULT 0 | เงินที่ใช้ได้ |
| balance_total | DECIMAL(10,2) | DEFAULT 0 | เงินรวม |
| balance_pending | DECIMAL(10,2) | DEFAULT 0 | เงินถูก hold (escrow) |
| withdraw | DECIMAL(10,2) | DEFAULT 0 | ยอดถอนสะสม |
| deposit | DECIMAL(10,2) | DEFAULT 0 | ยอดเติมสะสม |

**สูตร:** `balance_total = balance_available + balance_pending`

### ตาราง 11: wallet_transactions - ประวัติธุรกรรม
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัสธุรกรรม |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | เจ้าของ |
| wallet_id | BIGINT UNSIGNED | FK -> wallets, CASCADE | wallet |
| type | ENUM | NOT NULL | ประเภทธุรกรรม |
| amount | DECIMAL(12,2) | NOT NULL | จำนวนเงิน (+/-) |
| description | VARCHAR(255) | NULLABLE | รายละเอียด |
| reference_type | VARCHAR(255) | NULLABLE | ประเภทอ้างอิง |
| reference_id | BIGINT UNSIGNED | NULLABLE | รหัสอ้างอิง |
| balance_after | DECIMAL(12,2) | NOT NULL | ยอดเงินหลังทำรายการ |

**type values (9 ประเภท):**
| Type | ความหมาย | amount |
|------|----------|--------|
| topup | เติมเงิน | + |
| withdraw | ถอนเงิน | - |
| bid_placed | วาง bid (hold เงิน) | - |
| bid_refund | คืนเงินเมื่อถูก outbid | + |
| auction_won | ชนะประมูล | - |
| auction_sold | ขายสำเร็จ | + |
| escrow_hold | Hold เงิน escrow | - |
| escrow_release | ปล่อยเงิน escrow | - (buyer) / + (seller) |
| escrow_refund | คืนเงิน escrow | + |

---

### ตาราง 12: reports - รายงาน + ข้อพิพาท (Unified)
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส |
| reporter_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้รายงาน |
| reported_user_id | BIGINT UNSIGNED | FK -> users, CASCADE, **NULLABLE** | ผู้ถูกรายงาน |
| reported_product_id | BIGINT UNSIGNED | FK -> products, SET NULL | สินค้าที่ถูกรายงาน |
| order_id | BIGINT UNSIGNED | FK -> orders, SET NULL, **NULLABLE** | order (สำหรับ dispute) |
| type | ENUM | NOT NULL | ประเภท |
| description | TEXT | **NULLABLE** | รายละเอียด |
| evidence_images | JSON | NULLABLE | รูปหลักฐาน (array) |
| status | ENUM | DEFAULT 'pending' | สถานะ |
| admin_note | TEXT | NULLABLE | บันทึก admin |
| admin_reply | TEXT | NULLABLE | ข้อความตอบกลับจาก admin |
| admin_reply_at | TIMESTAMP | NULLABLE | เวลาที่ admin ตอบ |
| admin_reply_by | BIGINT UNSIGNED | FK -> users, SET NULL | admin ที่ตอบ |
| reviewing_at | TIMESTAMP | NULLABLE | เวลาเริ่มตรวจสอบ |
| resolved_at | TIMESTAMP | NULLABLE | เวลาแก้ไขเสร็จ |

**type values (6 ประเภท):**
| Type | ความหมาย |
|------|----------|
| scam | หลอกลวง |
| fake_product | สินค้าปลอม |
| harassment | คุกคาม |
| inappropriate_content | เนื้อหาไม่เหมาะสม |
| other | อื่นๆ |
| **dispute** | **ข้อพิพาทจาก order** |

**status values (7 สถานะ):**
| Status | ใช้กับ | ความหมาย |
|--------|--------|----------|
| pending | report | รอตรวจสอบ |
| reviewing | report | กำลังดำเนินการ |
| resolved | report | แก้ไขแล้ว |
| dismissed | report | ยกเลิกรายงาน |
| open | dispute | เปิดข้อพิพาท |
| resolved_buyer | dispute | ตัดสินให้ผู้ซื้อ (คืนเงิน) |
| resolved_seller | dispute | ตัดสินให้ผู้ขาย (จ่ายเงิน) |

**Computed fields (ไม่ได้เก็บใน DB):**
- `report_code` = "RPT-001" (format จาก id)
- `timeline` = array ของ status changes พร้อม timestamp

**Report Flow:**
```
pending ---> reviewing ---> resolved / dismissed
```

**Dispute Flow:**
```
open ---> resolved_buyer (คืนเงิน escrow ให้ buyer)
     ---> resolved_seller (ปล่อยเงิน escrow ให้ seller)
```

---

### ตาราง 13: reviews - รีวิวผู้ขาย
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส |
| order_id | BIGINT UNSIGNED | FK -> orders, CASCADE, **UNIQUE** | 1 order = 1 review |
| reviewer_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้รีวิว (buyer) |
| seller_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้ถูกรีวิว (seller) |
| rating | TINYINT | NOT NULL | คะแนน 1-5 |
| comment | TEXT | NULLABLE | ความเห็น |

---

### ตาราง 14: notifications - การแจ้งเตือน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้รับ |
| type | ENUM | NOT NULL | ประเภท |
| title | VARCHAR(255) | NOT NULL | หัวข้อ |
| message | TEXT | NOT NULL | ข้อความ |
| product_id | BIGINT UNSIGNED | FK -> products, NULLABLE | สินค้าที่เกี่ยวข้อง |
| is_read | BOOLEAN | DEFAULT false | อ่านแล้วหรือยัง |

**type values:** `outbid`, `won`, `lost`, `sold`, `new_bid`, `order`, `system`

### ตาราง 15: user_strikes - การลงโทษ/แบน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AI | รหัส |
| user_id | BIGINT UNSIGNED | FK -> users, CASCADE | ผู้ถูกลงโทษ |
| order_id | BIGINT UNSIGNED | FK -> orders, SET NULL | order ที่เกี่ยวข้อง |
| reason | VARCHAR(255) | NOT NULL | เหตุผล |
| banned_until | TIMESTAMP | NULLABLE | แบนถึงวันที่ |

---

## Relationships (ความสัมพันธ์)

### One-to-Many (1:N)
| Parent | Child | FK | ความหมาย |
|--------|-------|-----|----------|
| users | products | user_id | 1 user ขายได้หลายชิ้น |
| users | bids | user_id | 1 user bid ได้หลายครั้ง |
| users | orders (buyer) | user_id | 1 user ซื้อได้หลาย order |
| users | orders (seller) | seller_id | 1 user ขายได้หลาย order |
| users | notifications | user_id | 1 user มีหลายแจ้งเตือน |
| users | reports (reporter) | reporter_id | 1 user report ได้หลายครั้ง |
| users | reports (reported) | reported_user_id | 1 user ถูก report ได้หลายครั้ง |
| users | user_strikes | user_id | 1 user ถูกลงโทษได้หลายครั้ง |
| users | reviews (reviewer) | reviewer_id | 1 user เขียนรีวิวได้หลายอัน |
| users | reviews (seller) | seller_id | 1 user ได้รับรีวิวหลายอัน |
| categories | subcategories | category_id | 1 category มีหลาย subcategory |
| categories | products | category_id | 1 category มีหลายสินค้า |
| subcategories | products | subcategory_id | 1 subcategory มีหลายสินค้า |
| products | bids | product_id | 1 สินค้ามีหลาย bids |
| products | product_images | product_id | 1 สินค้ามีหลายรูป |
| orders | reports (dispute) | order_id | 1 order มี dispute ได้ 1 อัน |
| wallets | wallet_transactions | wallet_id | 1 wallet มีหลาย transactions |

### One-to-One (1:1)
| Parent | Child | FK | ความหมาย |
|--------|-------|-----|----------|
| users | wallets | user_id | 1 user = 1 wallet |
| products | product_certificates | product_id | 1 สินค้า = 1 ใบเซอร์ |
| orders | reviews | order_id (UNIQUE) | 1 order = 1 review |

---

## Normalization (การทำ Normalization)

### 1NF (First Normal Form) - ผ่าน
- ทุกตารางมี Primary Key (BIGINT UNSIGNED AUTO_INCREMENT)
- ทุก column เก็บค่า atomic (ค่าเดียว)
- ยกเว้น `evidence_images` (JSON array) — ตั้งใจเก็บแบบ semi-structured

### 2NF (Second Normal Form) - ผ่าน
- ใช้ surrogate key (id) ทุกตาราง → ไม่มี composite key
- ทุก non-key column ขึ้นกับ PK ทั้งหมด → ไม่มี partial dependency

### 3NF (Third Normal Form) - ผ่าน (มี denormalization ตั้งใจ)
- ไม่มี transitive dependency
- **Denormalization (ตั้งใจ):**
  - `products.current_price` — คำนวณจาก bid ล่าสุดได้ แต่เก็บเพื่อ query performance
  - `wallets.balance_total` = available + pending — เก็บเพื่อ performance
  - `wallet_transactions.balance_after` — snapshot สำหรับ audit trail

---

## Escrow Flow (ระบบค้ำประกัน)

```
[Buyer ชนะประมูล]
        |
        v
   Order สร้าง (pending_buyer_confirm)
        |
        v
   Buyer กด Confirm
        |
        v
   balance_available --(escrow_hold)--> balance_pending
        |
        v
   Seller จัดส่ง (shipped)
        |
   +---------+-----------+
   |                     |
   v                     v
Buyer กดรับ          Buyer dispute
   |                     |
   v                     v
balance_pending     Admin ตัดสิน
   |               /          \
   v              v            v
Seller ได้เงิน  resolved_buyer  resolved_seller
(escrow_release) (escrow_refund) (escrow_release)
                 คืนเงิน buyer   จ่ายเงิน seller
```

---

## ENUM Summary

| Table.Column | Values |
|-------------|--------|
| users.role | `user`, `admin` |
| products.status | `active`, `completed`, `cancelled` |
| bids.status | `active`, `outbid`, `won`, `lost` |
| orders.status | `pending_buyer_confirm`, `confirmed`, `shipped`, `completed`, `disputed`, `cancelled` |
| reports.type | `scam`, `fake_product`, `harassment`, `inappropriate_content`, `other`, `dispute` |
| reports.status | `pending`, `reviewing`, `resolved`, `dismissed`, `open`, `resolved_buyer`, `resolved_seller` |
| wallet_transactions.type | `topup`, `withdraw`, `bid_placed`, `bid_refund`, `auction_won`, `auction_sold`, `escrow_hold`, `escrow_release`, `escrow_refund` |
| notifications.type | `outbid`, `won`, `lost`, `sold`, `new_bid`, `order`, `system` |
| product_certificates.status | `pending`, `approved`, `rejected` |
| order_confirmations.role | `buyer`, `seller` |

---

## ON DELETE Actions

| Action | ใช้กับ | ความหมาย |
|--------|--------|----------|
| CASCADE | bids, orders, notifications, product_images, etc. | ลบ parent -> ลบ child ตาม |
| SET NULL | category_id, subcategory_id, reported_product_id, order_id, verified_by, admin_reply_by | ลบ parent -> child ยังอยู่ แต่ FK = NULL |

---

## สถิติ Database

| รายการ | จำนวน |
|--------|-------|
| ตารางหลัก (business) | 15 ตาราง |
| ตาราง Laravel system | 8 ตาราง |
| **รวม** | **23 ตาราง** |
| Foreign Keys | 30+ |
| ENUM fields | 10 fields |
| JSON fields | 1 field (evidence_images) |
| Unique Constraints | 5 (email, phone, token, order_id in reviews, order_id+role) |

---

## สำหรับสไลด์นำเสนอ

### Slide 1: ภาพรวม Database
- 23 ตาราง (15 business + 8 system)
- MySQL 8.0 กับ Laravel 12
- Normalization ถึง 3NF (มี denormalization ตั้งใจ 3 จุด)

### Slide 2: ER Diagram
- ใช้ diagram จากด้านบน (แนะนำวาดใหม่ด้วย draw.io)
- แสดง relationships ทุกเส้น

### Slide 3: Core Tables
- users, products, bids, orders (4 ตารางหลัก)
- Flow: User ลงสินค้า -> คนอื่น bid -> ชนะ -> สร้าง order

### Slide 4: Wallet & Escrow System
- Wallet: available / pending / total
- Escrow flow: hold -> release/refund
- 9 ประเภท transaction

### Slide 5: Report & Dispute (Unified)
- รวม reports + disputes เป็นตาราง reports เดียว
- 6 ประเภท report, 7 สถานะ
- Dispute มี escrow resolution
- Timeline tracking + admin reply

### Slide 6: Supporting Systems
- Reviews (1-5 stars, 1 review per order)
- Certificates (admin approve/reject)
- Notifications (7 types, real-time alerts)
- User Strikes (ban system)

### Slide 7: Normalization & Indexes
- 1NF/2NF/3NF analysis
- Denormalization ที่ตั้งใจ (current_price, balance_total, balance_after)
- Foreign key indexes, unique constraints
- ON DELETE actions (CASCADE vs SET NULL)
