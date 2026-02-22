# Database Documentation - Auction API (BidKhong)

## ER Diagram (Text-based)

```
                                    +------------------+
                                    |    categories    |
                                    +------------------+
                                    | PK id            |
                                    |    name          |
                                    |    description   |
                                    +--------+---------+
                                             |
                                             | 1..* (has many)
                                             |
                                    +--------v---------+
                                    |  subcategories   |
                                    +------------------+
                                    | PK id            |
                                    | FK category_id   |---> categories(id)
                                    |    name          |
                                    |    description   |
                                    +--------+---------+
                                             |
                 +---------------------------+
                 |                           |
+----------------v---+              +--------v---------+
|      users         |              |    products      |
+--------------------+              +------------------+
| PK id              |<----+       | PK id            |
|    name             |     |       | FK user_id       |---> users(id) [seller]
|    email (UQ)       |     |       | FK category_id   |---> categories(id)
|    password         |     |       | FK subcategory_id |---> subcategories(id)
|    phone_number(UQ) |     |       |    name          |
|    join_date        |     |       |    description   |
|    role (enum)      |     |       |    starting_price|
|    profile_image    |     |       |    current_price |
+----+---+---+---+---+     |       |    bid_increment |
     |   |   |   |         |       |    buyout_price  |
     |   |   |   |         |       |    auction_start |
     |   |   |   |         |       |    auction_end   |
     |   |   |   |         |       |    location      |
     |   |   |   |         |       |    picture       |
     |   |   |   |         |       |    status (enum) |
     |   |   |   |         |       +--+--+--+--+------+
     |   |   |   |         |          |  |  |  |
     |   |   |   |         |          |  |  |  |
     |   |   |   |         |          |  |  |  +--------> product_images
     |   |   |   |         |          |  |  |
     |   |   |   |         |          |  |  +-----------> product_certificates
     |   |   |   |         |          |  |
     |   |   |   |         |          |  +-----+
     |   |   |   |         |          |        |
     |   |   |   |    +----v----+     |   +----v----+
     |   |   |   |    |  bids   |     |   |  orders |
     |   |   |   |    +---------+     |   +---------+
     |   |   |   +--->| PK id   |     |   | PK id   |
     |   |   |        | FK user |     |   | FK user  | (buyer)
     |   |   |        | FK prod |     |   | FK seller| (seller)
     |   |   |        | price   |     |   | FK prod  |
     |   |   |        | status  |     |   | final_pr |
     |   |   |        | time    |     |   | status   |
     |   |   |        +---------+     |   | deadlines|
     |   |   |                        |   +----+-----+
     |   |   |                        |        |
     |   |   |                        |   +----v------+    +------------+
     |   |   |                        |   | disputes  |    |  reviews   |
     |   |   |                        |   +-----------+    +------------+
     |   |   +----------------------->|   | FK order  |    | FK order   |
     |   |                            |   | FK reporter|   | FK reviewer|
     |   |                            |   | reason    |    | FK seller  |
     |   |                            |   | status    |    | rating 1-5 |
     |   |                            |   +-----------+    | comment    |
     |   |                            |                    +------------+
     |   |     +-------------+        |
     |   +---->|   wallets   |        |
     |         +-------------+        |
     |         | PK id       |        |
     |         | FK user_id  |        |
     |         | bal_avail   |        |
     |         | bal_total   |        |
     |         | bal_pending |        |
     |         +------+------+        |
     |                |               |
     |         +------v-----------+   |
     |         |wallet_transactions|  |
     |         +------------------+   |
     |         | PK id            |   |
     |         | FK user_id       |   |
     |         | FK wallet_id     |   |
     |         | type (enum)      |   |
     |         | amount           |   |
     |         | balance_after    |   |
     |         +------------------+   |
     |                                |
     |   +----------------+    +------+--------+    +---------------+
     +-->| notifications  |    | user_strikes  |    |    reports    |
         +----------------+    +---------------+    +---------------+
         | FK user_id     |    | FK user_id    |    | FK reporter   |
         | FK product_id  |    | FK order_id   |    | FK reported_u |
         | type (enum)    |    | reason        |    | FK reported_p |
         | title, message |    | banned_until  |    | type (enum)   |
         | is_read        |    +---------------+    | description   |
         +----------------+                         | status (enum) |
                                                    +---------------+
```

---

## Relationships (ความสัมพันธ์ระหว่างตาราง)

