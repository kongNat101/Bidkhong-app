# 📱 คู่มือหน้าจอ Frontend — BidKhong

**อัปเดตล่าสุด:** 15 ก.พ. 2026

เอกสารนี้บอกว่า **แต่ละหน้าจอ** ต้องเรียก API อะไร และ **แสดง UI ยังไง** ตาม role + status

---

## 🗺 หน้าจอทั้งหมด

| Tab | หน้าจอ | API หลัก |
|-----|-------|----------|
| Home | หน้าแรก (สินค้า) | `GET /products` |
| Home | รายละเอียดสินค้า | `GET /products/{id}` + `GET /products/{id}/bids` |
| My Wallet | กระเป๋าเงิน | `GET /me` + `GET /wallet/transactions` |
| Seller | สร้างสินค้า | `POST /products` |
| My Bids | ประวัติ bid | `GET /users/me/bids` |
| Profile | โปรไฟล์ | `GET /me` |
| Profile | แก้ไขโปรไฟล์ | `PATCH /profile` |
| — | รายละเอียด Order | `GET /orders/{id}/detail` |

---

## 1. 🏠 Home — หน้าแรก

**API:** `GET /api/products?sort=newest`

**Sections (ใช้ sort ต่างกัน):**
| Section | Query |
|---------|-------|
| Hot Auctions | `?sort=most_bids` |
| Incoming | `?sort=incoming` |
| Ending Soon | `?sort=ending_soon` |
| ทั้งหมด | `?sort=newest` |

**Search bar:** ส่ง `?search=keyword`
**Filter:** `?category_id=1&min_price=100&max_price=5000`

---

## 2. 📦 รายละเอียดสินค้า

**API:**
- `GET /api/products/{id}` — ข้อมูลสินค้า
- `GET /api/products/{id}/bids` — ประวัติ bid

**ปุ่มที่แสดง:**

| เงื่อนไข | แสดงอะไร |
|----------|---------|
| สินค้า active + ไม่ใช่เจ้าของ | ปุ่ม **"Bid"** + ปุ่ม **"Buy Now"** (ถ้ามี buyout_price) |
| สินค้า active + เป็นเจ้าของ + หมดเวลาแล้ว | ปุ่ม **"ปิดประมูล"** |
| สินค้า active + เป็นเจ้าของ + ยังไม่หมดเวลา | ปุ่ม **"ลบสินค้า"** (ถ้ายังไม่มี bid) |
| สินค้า completed | แสดง **"ประมูลจบแล้ว"** + ราคาสุดท้าย |

---

## 3. 💰 My Wallet

**API:**
- `GET /api/me` → `user.wallet` — ยอดเงิน
- `GET /api/wallet/transactions` — ประวัติ
- `POST /api/wallet/topup` — เติมเงิน
- `POST /api/wallet/withdraw` — ถอนเงิน

**แสดง:**
- ยอดใช้ได้ (`balance_available`)
- ยอด Hold (`balance_pending`) — เงินที่ถูก hold จาก bid/escrow
- ยอดรวม (`balance_total`)

---

## 4. 📋 My Bids

**API:** `GET /api/users/me/bids`

**Response มี:**
```json
{
  "summary": { "total": 10, "winning": 2, "outbid": 3, "won": 4, "lost": 1 },
  "bids": { "data": [...] }
}
```

**Tab filter:** ส่ง `?status=winning`, `?status=outbid`, `?status=won`, `?status=lost`

---

## 5. 👤 Profile

**หน้าหลัก:**
- **API:** `GET /api/me`
- แสดง: รูป, ชื่อ, email, วันที่สมัคร

**Edit Profile:**
- **API:** `PATCH /api/profile`
- ส่ง: `{ name, email, phone_number }` (ส่งแค่ field ที่แก้ได้)

**Change Password (อยู่ใน Privacy & Security):**
- **API:** `POST /api/change-password`
- ส่ง: `{ current_password, new_password, new_password_confirmation }`
- ถ้ารหัสเดิมผิด → 400

**Logout:**
- **API:** `POST /api/logout`

---

## 6. 🤝 Order Detail — หน้าสำคัญที่สุด!

**API:** `GET /api/orders/{id}/detail`

**Response:**
```json
{
  "order": { "id": 1, "status": "pending_confirm", ... },
  "my_role": "buyer",
  "my_confirmation": null,
  "other_contact": null
}
```

### 🎯 แสดง UI ตาม status + role:

---

### Status = `pending_confirm` (รอยืนยัน)

| ถ้าฉันเป็น | my_confirmation | แสดงอะไร |
|-----------|----------------|---------|
| Buyer | `null` | ✅ **Form ยืนยัน** (เบอร์โทร, LINE, FB, หมายเหตุ) + ปุ่ม **"ยืนยัน"** |
| Buyer | มีค่าแล้ว | ⏳ "คุณยืนยันแล้ว — รอผู้ขายยืนยัน" |
| Seller | `null` | ✅ **Form ยืนยัน** + ปุ่ม **"ยืนยัน"** |
| Seller | มีค่าแล้ว | ⏳ "คุณยืนยันแล้ว — รอผู้ชนะยืนยัน" |

