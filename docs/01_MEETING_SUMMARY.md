# 📋 BidKhong — สรุปงาน Backend ทั้งหมด (สำหรับประชุมทีม)

**อัปเดตล่าสุด:** 15 ก.พ. 2026

---

## 🏗 ภาพรวมระบบ

- **Framework:** Laravel 12
- **Database:** MySQL (Docker)
- **Auth:** Laravel Sanctum (Token-based)
- **API ทั้งหมด:** 35 endpoints (9 public + 26 protected)
- **Database Tables:** 12 tables

---

## ✅ งานที่เสร็จแล้วทั้งหมด

---

### 1. 🐛 แก้ Bug 12 ตัว

| # | Bug | สถานะ |
|---|-----|-------|
| 1 | Login ไม่ได้ (password hash ซ้ำ) | ✅ แก้แล้ว |
| 2 | เจ้าของ bid ของตัวเองได้ | ✅ บล็อกแล้ว (400) |
| 3 | Bid สินค้าที่ปิดแล้วได้ | ✅ บล็อกแล้ว (400) |
| 4 | ใครก็ปิดประมูลได้ | ✅ เช็คเจ้าของ (403) |
| 5 | ใครก็ verify order ได้ | ✅ เช็คผู้ซื้อ (403) |
| 6 | อ่าน notification คนอื่นได้ | ✅ scope ตาม user |
| 7 | ปิดประมูลแล้วไม่ refund | ✅ refund อัตโนมัติ |
| 8 | Products โหลดทีเดียว | ✅ Pagination (20/หน้า) |
| 9 | เติม/ถอนเงินพร้อมกัน ยอดผิด | ✅ DB lock |
| 10-12 | Route conflict, code cleanup | ✅ แก้แล้ว |

---

### 2. 📸 Image Upload

- **รูปโปรไฟล์:** `POST /api/profile/image` (max 2MB)
- **รูปสินค้า:** สูงสุด 8 รูปต่อชิ้น (max 5MB/รูป)
- **ลบรูป:** `DELETE /api/products/{id}/images/{imageId}`

---

### 3. 🔍 Search & Filter

`GET /api/products` รองรับ query params:
- `search` — ค้นหาชื่อ
- `category_id`, `subcategory_id` — กรองตามหมวดหมู่
- `min_price`, `max_price` — กรองตามราคา
- `location`, `status` — กรองตามที่ตั้งและสถานะ
- `sort` — เรียงตาม: `newest`, `price_asc`, `price_desc`, `ending_soon`, `most_bids`, `incoming`

---

### 4. 🔑 Auth & Profile

- สมัครสมาชิกพร้อม phone_number (optional)
- Password Reset: `forgot-password` → `reset-password` (token หมดอายุ 60 นาที)
- **Edit Profile:** `PATCH /api/profile` — แก้ชื่อ, email, เบอร์โทร
- **Change Password:** `POST /api/change-password` — ต้องยืนยันรหัสเดิมก่อน

---

### 5. 📂 Categories & Subcategories

6 หมวดหมู่หลัก + 36 หมวดหมู่ย่อย (seed ไว้แล้ว):
- Electronics, Fashion, Collectibles, Home, Vehicles, Others

---

### 6. 🔨 ระบบประมูล

- **Bid** — เช็ค: ราคา, ยอดเงิน, สถานะ, เจ้าของ, เวลา
- **Buy Now** — ซื้อทันทีในราคา buyout
- **Auto-close** — Scheduler ปิดประมูลอัตโนมัติทุก 1 นาที
- **Hold เงิน** — หักจาก available → pending ตอน bid
- **Refund อัตโนมัติ** — คืนเงินเมื่อถูก outbid
- **ลบสินค้า** — `DELETE /products/{id}` (เจ้าของเท่านั้น)
- **Product sort** — `most_bids` (Hot) + `incoming`

---

### 7. 💰 ระบบ Wallet (Simulated)

- เติมเงิน, ถอนเงิน (ขั้นต่ำ 100 บาท)
- Transaction history
- 3 ยอดเงิน: available, pending, total
- ป้องกัน race condition ด้วย DB lock

> 💡 **หมายเหตุ:** Wallet เป็น simulated payment เพื่อ demo flow — logic ด้านเงิน (hold, refund, escrow) พร้อมเชื่อม payment gateway จริงได้เลย

---

### 8. 🔔 ระบบ Notifications

- แจ้งเตือนอัตโนมัติ: outbid, won, lost, sold, order
- ดูทั้งหมด / เฉพาะยังไม่อ่าน / mark as read

---

### 9. 🛡 Security & Rate Limiting

| Feature | รายละเอียด |
|---------|-----------|
| Auth | Token-based (Sanctum) |
| Rate Limit (Auth) | 10 req/min |
| Rate Limit (Public) | 60 req/min |
| Rate Limit (Protected) | 100 req/min |
| IDOR Protection | scope ตาม user |
| Race Condition | DB Transaction + Lock |

---

### 10. 🤝 Post-Auction Feature (Escrow System)

**Flow หลังประมูลจบ:**