### One-to-Many (1:N)
| Parent | Child | FK Column | ความหมาย |
|--------|-------|-----------|----------|
| users | products | user_id | 1 user ขายสินค้าได้หลายชิ้น |
| users | bids | user_id | 1 user ประมูลได้หลายครั้ง |
| users | orders (buyer) | user_id | 1 user ซื้อสินค้าได้หลาย order |
| users | orders (seller) | seller_id | 1 user ขายสินค้าได้หลาย order |
| users | wallets | user_id | 1 user มี 1 wallet (1:1 ในทางปฏิบัติ) |
| users | notifications | user_id | 1 user มี notifications หลายอัน |
| users | wallet_transactions | user_id | 1 user มี transactions หลายรายการ |
| users | reports (reporter) | reporter_id | 1 user report ได้หลายครั้ง |
| users | reports (reported) | reported_user_id | 1 user ถูก report ได้หลายครั้ง |
| users | user_strikes | user_id | 1 user ถูกลงโทษได้หลายครั้ง |
| users | reviews (reviewer) | reviewer_id | 1 user เขียนรีวิวได้หลายอัน |
| users | reviews (seller) | seller_id | 1 user ได้รับรีวิวหลายอัน |
| categories | subcategories | category_id | 1 category มีหลาย subcategories |
| categories | products | category_id | 1 category มีหลายสินค้า |
| subcategories | products | subcategory_id | 1 subcategory มีหลายสินค้า |
| products | bids | product_id | 1 สินค้ามีหลาย bids |
| products | product_images | product_id | 1 สินค้ามีหลายรูป |
| products | notifications | product_id | 1 สินค้ามี notifications หลายอัน |
| wallets | wallet_transactions | wallet_id | 1 wallet มีหลาย transactions |
| orders | disputes | order_id | 1 order มี dispute ได้ 1 อัน (1:1) |

### One-to-One (1:1)
| Parent | Child | FK Column | ความหมาย |
|--------|-------|-----------|----------|
| users | wallets | user_id | 1 user มี 1 wallet |
| products | product_certificates | product_id | 1 สินค้ามี 1 ใบเซอร์ |
| orders | reviews | order_id (UNIQUE) | 1 order มี 1 review |
| orders | disputes | order_id | 1 order มี 1 dispute |

---

## ตารางทั้งหมด 24 ตาราง (แยกตามกลุ่ม)

### กลุ่ม 1: User & Authentication (3 ตาราง)

#### 1.1 users - ผู้ใช้งาน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | รหัสผู้ใช้ |
| name | VARCHAR(255) | NOT NULL | ชื่อผู้ใช้ |
| email | VARCHAR(255) | NOT NULL, UNIQUE | อีเมล (ใช้ login) |
| password | VARCHAR(255) | NOT NULL | รหัสผ่าน (hashed ด้วย bcrypt) |
| phone_number | VARCHAR(255) | NOT NULL, UNIQUE | เบอร์โทร |
| join_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | วันสมัคร |
| role | ENUM('user','admin') | DEFAULT 'user' | บทบาท |
| profile_image | VARCHAR(255) | NULLABLE | รูปโปรไฟล์ |
| email_verified_at | TIMESTAMP | NULLABLE | วันยืนยันอีเมล |
| remember_token | VARCHAR(100) | NULLABLE | Token จำการ login |

**Indexes:** email (UNIQUE), phone_number (UNIQUE)

#### 1.2 personal_access_tokens - Token สำหรับ API Authentication (Sanctum)
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส token |
| tokenable_type | VARCHAR(255) | NOT NULL | ประเภท model (App\Models\User) |
| tokenable_id | BIGINT UNSIGNED | NOT NULL | รหัส user |
| name | VARCHAR(255) | NOT NULL | ชื่อ token |
| token | VARCHAR(64) | UNIQUE | ค่า token (hashed) |
| abilities | TEXT | NULLABLE | สิทธิ์ของ token |
| last_used_at | TIMESTAMP | NULLABLE | ใช้ครั้งล่าสุด |
| expires_at | TIMESTAMP | NULLABLE | หมดอายุ |

**ใช้กับ:** Laravel Sanctum สำหรับ API authentication

#### 1.3 password_reset_tokens - Token รีเซ็ตรหัสผ่าน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| email | VARCHAR(255) | PK | อีเมลที่ขอ reset |
| token | VARCHAR(255) | NOT NULL | Token สำหรับ reset |
| created_at | TIMESTAMP | NULLABLE | สร้างเมื่อ |

