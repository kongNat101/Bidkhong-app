# 📱 BidKhong Auction API — Features ทั้งหมด

**Framework:** Laravel 12 · **Database:** MySQL (Docker) · **Auth:** Laravel Sanctum
**อัปเดตล่าสุด:** 15 กุมภาพันธ์ 2026

---

## 📋 สรุป Features (10 ระบบ)

| # | ระบบ | Endpoints | สถานะ |
|---|------|-----------|-------|
| 1 | 🔐 Authentication & Profile | 8 | ✅ เสร็จ |
| 2 | 📦 Product Management | 4 | ✅ เสร็จ |
| 3 | 📂 Categories & Subcategories | 3 | ✅ เสร็จ |
| 4 | 🔨 ระบบประมูล (Bidding) | 4 | ✅ เสร็จ |
| 5 | 💰 ระบบ Wallet | 3 | ✅ เสร็จ |
| 6 | 🛒 ระบบ Orders | 3 | ✅ เสร็จ |
| 7 | 🤝 Post-Auction (Escrow) | 5 | ✅ เสร็จ |
| 8 | 🔔 ระบบ Notifications | 4 | ✅ เสร็จ |
| 9 | ⏰ Auto-close Auctions | Scheduler | ✅ เสร็จ |
| 10 | 🛡 Security & Rate Limiting | Middleware | ✅ เสร็จ |

---

## 1. 🔐 Authentication & Profile

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `POST` | `/api/register` | ❌ | สมัครสมาชิก (+ phone_number optional) |
| `POST` | `/api/login` | ❌ | เข้าสู่ระบบ → ได้ Token |
| `POST` | `/api/logout` | ✅ | ออกจากระบบ (ลบ Token) |
| `GET` | `/api/me` | ✅ | ดูข้อมูลตัวเอง + Wallet |
| `POST` | `/api/profile/image` | ✅ | อัปรูปโปรไฟล์ (max 2MB) |
| `PATCH` | `/api/profile` | ✅ | แก้ไขชื่อ, email, เบอร์โทร |
| `POST` | `/api/change-password` | ✅ | เปลี่ยนรหัสผ่าน (ยืนยันรหัสเดิม) |
| `POST` | `/api/forgot-password` | ❌ | ขอ reset token (หมดอายุ 60 นาที) |
| `POST` | `/api/reset-password` | ❌ | เปลี่ยนรหัสด้วย token |

---

## 2. 📦 Product Management

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `GET` | `/api/products` | ❌ | ดูสินค้า (pagination + search + filter + sort) |
| `GET` | `/api/products/{id}` | ❌ | ดูสินค้าชิ้นเดียว |
| `POST` | `/api/products` | ✅ | สร้างสินค้าประมูล (FormData, สูงสุด 8 รูป) |
| `DELETE` | `/api/products/{id}` | ✅ | ลบสินค้า (เจ้าของเท่านั้น) |

**Query Params สำหรับ `GET /products`:**
- `search`, `category_id`, `subcategory_id`, `min_price`, `max_price`
- `location`, `status`
- `sort`: `newest`, `price_asc`, `price_desc`, `ending_soon`, `most_bids`, `incoming`

**สร้างสินค้า:**
- รองรับ `duration` (1-5 วัน) แทน `auction_end_time`
- `min_price` เป็น optional
- รูปภาพสูงสุด 8 รูป (max 5MB/รูป)

---

## 3. 📂 Categories & Subcategories

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `GET` | `/api/categories` | ❌ | ดูหมวดหมู่ทั้งหมดพร้อม subcategories |
| `GET` | `/api/categories/{id}` | ❌ | ดูหมวดหมู่เดียว |
| `GET` | `/api/subcategories` | ❌ | ดู subcategories ทั้งหมด |

6 หมวดหมู่: Electronics, Fashion, Collectibles, Home, Vehicles, Others (36 ย่อย)

---

## 4. 🔨 ระบบประมูล (Bidding)

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `POST` | `/api/products/{id}/bid` | ✅ | ประมูลสินค้า |
| `POST` | `/api/products/{id}/buy-now` | ✅ | ซื้อทันทีในราคา Buyout |
| `GET` | `/api/products/{id}/bids` | ✅ | ดูประวัติ bid ของสินค้า |
| `GET` | `/api/users/me/bids` | ✅ | ดู bid ตัวเอง (+ summary + filter) |

**My Bids Summary:** ส่ง `total`, `winning`, `outbid`, `won`, `lost` + filter `?status=winning`