```
ประมูลจบ → แจ้งเตือน 2 ฝั่ง (48 ชม.)
  → Confirm + กรอก Contact (เบอร์โทร, LINE, FB)
  → แลก Contact + Hold เงิน (Escrow)
  → ผู้ขายกดจัดส่ง (3 วัน)
  → ผู้ชนะกดรับ → โอนเงินให้ผู้ขาย ✅
  → หรือกด "แจ้งปัญหา" → Admin ตัดสิน
```

**API ใหม่ 5 ตัว:**

| Method | Endpoint | ทำอะไร |
|--------|----------|--------|
| POST | `/orders/{id}/confirm` | ยืนยัน + กรอก contact |
| GET | `/orders/{id}/detail` | ดู order + contact อีกฝ่าย |
| POST | `/orders/{id}/ship` | กดจัดส่ง |
| POST | `/orders/{id}/receive` | กดรับ → โอนเงิน |
| POST | `/orders/{id}/dispute` | แจ้งปัญหา + แนบรูป |

**Database ใหม่ 3 table:**
- `order_confirmations` — contact info
- `disputes` — ข้อมูลแจ้งปัญหา
- `user_strikes` — strike/ban (3 strikes = แบน 7 วัน)

---

## 📡 API ทั้งหมด (35 endpoints)

### 🔓 ไม่ต้อง Login (9 เส้น)

```
POST  /api/register                      สมัคร (+ phone_number optional)
POST  /api/login                         เข้าสู่ระบบ
POST  /api/forgot-password               ขอ reset รหัส
POST  /api/reset-password                เปลี่ยนรหัส
GET   /api/products                      สินค้าทั้งหมด (search/filter/sort)
GET   /api/products/{id}                 สินค้าชิ้นเดียว
GET   /api/categories                    หมวดหมู่ทั้งหมด
GET   /api/categories/{id}               หมวดหมู่เดียว
GET   /api/subcategories                 subcategories
```

### 🔒 ต้อง Login (26 เส้น)

```
POST   /api/logout                       ออกจากระบบ
GET    /api/me                           ดูข้อมูลตัวเอง + wallet
POST   /api/profile/image               อัปรูปโปรไฟล์
PATCH  /api/profile                      แก้ไขข้อมูลส่วนตัว
POST   /api/change-password              เปลี่ยนรหัสผ่าน

POST   /api/wallet/topup                 เติมเงิน
POST   /api/wallet/withdraw              ถอนเงิน
GET    /api/wallet/transactions           ประวัติธุรกรรม

POST   /api/products                     สร้างสินค้า (FormData)
DELETE /api/products/{id}                 ลบสินค้า (เจ้าของ)
DELETE /api/products/{id}/images/{imgId}  ลบรูปสินค้า

POST   /api/products/{id}/bid            ประมูล
POST   /api/products/{id}/buy-now        ซื้อทันที
GET    /api/products/{id}/bids            ประวัติ bid สินค้า
GET    /api/users/me/bids                ประวัติ bid ตัวเอง (+ summary + filter)

GET    /api/users/me/orders              ดู orders
POST   /api/products/{id}/close          ปิดประมูล (เจ้าของ)
PATCH  /api/orders/{id}/verify           ยืนยัน order (ผู้ซื้อ)

POST   /api/orders/{id}/confirm          ยืนยัน + กรอก contact
GET    /api/orders/{id}/detail            ดู order detail + contact
POST   /api/orders/{id}/ship             กดจัดส่ง
POST   /api/orders/{id}/receive          กดรับ → โอนเงิน
POST   /api/orders/{id}/dispute          แจ้งปัญหา

GET    /api/notifications                แจ้งเตือนทั้งหมด
GET    /api/notifications/unread         ยังไม่อ่าน
PATCH  /api/notifications/read-all       อ่านทั้งหมด
PATCH  /api/notifications/{id}/read      อ่านรายการเดียว
```

---

## ⚠️ ER Diagram — ต้องอัปเดต

1. ❌ ขาด 5 table: Categories, Subcategories, ProductImages, WalletTransactions, Notifications
2. ❌ ขาด 3 table ใหม่: order_confirmations, disputes, user_strikes
3. ⚠️ Order relationship ผิด (ต้องผูกกับ Product ไม่ใช่ Bid)
4. ⚠️ Products ขาด `subcategory_id`

---

## 📌 สิ่งที่ยังเหลือ (อนาคต)

| งาน | ใคร | เมื่อไหร่ |
|-----|-----|---------|
| อัปเดต ER Diagram ใน FigJam | ทีม | เร็ว ๆ นี้ |
| รัน migration ใน Docker | Backend | ก่อน test |
| หน้าจอ Post-Auction (Frontend) | Frontend | หลัง API พร้อม |
| Scheduler: auto-complete 7 วัน | Backend | Phase 2 |
| Scheduler: auto-cancel 48 ชม. | Backend | Phase 2 |
| Admin panel dispute | Backend + Frontend | Phase 2 |
| อัปเดต Postman Collection | Backend | เร็ว ๆ นี้ |