---

### กลุ่ม 2: Product & Category (4 ตาราง)

#### 2.1 categories - หมวดหมู่สินค้า
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัสหมวดหมู่ |
| name | VARCHAR(255) | NOT NULL | ชื่อหมวดหมู่ (Electronics, Fashion, etc.) |
| description | TEXT | NULLABLE | คำอธิบาย |

**ข้อมูลตัวอย่าง:** Electronics, Fashion, Collectibles, Home, Vehicles, Others

#### 2.2 subcategories - หมวดหมู่ย่อย
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัสหมวดหมู่ย่อย |
| category_id | BIGINT UNSIGNED | FK → categories(id), CASCADE | หมวดหมู่หลัก |
| name | VARCHAR(255) | NOT NULL | ชื่อหมวดหมู่ย่อย |
| description | TEXT | NULLABLE | คำอธิบาย |

**ตัวอย่าง:** Electronics → Smartphones & Tablets, Computers & Laptops, Audio & Headphones

#### 2.3 products - สินค้าประมูล
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัสสินค้า |
| user_id | BIGINT UNSIGNED | FK → users(id), CASCADE | เจ้าของ/ผู้ขาย |
| category_id | BIGINT UNSIGNED | FK → categories(id), SET NULL, NULLABLE | หมวดหมู่ |
| subcategory_id | BIGINT UNSIGNED | FK → subcategories(id), SET NULL, NULLABLE | หมวดหมู่ย่อย |
| name | VARCHAR(255) | NOT NULL | ชื่อสินค้า |
| description | TEXT | NULLABLE | คำอธิบาย |
| starting_price | DECIMAL(10,2) | NOT NULL | ราคาเริ่มต้น |
| current_price | DECIMAL(10,2) | NOT NULL | ราคาปัจจุบัน (bid ล่าสุด) |
| bid_increment | DECIMAL(10,2) | NOT NULL | จำนวนบิดขั้นต่ำ (seller กำหนด) |
| buyout_price | DECIMAL(10,2) | NULLABLE | ราคาซื้อทันที (Buy Now) |
| auction_start_time | TIMESTAMP | NULLABLE | เวลาเริ่มประมูล |
| auction_end_time | TIMESTAMP | NOT NULL | เวลาสิ้นสุดประมูล |
| location | VARCHAR(255) | NULLABLE | สถานที่ |
| picture | VARCHAR(255) | NULLABLE | รูปหลัก |
| status | ENUM('active','completed','cancelled') | DEFAULT 'active' | สถานะ |

**สำคัญ:**
- `current_price` อัปเดตทุกครั้งที่มีคน bid
- `bid_increment` = จำนวนเงินขั้นต่ำที่ต้อง bid เพิ่มจาก current_price
- `buyout_price` = ราคา Buy Now (ถ้า NULL = ไม่มี Buy Now)

#### 2.4 product_images - รูปสินค้าเพิ่มเติม
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัสรูป |
| product_id | BIGINT UNSIGNED | FK → products(id), CASCADE | สินค้า |
| image_url | VARCHAR(255) | NOT NULL | path ของรูป |
| sort_order | INT | DEFAULT 0 | ลำดับการแสดง |

---

### กลุ่ม 3: Bidding (1 ตาราง)

#### 3.1 bids - การประมูล
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส bid |
| user_id | BIGINT UNSIGNED | FK → users(id), CASCADE | ผู้ประมูล |
| product_id | BIGINT UNSIGNED | FK → products(id), CASCADE | สินค้าที่ประมูล |
| price | DECIMAL(10,2) | NOT NULL | ราคาที่ประมูล |
| time | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | เวลาที่ประมูล |
| status | ENUM('active','outbid','won','lost') | DEFAULT 'active' | สถานะ |

**Status flow:**
- `active` → bid นี้เป็น bid สูงสุดอยู่
- `outbid` → มีคนอื่น bid สูงกว่าแล้ว (ได้เงินคืน)
- `won` → ชนะการประมูล
- `lost` → ประมูลจบแล้ว ไม่ได้ชนะ

---

### กลุ่ม 4: Wallet & Transactions (2 ตาราง)