**กดยืนยัน → API:** `POST /api/orders/{id}/confirm`
```json
{ "phone": "0812345678", "line_id": "@me", "facebook": "", "note": "" }
```

**แสดง countdown:** `order.confirm_deadline` — เหลือกี่ชั่วโมง

---

### Status = `confirmed` (ยืนยันแล้ว — รอจัดส่ง)

| ถ้าฉันเป็น | แสดงอะไร |
|-----------|---------|
| **Buyer** | 📱 **Contact ผู้ขาย** (จาก `other_contact`) + ⏳ "รอผู้ขายจัดส่ง" |
| **Seller** | 📱 **Contact ผู้ชนะ** (จาก `other_contact`) + ปุ่ม **"กดจัดส่งแล้ว"** |

**กดจัดส่ง → API:** `POST /api/orders/{id}/ship`

**แสดง countdown:** `order.ship_deadline` — ผู้ขายเหลือเวลาจัดส่งกี่วัน

**แสดง Contact:**
```
📞 เบอร์โทร: 081-234-5678
💬 LINE: @seller_line
📘 Facebook: fb.com/seller
📝 หมายเหตุ: ติดต่อหลัง 6 โมง
```

---

### Status = `shipped` (จัดส่งแล้ว — รอรับ)

| ถ้าฉันเป็น | แสดงอะไร |
|-----------|---------|
| **Buyer** | ปุ่ม **"รับสินค้าแล้ว ✅"** + ปุ่ม **"แจ้งปัญหา ⚠️"** + Contact ผู้ขาย |
| **Seller** | ⏳ "รอผู้ชนะกดรับสินค้า" + Contact ผู้ชนะ |

**กดรับ → API:** `POST /api/orders/{id}/receive`

**กดแจ้งปัญหา → เปิด Form:**
- เหตุผล (required, text)
- แนบรูปหลักฐาน (optional, สูงสุด 5 รูป)
- **API:** `POST /api/orders/{id}/dispute` (FormData)

**แสดง countdown:** `order.receive_deadline` — เหลือกี่วันก่อน auto-complete

---

### Status = `completed` (เสร็จสิ้น ✅)

| ถ้าฉันเป็น | แสดงอะไร |
|-----------|---------|
| Buyer | ✅ "สำเร็จ! ได้รับสินค้าแล้ว" + ราคา + วันที่ |
| Seller | ✅ "สำเร็จ! ได้รับเงินแล้ว 💰" + ราคา + วันที่ |

---

### Status = `disputed` (อยู่ระหว่างตรวจสอบ)

| ถ้าฉันเป็น | แสดงอะไร |
|-----------|---------|
| Buyer | ⚠️ "กำลังตรวจสอบ — Admin จะตัดสินภายใน X วัน" + แสดงเหตุผลที่แจ้ง |
| Seller | ⚠️ "มีการแจ้งปัญหา — Admin กำลังตรวจสอบ" |

---

### Status = `cancelled` (ยกเลิก)

| แสดง |
|------|
| ❌ "Order ถูกยกเลิก" + เหตุผล (หมดเวลา confirm / เงินไม่พอ) |

---

## 7. 🛒 My Orders — หน้ารวม Orders

**API:** `GET /api/users/me/orders`

**แสดง list ของ orders:**

| Status | Badge สี | ข้อความ |
|--------|---------|--------|
| `pending_confirm` | 🟡 เหลือง | "รอยืนยัน" |
| `confirmed` | 🔵 น้ำเงิน | "รอจัดส่ง" |
| `shipped` | 🟣 ม่วง | "กำลังขนส่ง" |
| `completed` | 🟢 เขียว | "สำเร็จ" |
| `disputed` | 🟠 ส้ม | "กำลังตรวจสอบ" |
| `cancelled` | 🔴 แดง | "ยกเลิก" |

**กดแต่ละ order → ไปหน้า Order Detail**

---

## 📊 สรุป: API mapping ต่อหน้าจอ

```
Home
  └─ GET /products?sort=...

Product Detail
  ├─ GET /products/{id}
  ├─ GET /products/{id}/bids
  ├─ POST /products/{id}/bid          ← กด Bid
  ├─ POST /products/{id}/buy-now      ← กด Buy Now
  ├─ POST /products/{id}/close        ← กดปิดประมูล
  └─ DELETE /products/{id}            ← กดลบ

My Wallet
  ├─ GET /me
  ├─ GET /wallet/transactions
  ├─ POST /wallet/topup
  └─ POST /wallet/withdraw

My Bids
  └─ GET /users/me/bids?status=...

My Orders
  └─ GET /users/me/orders

Order Detail                           ← หน้าสำคัญที่สุด!
  ├─ GET /orders/{id}/detail
  ├─ POST /orders/{id}/confirm        ← กดยืนยัน
  ├─ POST /orders/{id}/ship           ← กดจัดส่ง
  ├─ POST /orders/{id}/receive        ← กดรับ
  └─ POST /orders/{id}/dispute        ← กดแจ้งปัญหา

Profile
  ├─ GET /me
  ├─ PATCH /profile                   ← แก้ข้อมูล
  ├─ POST /change-password            ← เปลี่ยนรหัส
  ├─ POST /profile/image              ← เปลี่ยนรูป
  └─ POST /logout
```
