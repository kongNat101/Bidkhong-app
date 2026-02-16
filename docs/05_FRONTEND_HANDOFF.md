# 📋 สรุป Backend → ส่งให้ทีม Frontend

**Project:** BidKhong Auction API (Laravel 12)
**Base URL:** `http://127.0.0.1:8000/api`
**อัปเดตล่าสุด:** 15 ก.พ. 2026

---

## 📡 API ทั้งหมด (35 เส้น )

### 🔓 ไม่ต้อง Login (9 เส้น)

```
POST  /api/register                    สมัคร (+ phone_number optional)
POST  /api/login                       เข้าสู่ระบบ
POST  /api/forgot-password             ขอ reset รหัส
POST  /api/reset-password              เปลี่ยนรหัส

GET   /api/products                    สินค้าทั้งหมด (search/filter/sort)
GET   /api/products/{id}               สินค้าชิ้นเดียว
GET   /api/categories                  หมวดหมู่ทั้งหมด
GET   /api/categories/{id}             หมวดหมู่เดียว
GET   /api/subcategories               subcategories
```

### 🔒 ต้อง Login — ส่ง `Authorization: Bearer TOKEN` (26 เส้น)

```
POST   /api/logout                     ออกจากระบบ
GET    /api/me                         ดูข้อมูลตัวเอง + wallet
POST   /api/profile/image             อัปรูปโปรไฟล์
PATCH  /api/profile                    แก้ไขข้อมูลส่วนตัว
POST   /api/change-password            เปลี่ยนรหัสผ่าน

POST   /api/wallet/topup               เติมเงิน
POST   /api/wallet/withdraw            ถอนเงิน
GET    /api/wallet/transactions         ประวัติธุรกรรม

POST   /api/products                   สร้างสินค้า (FormData)
DELETE /api/products/{id}               ลบสินค้า (เจ้าของเท่านั้น)
DELETE /api/products/{id}/images/{imgId} ลบรูปสินค้า

POST   /api/products/{id}/bid          ประมูล
POST   /api/products/{id}/buy-now      ซื้อทันที
GET    /api/products/{id}/bids          ประวัติ bid สินค้า
GET    /api/users/me/bids              ประวัติ bid ตัวเอง (+ summary + filter)

GET    /api/users/me/orders            ดู orders
POST   /api/products/{id}/close        ปิดประมูล (เจ้าของเท่านั้น)
PATCH  /api/orders/{id}/verify         ยืนยัน order (ผู้ซื้อเท่านั้น)

POST   /api/orders/{id}/confirm        ยืนยัน + กรอก contact
GET    /api/orders/{id}/detail          ดู order detail + contact อีกฝ่าย
POST   /api/orders/{id}/ship           ผู้ขายกดจัดส่ง
POST   /api/orders/{id}/receive        ผู้ชนะกดรับ → โอนเงิน
POST   /api/orders/{id}/dispute        แจ้งปัญหา + แนบรูป

GET    /api/notifications              แจ้งเตือนทั้งหมด
GET    /api/notifications/unread       ยังไม่อ่าน
PATCH  /api/notifications/read-all     อ่านทั้งหมด
PATCH  /api/notifications/{id}/read    อ่านรายการเดียว
```

---

## ⚠️ Breaking Changes ที่ Frontend ต้องรู้

### 1. `GET /api/products` — Pagination

```javascript
// เดิม: response.data = [array ของสินค้า]
// ใหม่: response.data.data = [array ของสินค้า]
//       response.data.current_page, last_page, total, next_page_url
```

### 2. `POST /api/products` — FormData

```javascript
const formData = new FormData();
formData.append('name', 'iPhone 16');
formData.append('starting_price', '30000');
formData.append('duration', '3');            // 1-5 วัน (แทน auction_end_time ได้)
formData.append('picture', mainImage);       // รูปหลัก (optional)
formData.append('images[]', extraImage1);    // รูปเพิ่ม (optional, max 8)

await axios.post('/api/products', formData, {
  headers: { 'Content-Type': 'multipart/form-data' }
});
```

### 3. `PATCH /api/profile` — Edit Profile (ใหม่)

```javascript
await axios.patch('/api/profile', {
  name: 'New Name',
  phone_number: '0812345678'
});
```

### 4. `POST /api/change-password` (ใหม่)

```javascript
await axios.post('/api/change-password', {
  current_password: 'old123456',
  new_password: 'new123456',
  new_password_confirmation: 'new123456'  // ต้องส่งด้วย
});
```

### 5. `POST /api/orders/{id}/confirm` (ใหม่ — Post-Auction)

```javascript
await axios.post(`/api/orders/${orderId}/confirm`, {
  phone: '0812345678',
  line_id: '@myline',       // optional
  facebook: 'fb.com/me',   // optional
  note: 'ติดต่อหลัง 6 โมง' // optional
});
```

### 6. `POST /api/orders/{id}/dispute` (ใหม่ — Post-Auction)

```javascript
const formData = new FormData();
formData.append('reason', 'สินค้าไม่ตรงปก');
formData.append('evidence_images[]', photo1);  // optional, max 5 รูป
formData.append('evidence_images[]', photo2);

await axios.post(`/api/orders/${orderId}/dispute`, formData, {
  headers: { 'Content-Type': 'multipart/form-data' }
});
```

---

## ❌ Error Codes ที่ต้อง Handle

| Code | ความหมาย | ตัวอย่าง |
|------|---------|---------|
| `400` | Bad Request | bid ต่ำ, ประมูลปิดแล้ว, รหัสผ่านเดิมผิด |
| `401` | Unauthorized | Token หมดอายุ / ไม่ได้ส่ง |
| `403` | Forbidden | ไม่ใช่เจ้าของ, ถูกแบน |
| `404` | Not Found | หา product/order ไม่เจอ |
| `422` | Validation Error | ข้อมูลไม่ครบ / format ผิด |
| `429` | Too Many Requests | เกิน rate limit |

---

## 🔑 วิธีใช้ Auth

```javascript
// 1. Register หรือ Login → ได้ token
const { data } = await axios.post('/api/login', { email, password });
const token = data.token;  // เก็บใน AsyncStorage

// 2. ทุก request ที่ต้อง Login
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
axios.defaults.headers.common['Accept'] = 'application/json';
```

---

## 💡 แนะนำลำดับ Integration

1. **Auth** → register, login, logout, me, profile, change-password (6 เส้น)
2. **Products + Categories** → ดูสินค้า, หมวดหมู่ (5 เส้น)
3. **Wallet** → เติม/ถอนเงิน (3 เส้น)
4. **Bidding** → ประมูล, ซื้อทันที (4 เส้น)
5. **Orders** → ดู/ปิดประมูล (3 เส้น)
6. **Post-Auction** → confirm, ship, receive, dispute (5 เส้น)
7. **Notifications** → แจ้งเตือน (4 เส้น)