#### 4.1 wallets - กระเป๋าเงิน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส wallet |
| user_id | BIGINT UNSIGNED | FK → users(id), CASCADE | เจ้าของ |
| balance_available | DECIMAL(10,2) | DEFAULT 0 | เงินที่ใช้ได้ |
| balance_total | DECIMAL(10,2) | DEFAULT 0 | เงินรวมทั้งหมด |
| balance_pending | DECIMAL(10,2) | DEFAULT 0 | เงินที่ถูก hold (escrow) |
| withdraw | DECIMAL(10,2) | DEFAULT 0 | ยอดถอนสะสม |
| deposit | DECIMAL(10,2) | DEFAULT 0 | ยอดเติมสะสม |

**สูตร:** `balance_total = balance_available + balance_pending`

**การทำงาน:**
- **เติมเงิน (topup):** balance_available +, balance_total +, deposit +
- **ถอนเงิน (withdraw):** balance_available -, balance_total -, withdraw +
- **ประมูล (bid):** balance_available → balance_pending (hold เงินไว้)
- **ถูกประมูลทับ (outbid):** balance_pending → balance_available (คืนเงิน)
- **ชนะประมูล:** balance_pending - (เงินออกจากระบบไป seller)
- **Escrow hold:** balance_available → balance_pending
- **Escrow release:** balance_pending → seller's balance_available

#### 4.2 wallet_transactions - ประวัติธุรกรรม
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัสธุรกรรม |
| user_id | BIGINT UNSIGNED | FK → users(id), CASCADE | เจ้าของ |
| wallet_id | BIGINT UNSIGNED | FK → wallets(id), CASCADE | wallet |
| type | ENUM | NOT NULL | ประเภทธุรกรรม |
| amount | DECIMAL(12,2) | NOT NULL | จำนวนเงิน (+/-) |
| description | VARCHAR(255) | NULLABLE | รายละเอียด |
| reference_type | VARCHAR(255) | NULLABLE | อ้างอิง (product/order) |
| reference_id | BIGINT UNSIGNED | NULLABLE | รหัสอ้างอิง |
| balance_after | DECIMAL(12,2) | NOT NULL | ยอดคงเหลือหลังทำรายการ |

**Transaction Types:**
| Type | ความหมาย | amount |
|------|----------|--------|
| topup | เติมเงิน | + |
| withdraw | ถอนเงิน | - |
| bid_placed | วางประมูล (hold เงิน) | - |
| bid_refund | คืนเงินเมื่อถูกประมูลทับ | + |
| auction_won | ชนะประมูล (หักเงินจริง) | - |
| auction_sold | ขายสำเร็จ (ได้รับเงิน) | + |
| escrow_hold | Hold เงินใน escrow | - |
| escrow_release | ปล่อยเงินจาก escrow | - (buyer) / + (seller) |
| escrow_refund | คืนเงิน escrow | + |

---

### กลุ่ม 5: Orders & Post-Auction (3 ตาราง)

#### 5.1 orders - คำสั่งซื้อ
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส order |
| user_id | BIGINT UNSIGNED | FK → users(id), CASCADE | ผู้ซื้อ (buyer) |
| seller_id | BIGINT UNSIGNED | FK → users(id), CASCADE, NULLABLE | ผู้ขาย (seller) |
| product_id | BIGINT UNSIGNED | FK → products(id), CASCADE | สินค้า |
| final_price | DECIMAL(10,2) | NOT NULL | ราคาสุดท้าย |
| order_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | วันสั่งซื้อ |
| status | ENUM | DEFAULT 'pending_buyer_confirm' | สถานะ |
| buyer_confirmed_at | TIMESTAMP | NULLABLE | วันที่ buyer ยืนยัน |
| shipped_at | TIMESTAMP | NULLABLE | วันที่จัดส่ง |
| received_at | TIMESTAMP | NULLABLE | วันที่รับสินค้า |
| confirm_deadline | TIMESTAMP | NULLABLE | กำหนด confirm (48 ชม.) |
| ship_deadline | TIMESTAMP | NULLABLE | กำหนดจัดส่ง (3 วัน) |
| receive_deadline | TIMESTAMP | NULLABLE | กำหนดรับสินค้า (7 วัน) |

**Order Status Flow:**
```
pending_buyer_confirm → confirmed → shipped → completed
                    ↓                    ↓
                cancelled           disputed → resolved_buyer / resolved_seller
```