---

## 5. 💰 ระบบ Wallet (Simulated)

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `POST` | `/api/wallet/topup` | ✅ | เติมเงิน |
| `POST` | `/api/wallet/withdraw` | ✅ | ถอนเงิน (ขั้นต่ำ 100 บาท) |
| `GET` | `/api/wallet/transactions` | ✅ | ประวัติธุรกรรม |

**ประเภทยอดเงิน:** `balance_available`, `balance_pending`, `balance_total`
**Transaction Types:** `topup`, `withdraw`, `bid_placed`, `bid_refund`, `auction_won`, `auction_sold`, `escrow_hold`, `escrow_release`

> 💡 Wallet เป็น simulated — logic ด้านเงินพร้อมเชื่อม payment gateway จริงได้เลย

---

## 6. 🛒 ระบบ Orders

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `GET` | `/api/users/me/orders` | ✅ | ดู orders ของตัวเอง |
| `POST` | `/api/products/{id}/close` | ✅ | ปิดประมูล (เจ้าของเท่านั้น) |
| `PATCH` | `/api/orders/{id}/verify` | ✅ | ยืนยัน order (ผู้ซื้อเท่านั้น) |

**เมื่อปิดประมูล:** สร้าง Order status `pending_confirm` → แจ้งเตือนทั้ง 2 ฝั่ง → รอ confirm 48 ชม.

---

## 7. 🤝 Post-Auction (Escrow System)

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `POST` | `/api/orders/{id}/confirm` | ✅ | ยืนยัน + กรอก contact |
| `GET` | `/api/orders/{id}/detail` | ✅ | ดู order + contact อีกฝ่าย |
| `POST` | `/api/orders/{id}/ship` | ✅ | ผู้ขายกดจัดส่ง |
| `POST` | `/api/orders/{id}/receive` | ✅ | ผู้ชนะกดรับ → โอนเงิน |
| `POST` | `/api/orders/{id}/dispute` | ✅ | แจ้งปัญหา + แนบรูป |

**Order Status Flow:**
```
pending_confirm → confirmed → shipped → completed
                                      → disputed → resolved
              → cancelled (timeout)
```

**Time Limits:**
| ขั้นตอน | เวลา | ถ้าหมดเวลา |
|---------|------|-----------|
| Confirm | 48 ชม. | ยกเลิก + strike |
| จัดส่ง | 3 วัน | ยกเลิก + คืนเงิน + strike |
| กดรับสินค้า | 7 วัน | Auto-complete |

---

## 8. 🔔 ระบบ Notifications

| Method | Endpoint | Auth | คำอธิบาย |
|--------|----------|------|---------|
| `GET` | `/api/notifications` | ✅ | ดูแจ้งเตือนทั้งหมด |
| `GET` | `/api/notifications/unread` | ✅ | เฉพาะยังไม่อ่าน |
| `PATCH` | `/api/notifications/read-all` | ✅ | อ่านทั้งหมด |
| `PATCH` | `/api/notifications/{id}/read` | ✅ | อ่านรายการเดียว |

**Types:** outbid, won, lost, sold, order

---

## 9. ⏰ Auto-close Auctions

Scheduler รัน `auctions:close-expired` ทุก 1 นาที → ปิดประมูลอัตโนมัติ + สร้าง Order + แจ้งเตือน

---

## 10. 🛡 Security

| Feature | รายละเอียด |
|---------|-----------|
| Token-based Auth | Laravel Sanctum |
| Rate Limit (Auth) | 10 req/min |
| Rate Limit (Public) | 60 req/min |
| Rate Limit (Protected) | 100 req/min |
| IDOR Protection | scope ตาม user |
| Race Condition | DB Transaction + Lock |

---

## 🗄 Database (12 Tables)

| Table | คำอธิบาย |
|-------|---------|
| users | ผู้ใช้ |
| wallets | กระเป๋าเงิน |
| wallet_transactions | ประวัติธุรกรรม |
| products | สินค้าประมูล |
| product_images | รูปสินค้า |
| categories | หมวดหมู่หลัก |
| subcategories | หมวดหมู่ย่อย |
| bids | การประมูล |
| orders | คำสั่งซื้อ |
| order_confirmations | contact info ตอน confirm |
| disputes | แจ้งปัญหา |
| user_strikes | strike/ban |
| notifications | แจ้งเตือน |

---

## 🔌 วิธีเรียกใช้ API

```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept: application/json

Base URL: http://127.0.0.1:8000/api
```
