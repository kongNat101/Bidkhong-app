# 📁 สรุปโครงสร้าง Code — BidKhong Auction API

**Framework:** Laravel 12 · **PHP 8.2** · **MySQL 8** · **Docker**
**อัปเดต:** 20 กุมภาพันธ์ 2026

---

## 📂 Root Directory

```
auction-api/
├── app/                          # Application Code (หลัก)
├── config/                       # Configuration files
├── database/                     # Migrations + Seeders
├── docs/                         # เอกสารโปรเจกต์
├── public/                       # Public assets
├── routes/                       # API Routes
├── storage/                      # File uploads + logs
├── docker-compose.yml            # Docker config
├── Dockerfile                    # Docker image
├── .env                          # Environment (local)
├── .env.docker                   # Environment (Docker)
├── BidKhong_API.postman_collection.json  # Postman collection
└── README.md
```

---

## 🎯 app/ — Application Code

### Controllers (10 ไฟล์) — จัดการ API Logic

| ไฟล์ | หน้าที่ | Endpoints |
|------|--------|-----------|
| `AuthController.php` | สมัคร, Login, Profile, Wallet | 8 เส้น |
| `ProductController.php` | CRUD สินค้า, Search, Filter | 5 เส้น |
| `BidController.php` | ประมูล, Buy Now, ดู Bids | 4 เส้น |
| `CategoryController.php` | หมวดหมู่สินค้า | 3 เส้น |
| `OrderController.php` | จัดการ Orders | 3 เส้น |
| `PostAuctionController.php` | Confirm, Ship, Receive, Dispute | 5 เส้น |
| `NotificationController.php` | แจ้งเตือน | 4 เส้น |
| `ReportController.php` | รายงานปัญหา | 2 เส้น |
| `AdminController.php` | Admin Dashboard, จัดการ users | 6 เส้น |
| `Controller.php` | Base controller | — |

### Models (14 ไฟล์) — Database ORM

| Model | ตาราง | Relationships |
|-------|-------|---------------|
| `User.php` | users | hasOne Wallet, hasMany Products/Bids/Orders |
| `Wallet.php` | wallets | belongsTo User, hasMany Transactions |
| `WalletTransaction.php` | wallet_transactions | belongsTo User, Wallet |
| `Product.php` | products | belongsTo User/Category, hasMany Bids/Images |
| `ProductImage.php` | product_images | belongsTo Product |
| `Category.php` | categories | hasMany Subcategories |
| `Subcategory.php` | subcategories | belongsTo Category |
| `Bid.php` | bids | belongsTo User, Product |
| `Order.php` | orders | belongsTo User(buyer/seller), Product |
| `OrderConfirmation.php` | order_confirmations | belongsTo Order, User |
| `Dispute.php` | disputes | belongsTo Order, User |
| `UserStrike.php` | user_strikes | belongsTo User |
| `Notification.php` | notifications | belongsTo User, Product |
| `Report.php` | reports | belongsTo User |

### Middleware (1 ไฟล์)

| ไฟล์ | หน้าที่ |
|------|--------|
| `AdminMiddleware.php` | เช็คว่า user เป็น admin |

### Console Commands (1 ไฟล์)

| ไฟล์ | หน้าที่ |
|------|--------|
| `CloseExpiredAuctions.php` | ปิดประมูลอัตโนมัติทุก 1 นาที |

### Mail (1 ไฟล์)

| ไฟล์ | หน้าที่ |
|------|--------|
| `ResetPasswordMail.php` | ส่งอีเมล Reset Password |

---

## 🗄 database/ — Database Structure

### Migrations (23 ไฟล์)