| Status | ความหมาย |
|--------|----------|
| pending_buyer_confirm | รอผู้ซื้อยืนยัน (48 ชม.) |
| confirmed | ผู้ซื้อยืนยันแล้ว รอจัดส่ง |
| shipped | จัดส่งแล้ว รอรับสินค้า |
| completed | สำเร็จ (เงินโอนให้ seller) |
| disputed | มีข้อพิพาท (admin ตัดสิน) |
| cancelled | ยกเลิก |

#### 5.2 order_confirmations - ข้อมูลติดต่อ
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส |
| order_id | BIGINT UNSIGNED | FK → orders(id) | order |
| user_id | BIGINT UNSIGNED | FK → users(id) | user |
| role | ENUM('buyer','seller') | NOT NULL | บทบาท |
| phone | VARCHAR(20) | NOT NULL | เบอร์โทร |
| line_id | VARCHAR(100) | NULLABLE | LINE ID |
| facebook | VARCHAR(255) | NULLABLE | Facebook |
| note | TEXT | NULLABLE | หมายเหตุ |

**Unique:** (order_id, role) — 1 order มี buyer + seller อย่างละ 1

#### 5.3 disputes - ข้อพิพาท
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส |
| order_id | BIGINT UNSIGNED | FK → orders(id) | order ที่มีปัญหา |
| reporter_id | BIGINT UNSIGNED | FK → users(id) | ผู้แจ้ง (buyer) |
| reason | TEXT | NOT NULL | เหตุผล |
| evidence_images | JSON | NULLABLE | รูปหลักฐาน |
| status | ENUM('open','resolved_buyer','resolved_seller') | DEFAULT 'open' | สถานะ |
| admin_note | TEXT | NULLABLE | บันทึกจาก admin |
| resolved_at | TIMESTAMP | NULLABLE | วันที่แก้ไข |

---

### กลุ่ม 6: Reviews & Certificates (2 ตาราง)

#### 6.1 reviews - รีวิวผู้ขาย
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส |
| order_id | BIGINT UNSIGNED | FK → orders(id), UNIQUE | 1 order = 1 review |
| reviewer_id | BIGINT UNSIGNED | FK → users(id) | ผู้รีวิว (buyer) |
| seller_id | BIGINT UNSIGNED | FK → users(id) | ผู้ถูกรีวิว (seller) |
| rating | TINYINT | NOT NULL | คะแนน 1-5 |
| comment | TEXT | NULLABLE | ความเห็น |

**UNIQUE(order_id)** — ป้องกันรีวิวซ้ำ

#### 6.2 product_certificates - ใบรับรองสินค้า
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส |
| product_id | BIGINT UNSIGNED | FK → products(id), CASCADE | สินค้า |
| file_path | VARCHAR(255) | NOT NULL | ไฟล์ใบเซอร์ |
| original_name | VARCHAR(255) | NOT NULL | ชื่อไฟล์เดิม |
| status | ENUM('pending','approved','rejected') | DEFAULT 'pending' | สถานะ |
| admin_note | TEXT | NULLABLE | บันทึกจาก admin |
| verified_by | BIGINT UNSIGNED | FK → users(id), NULLABLE | admin ที่ตรวจ |
| verified_at | TIMESTAMP | NULLABLE | วันที่ตรวจ |

**Certificate Flow:**
```
pending → approved (สินค้าได้แท็ก Certified)
       → rejected (ไม่ผ่าน)
```

---

### กลุ่ม 7: Notifications, Reports, Strikes (3 ตาราง)

#### 7.1 notifications - การแจ้งเตือน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส |
| user_id | BIGINT UNSIGNED | FK → users(id) | ผู้รับ |
| type | ENUM | NOT NULL | ประเภท |
| title | VARCHAR(255) | NOT NULL | หัวข้อ |
| message | TEXT | NOT NULL | ข้อความ |
| product_id | BIGINT UNSIGNED | FK → products(id), NULLABLE | สินค้าที่เกี่ยวข้อง |
| is_read | BOOLEAN | DEFAULT false | อ่านแล้วหรือยัง |

**Notification Types:** outbid, won, lost, sold, new_bid, order, system

#### 7.2 reports - การรายงาน
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส |
| reporter_id | BIGINT UNSIGNED | FK → users(id) | ผู้รายงาน |
| reported_user_id | BIGINT UNSIGNED | FK → users(id) | ผู้ถูกรายงาน |
| reported_product_id | BIGINT UNSIGNED | FK → products(id), NULLABLE | สินค้าที่ถูกรายงาน |
| type | ENUM | NOT NULL | ประเภท (scam, fake_product, etc.) |
| description | TEXT | NOT NULL | รายละเอียด |
| evidence_images | JSON | NULLABLE | รูปหลักฐาน |
| status | ENUM('pending','reviewing','resolved','dismissed') | DEFAULT 'pending' | สถานะ |
| admin_note | TEXT | NULLABLE | บันทึก admin |

#### 7.3 user_strikes - การลงโทษ
| Column | Type | Constraints | คำอธิบาย |
|--------|------|-------------|----------|
| id | BIGINT UNSIGNED | PK | รหัส |
| user_id | BIGINT UNSIGNED | FK → users(id) | ผู้ถูกลงโทษ |
| order_id | BIGINT UNSIGNED | FK → orders(id), NULLABLE | order ที่เกี่ยวข้อง |
| reason | VARCHAR(255) | NOT NULL | เหตุผล |
| banned_until | TIMESTAMP | NULLABLE | แบนถึงวันที่ |

---

## Normalization (การทำ Normalization)

### First Normal Form (1NF) - ผ่าน
- ทุกตารางมี Primary Key
- ทุก column เก็บค่า atomic (ค่าเดียว) ยกเว้น `evidence_images` (JSON) ที่เก็บ array ของ paths
- ไม่มี repeating groups

### Second Normal Form (2NF) - ผ่าน
- ทุก non-key column ขึ้นกับ PK ทั้งหมด (ไม่มี partial dependency)
- ตัวอย่าง: ใน `bids` ทุก column (price, time, status) ขึ้นกับ bid id เท่านั้น

### Third Normal Form (3NF) - ผ่าน (ส่วนใหญ่)
- ไม่มี transitive dependency
- **ข้อยกเว้น:** `products.current_price` เป็น derived data (คำนวณจาก bid ล่าสุดได้) แต่เก็บไว้เพื่อ performance (denormalization โดยตั้งใจ)
- **ข้อยกเว้น:** `wallets.balance_total` = `balance_available + balance_pending` (denormalization เพื่อ performance)

---

## Indexes & Performance

### Primary Keys (ทุกตาราง)
- ใช้ `BIGINT UNSIGNED AUTO_INCREMENT` เป็น surrogate key

### Unique Indexes
| ตาราง | Column | เหตุผล |
|-------|--------|--------|
| users | email | ป้องกัน email ซ้ำ |
| users | phone_number | ป้องกันเบอร์ซ้ำ |
| reviews | order_id | 1 order = 1 review เท่านั้น |
| personal_access_tokens | token | token ต้องไม่ซ้ำ |

### Foreign Key Indexes
- Laravel สร้าง index อัตโนมัติสำหรับทุก foreign key
- ช่วยเร่ง JOIN queries

### ON DELETE Actions
| Action | ใช้กับ | ความหมาย |
|--------|--------|----------|
| CASCADE | bids, orders, notifications, etc. | ลบ parent → ลบ child ด้วย |
| SET NULL | category_id, subcategory_id | ลบ category → สินค้ายังอยู่ แต่ไม่มี category |
| NULL ON DELETE | verified_by (certificates) | ลบ admin → ใบเซอร์ยังอยู่ แต่ไม่มีคนตรวจ |

---

## ENUM Values Summary

| Table.Column | Values |
|-------------|--------|
| users.role | user, admin |
| products.status | active, completed, cancelled |
| bids.status | active, outbid, won, lost |
| orders.status | pending_confirm, pending_buyer_confirm, confirmed, shipped, completed, disputed, cancelled |
| disputes.status | open, resolved_buyer, resolved_seller |
| wallet_transactions.type | topup, withdraw, bid_placed, bid_refund, auction_won, auction_sold, escrow_hold, escrow_release, escrow_refund |
| notifications.type | outbid, won, lost, sold, new_bid, order, system |
| reports.type | scam, fake_product, harassment, inappropriate_content, other |
| reports.status | pending, reviewing, resolved, dismissed |
| product_certificates.status | pending, approved, rejected |
| order_confirmations.role | buyer, seller |

---

## สรุปสถิติ Database
- **จำนวนตาราง:** 24 ตาราง (17 ตารางหลัก + 7 ตาราง Laravel system)
- **จำนวน columns ทั้งหมด:** ~250 columns
- **จำนวน Foreign Keys:** 40+
- **จำนวน ENUM fields:** 11 fields
- **JSON fields:** 2 fields (evidence_images)
- **Unique Constraints:** 5+