| วันที่ | Migration | หน้าที่ |
|--------|----------|--------|
| Default | `create_users_table` | ตาราง users |
| Default | `create_cache_table` | ตาราง cache |
| Default | `create_jobs_table` | ตาราง jobs/queue |
| 14 ม.ค. | `create_products_table` | ตาราง products |
| 14 ม.ค. | `create_personal_access_tokens_table` | Sanctum tokens |
| 16 ม.ค. | `create_categories_table` | หมวดหมู่ |
| 16 ม.ค. | `create_subcategories_table` | หมวดหมู่ย่อย |
| 16 ม.ค. | `create_wallets_table` | กระเป๋าเงิน |
| 16 ม.ค. | `update_users_table` | เพิ่ม phone, role |
| 16 ม.ค. | `update_products_table` | เพิ่ม fields สินค้า |
| 16 ม.ค. | `create_bids_table` | ตาราง bids |
| 16 ม.ค. | `create_orders_table` | ตาราง orders |
| 4 ก.พ. | `create_notifications_table` | แจ้งเตือน |
| 5 ก.พ. | `create_wallet_transactions_table` | ธุรกรรมเงิน |
| 13 ก.พ. | `add_profile_image_to_users` | รูปโปรไฟล์ |
| 13 ก.พ. | `create_product_images_table` | รูปสินค้าเพิ่มเติม |
| 15 ก.พ. | `add_post_auction_fields_to_orders` | Escrow fields |
| 15 ก.พ. | `create_order_confirmations_table` | ข้อมูลติดต่อ |
| 15 ก.พ. | `create_disputes_table` | แจ้งปัญหา |
| 15 ก.พ. | `create_user_strikes_table` | ลงโทษ user |
| 16 ก.พ. | `update_post_auction_flow` | ปรับปรุง deadlines |
| 19 ก.พ. | `create_reports_table` | ระบบรายงาน |
| 20 ก.พ. | `update_products_add_start_time` | เพิ่ม auction_start_time |

### Seeders (4 ไฟล์)

| ไฟล์ | หน้าที่ |
|------|--------|
| `DatabaseSeeder.php` | ตัวหลัก — เรียก seeders อื่น |
| `CategorySeeder.php` | 6 หมวดหมู่ + 36 หมวดย่อย |
| `AdminSeeder.php` | สร้าง admin user |
| `ProductSeeder.php` | 27 สินค้า + 10 bidders + bids (HOT/ENDING/DEFAULT/ENDED/INCOMING) |

---

## 🛣 routes/ — API Routes

| ไฟล์ | หน้าที่ | Endpoints |
|------|--------|-----------|
| `api.php` | **ทุก API route** (40 endpoints) | auth, products, bids, wallet, orders, notifications, admin |
| `web.php` | Web routes (ไม่ได้ใช้) | — |
| `console.php` | Scheduler config | CloseExpiredAuctions ทุก 1 นาที |

---

## 🐳 Docker Files

| ไฟล์ | หน้าที่ |
|------|--------|
| `Dockerfile` | PHP 8.2 + extensions |
| `docker-compose.yml` | 2 services: app (PHP) + db (MySQL) |
| `docker-entrypoint.sh` | Auto migrate + seed เมื่อ start |
| `.env.docker` | Environment สำหรับ Docker |

---

## 📖 docs/ — เอกสาร

| ไฟล์ | เนื้อหา |
|------|--------|
| `01_MEETING_SUMMARY.md` | สรุปการประชุม |
| `02_FEATURES.md` | Features ทั้ง 10 ระบบ + 40 endpoints |
| `03_POST_AUCTION_FLOW.md` | Escrow flow ทั้งหมด |
| `04_ER_DIAGRAM_REVIEW.md` | ER Diagram review + สิ่งที่ต้องแก้ |
| `05_FRONTEND_HANDOFF.md` | คู่มือสำหรับ Frontend team |
| `06_FRONTEND_SCREEN_GUIDE.md` | แนะนำหน้าจอที่ต้องทำ |
| `06_GIT_COLLABORATION_GUIDE.md` | วิธี Git workflow |
| `PROFESSOR_QA_PREP.md` | เตรียมตัวคุยกับอาจารย์ |

---

## 🔑 Config สำคัญ

| ไฟล์ | ค่าสำคัญ |
|------|---------|
| `config/app.php` | timezone: `Asia/Bangkok` |
| `config/auth.php` | guard: Sanctum |
| `config/filesystems.php` | disk: public (local storage) |
| `.env` | DB: MySQL, MAIL: log/smtp |

---

## 📊 สรุปตัวเลข

| รายการ | จำนวน |
|--------|-------|
| Controllers | 10 |
| Models | 14 |
| Migrations | 23 |
| Seeders | 4 |
| API Endpoints | 40 |
| Database Tables | 13 |
| Middleware | 1 (Admin) |
| Console Commands | 1 (Auto-close) |
| Documentation | 8 ไฟล์ |
| Seed Products | 27 |
| Seed Users | 13 (3 sellers + 10 bidders) |
