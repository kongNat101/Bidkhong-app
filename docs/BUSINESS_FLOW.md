# BidKhong - Business Flow

## 1. System Overview (ภาพรวมระบบ)

```
BidKhong = ระบบประมูลสินค้าออนไลน์ + Escrow Wallet

Tech Stack: Laravel 12 + MySQL + Docker + Sanctum Auth

ผู้ใช้งาน 3 ระดับ:
  - Guest     → ดูสินค้า, ดูหมวดหมู่, ดูรีวิว
  - User      → ซื้อ + ขาย + ประมูล + Wallet + Report
  - Admin     → จัดการ Reports/Disputes, Users, Certificates, Dashboard
```

---

## 2. Business Flow หลัก

### Flow 1: สมัครสมาชิก & จัดการบัญชี

```
┌─────────────────────────────────────────────────────────────┐
│                     Authentication Flow                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Register ──→ สร้าง User + Wallet (balance = 0)             │
│      │                                                       │
│      ▼                                                       │
│  Login ──→ ได้ Sanctum Token ──→ เข้าถึง API ทั้งหมด        │
│      │                                                       │
│      ▼                                                       │
│  Profile ──→ แก้ชื่อ / อีเมล / โทรศัพท์ / รูปโปรไฟล์       │
│      │                                                       │
│      ▼                                                       │
│  Change Password / Forgot Password (OTP 6 หลัก → email)     │
│                                                              │
└─────────────────────────────────────────────────────────────┘

Validation:
  - email + phone_number ต้องไม่ซ้ำ
  - Forgot Password: OTP หมดอายุ 60 นาที
  - Reset Password: ลบ token ทั้งหมด (force logout ทุกอุปกรณ์)
```

---

### Flow 2: Wallet (กระเป๋าเงิน)

```
┌─────────────────────────────────────────────────────────────┐
│                       Wallet Structure                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  balance_available  = เงินที่ใช้ได้ (ซื้อ/ประมูล/ถอน)       │
│  balance_pending    = เงินที่ถูก hold (bid/escrow)           │
│  balance_total      = available + pending                    │
│  deposit            = ยอดเติมสะสม                             │
│  withdraw           = ยอดถอนสะสม                             │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Topup   ──→ balance_available += amount                     │
│              balance_total += amount                         │
│              deposit += amount                               │
│              สร้าง transaction type: "topup"                 │
│                                                              │
│  Withdraw ──→ balance_available -= amount                    │
│              balance_total -= amount                         │
│              withdraw += amount                              │
│              สร้าง transaction type: "withdraw"              │
│              (ขั้นต่ำ 100 บาท, ต้องระบุ bank details)        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 3: ลงขายสินค้า (Product Listing)

```
┌─────────────────────────────────────────────────────────────┐
│                    Product Creation Flow                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Seller สร้างสินค้า:                                         │
│  ├── ชื่อสินค้า + คำอธิบาย                                   │
│  ├── starting_price (ราคาเริ่มต้น)                           │
│  ├── bid_increment (ขั้นต่ำที่เพิ่มได้ต่อครั้ง)               │
│  ├── buyout_price (ซื้อทันที, optional)                      │
│  ├── duration (1-5 วัน) → คำนวณ auction_end_time             │
│  ├── auction_start_time (default = now)                      │
│  ├── category + subcategory                                  │
│  ├── location                                                │
│  ├── รูปหลัก 1 + รูปเพิ่ม 8 (max 5MB/รูป)                   │
│  └── certificate (optional, max 10MB)                        │
│                                                              │
│  ผลลัพธ์: Product status = "active"                          │
│           current_price = starting_price                     │
│           Auction เริ่มนับถอยหลัง                              │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│  Product Status:                                             │
│  active → completed (มีคนชนะ) / cancelled (ไม่มีคน bid)      │
│  active → sold (Buy Now)                                     │
├─────────────────────────────────────────────────────────────┤
│  Tags (คำนวณอัตโนมัติ):                                      │
│  hot     = bid_count ≥ 10                                    │
│  ending  = active + เหลือเวลา ≤ 6 ชั่วโมง                   │
│  incoming = auction_start_time > now (ยังไม่เริ่ม)            │
│  ended   = auction_end_time < now (หมดเวลาแล้ว)              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 4: การประมูล (Bidding) ⭐

```
┌─────────────────────────────────────────────────────────────┐
│                       Bidding Flow                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ผู้ซื้อ เสนอราคา:                                           │
│  ├── ต้อง ≥ current_price + bid_increment                    │
│  ├── ห้ามประมูลสินค้าตัวเอง                                   │
│  ├── Auction ต้อง active + ยังไม่หมดเวลา                      │
│  └── ต้องมีเงินพอ (balance_available ≥ bid amount)           │
│                                                              │
│  ขั้นตอน (DB Transaction):                                   │
│  ┌───────────────────────────────────────────────────┐       │
│  │ 1. เปลี่ยนคนเดิมเป็น "outbid"                     │       │
│  │ 2. คืนเงินคนเดิม:                                 │       │
│  │    balance_available += (bid เดิม)                 │       │
│  │    balance_pending -= (bid เดิม)                   │       │
│  │    สร้าง transaction: "bid_refund"                 │       │
│  │    ส่งแจ้งเตือน: "มีคนเสนอราคาสูงกว่า"              │       │
│  │                                                    │       │
│  │ 3. หักเงินคนใหม่:                                  │       │
│  │    balance_available -= bid amount                 │       │
│  │    balance_pending += bid amount                   │       │
│  │    สร้าง transaction: "bid_placed"                 │       │
│  │                                                    │       │
│  │ 4. สร้าง Bid record (status: "active")             │       │
│  │ 5. อัปเดต product.current_price                    │       │
│  └───────────────────────────────────────────────────┘       │
│                                                              │
│  Bid Status Lifecycle:                                       │
│  active → outbid (มีคนเสนอสูงกว่า, คืนเงิน)                 │
│  active → won (ชนะประมูล)                                    │
│  active → lost (แพ้ประมูล, คืนเงิน)                          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 4.1: ซื้อทันที (Buy Now)

```
┌─────────────────────────────────────────────────────────────┐
│                       Buy Now Flow                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  เงื่อนไข:                                                   │
│  ├── สินค้าต้องมี buyout_price                               │
│  ├── Auction ยัง active + ไม่หมดเวลา                         │
│  ├── ไม่ใช่สินค้าตัวเอง                                      │
│  └── มีเงินพอ                                                │
│                                                              │
│  ขั้นตอน (DB Transaction):                                   │
│  ┌───────────────────────────────────────────────────┐       │
│  │ 1. คืนเงิน bid ทั้งหมด (set status: "lost")       │       │
│  │    → คืน balance_available ให้ทุกคน                │       │
│  │    → ส่ง notification "lost" ทุกคน                 │       │
│  │                                                    │       │
│  │ 2. หักเงินผู้ซื้อ:                                 │       │
│  │    buyer.balance_available -= buyout_price         │       │
│  │    buyer.balance_total -= buyout_price             │       │
│  │                                                    │       │
│  │ 3. โอนเงินให้ผู้ขายทันที:                           │       │
│  │    seller.balance_available += buyout_price        │       │
│  │    seller.balance_total += buyout_price            │       │
│  │                                                    │       │
│  │ 4. สร้าง Bid (status: "won")                       │       │
│  │ 5. สร้าง Order (status: "pending_buyer_confirm")   │       │
│  │ 6. Product status → "completed"                    │       │
│  │ 7. แจ้งเตือน buyer + seller                        │       │
│  └───────────────────────────────────────────────────┘       │
│                                                              │
│  * Buy Now = จ่ายเงินตรงให้ seller ทันที (ไม่ผ่าน Escrow)    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 5: ปิดประมูล & สร้าง Order

```
┌─────────────────────────────────────────────────────────────┐
│                    Close Auction Flow                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  เมื่อเวลาประมูลหมด → Seller กดปิดประมูล                     │
│                                                              │
│  เงื่อนไข:                                                   │
│  ├── ต้องเป็นเจ้าของสินค้า                                    │
│  ├── Product status = "active"                               │
│  └── auction_end_time ≤ now                                  │
│                                                              │
│  กรณีที่ 1: ไม่มีคน bid                                       │
│  └── Product status → "cancelled"                            │
│                                                              │
│  กรณีที่ 2: มีคนชนะ (DB Transaction)                          │
│  ┌───────────────────────────────────────────────────┐       │
│  │ 1. Winning bid status → "won"                      │       │
│  │ 2. คน bid อื่นๆ → "lost" + ส่ง notification        │       │
│  │ 3. สร้าง Order:                                    │       │
│  │    - status: "pending_buyer_confirm"                │       │
│  │    - confirm_deadline: now + 48 ชม.                 │       │
│  │    - final_price = winning bid price                │       │
│  │ 4. Product status → "completed"                     │       │
│  │ 5. แจ้งเตือน winner + seller                        │       │
│  └───────────────────────────────────────────────────┘       │
│                                                              │
│  * หมายเหตุ: เงินยังไม่โอน! ต้องรอ Buyer Confirm ก่อน        │
│    (เงิน bid ยังค้างอยู่ใน balance_pending ของ winner)         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 6: Post-Auction / Escrow Flow ⭐ หัวใจของระบบ

```
┌─────────────────────────────────────────────────────────────┐
│              Post-Auction Escrow Flow                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  *** Order Status Lifecycle ***                              │
│                                                              │
│  pending_buyer_confirm ──→ confirmed ──→ shipped ──→ completed
│        │ (48 ชม.)            │ (3 วัน)      │ (7 วัน)    │
│        │                     │              │             │
│        ▼                     │              ▼             │
│    (หมดเวลา=ยกเลิก)          │          disputed          │
│                              │              │             │
│                              │              ▼             │
│                              │     Admin ตัดสิน            │
│                              │     ├── resolved_buyer     │
│                              │     └── resolved_seller    │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Step 1: Buyer Confirm (ภายใน 48 ชม.)                        │
│  ├── ตรวจ UserStrike (ถ้าถูกแบนอยู่ → ปฏิเสธ)                │
│  ├── ตรวจยอดเงิน (ถ้าไม่พอ → cancel order)                   │
│  ├── หักเงินเข้า Escrow:                                     │
│  │   balance_available -= final_price                        │
│  │   balance_pending += final_price                          │
│  │   สร้าง transaction: "escrow_hold"                        │
│  ├── ship_deadline = now + 3 วัน                             │
│  └── Order status → "confirmed"                              │
│                                                              │
│  Step 2: Seller Ship (ภายใน 3 วัน)                           │
│  ├── Order status → "shipped"                                │
│  ├── receive_deadline = now + 7 วัน                          │
│  └── แจ้งเตือน Buyer: "สินค้าจัดส่งแล้ว"                      │
│                                                              │
│  Step 3a: Buyer Receive (ยืนยันรับสินค้า) ✅                  │
│  ├── ปล่อย Escrow → โอนเงินให้ Seller:                       │
│  │   buyer.balance_pending -= final_price                    │
│  │   buyer.balance_total -= final_price                      │
│  │   seller.balance_available += final_price                 │
│  │   seller.balance_total += final_price                     │
│  │   สร้าง transaction buyer: "escrow_release"               │
│  │   สร้าง transaction seller: "auction_sold"                │
│  ├── Order status → "completed"                              │
│  └── แจ้งเตือนทั้ง 2 ฝ่าย                                    │
│                                                              │
│  Step 3b: Buyer Dispute (มีปัญหา) ❌                         │
│  ├── สร้าง Report (type: "dispute", status: "open")          │
│  ├── แนบหลักฐาน (รูปภาพ ≤ 5 รูป)                             │
│  ├── Order status → "disputed"                               │
│  ├── เงินค้างอยู่ใน Escrow → รอ Admin ตัดสิน                  │
│  └── แจ้งเตือน Seller                                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 7: Report & Dispute (ระบบรวม)

```
┌─────────────────────────────────────────────────────────────┐
│              Unified Report & Dispute System                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  *** ใช้ตาราง reports ตารางเดียว แยกด้วย type ***             │
│                                                              │
│  ┌───────────────────────────────────────────────────┐       │
│  │  User Report (แจ้งปัญหาทั่วไป)                     │       │
│  │  ├── type: scam / fake_product / harassment /      │       │
│  │  │         inappropriate_content / other           │       │
│  │  ├── ระบุ: reported_user + reported_product        │       │
│  │  ├── รายละเอียด + หลักฐาน (รูป ≤ 5)                │       │
│  │  ├── ห้าม report ตัวเอง                            │       │
│  │  │                                                 │       │
│  │  │  Status Flow:                                   │       │
│  │  │  pending → reviewing → resolved / dismissed     │       │
│  │  │                                                 │       │
│  │  └── Report Code: RPT-001, RPT-002, ...            │       │
│  └───────────────────────────────────────────────────┘       │
│                                                              │
│  ┌───────────────────────────────────────────────────┐       │
│  │  Order Dispute (ข้อพิพาทจากการซื้อขาย)              │       │
│  │  ├── type: dispute                                 │       │
│  │  ├── สร้างจาก POST /orders/{id}/dispute             │       │
│  │  ├── ผูกกับ order_id + reported_user = seller       │       │
│  │  │                                                 │       │
│  │  │  Status Flow:                                   │       │
│  │  │  open → resolved_buyer (คืนเงิน)                │       │
│  │  │       → resolved_seller (โอนเงิน)               │       │
│  │  │                                                 │       │
│  │  └── Admin จัดการ Escrow ผ่าน PATCH /admin/reports  │       │
│  └───────────────────────────────────────────────────┘       │
│                                                              │
│  *** Features ร่วม ***                                       │
│  ├── Timeline: submitted → reviewing → resolved              │
│  ├── Admin Reply: ข้อความตอบกลับ + เวลา + ชื่อ admin          │
│  └── User ดูสถานะ + summary counts ได้ที่ Help & Support      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 8: Review (รีวิวผู้ขาย)

```
┌─────────────────────────────────────────────────────────────┐
│                       Review Flow                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  เงื่อนไข:                                                   │
│  ├── Order status = "completed" เท่านั้น                      │
│  ├── เฉพาะ Buyer เท่านั้นที่รีวิวได้                           │
│  └── รีวิวได้ 1 ครั้ง / order                                 │
│                                                              │
│  ข้อมูล:                                                     │
│  ├── rating: 1-5 ดาว (บังคับ)                                │
│  └── comment: ข้อความ (optional, max 1000 ตัวอักษร)           │
│                                                              │
│  แสดงผล (Public):                                            │
│  ├── average_rating (ปัดทศนิยม 1 ตำแหน่ง)                    │
│  ├── total_reviews                                           │
│  └── rating_breakdown: จำนวนแต่ละระดับ (5⭐ → 1⭐)            │
│                                                              │
│  ผลกระทบ:                                                    │
│  └── ข้อมูลรีวิวแสดงในหน้าสินค้า (seller rating + count)     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

### Flow 9: Admin Management

```
┌─────────────────────────────────────────────────────────────┐
│                    Admin Management Flow                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Dashboard:                                                  │
│  ├── total_users (role: user)                                │
│  ├── total_products                                          │
│  ├── total_orders                                            │
│  ├── active_auctions                                         │
│  ├── open_disputes (type: dispute, status: open)             │
│  ├── pending_reports (type != dispute, status: pending)      │
│  ├── pending_certificates                                    │
│  └── recent_orders (10 รายการล่าสุด)                         │
│                                                              │
│  Reports Management:                                         │
│  ├── ดู reports + disputes ทั้งหมด (?status=, ?type=)        │
│  ├── Report: เปลี่ยน status + ตอบกลับ admin_reply            │
│  └── Dispute: resolve + จัดการ Escrow                        │
│                                                              │
│  User Management:                                            │
│  ├── ค้นหา user (ชื่อ / email)                               │
│  ├── ดูรายละเอียด (wallet, strikes, counts)                  │
│  └── แบน user:                                               │
│      ├── สร้าง UserStrike (reason + ban_days)                │
│      ├── ห้ามแบน admin                                       │
│      └── แจ้งเตือน user ที่ถูกแบน                             │
│                                                              │
│  Certificate Management:                                     │
│  ├── ดู certificates (?status=pending)                       │
│  ├── ดาวน์โหลดไฟล์ certificate                               │
│  └── ตรวจสอบ:                                                │
│      ├── approved → สินค้าได้แท็ก "Certified"                 │
│      ├── rejected + admin_note                               │
│      └── แจ้งเตือนเจ้าของสินค้า                               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Wallet Money Flow (แผนผังการไหลของเงิน)

```
                        ┌──────────────┐
                        │   ธนาคาร      │
                        └──────┬───────┘
                               │
                    Topup ▼    ▲ Withdraw (min 100)
                               │
                ┌──────────────┴───────────────┐
                │        balance_available      │ ← เงินที่ใช้ได้
                └──────────────┬───────────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
         Bid (hold)    Confirm (escrow)    Buy Now (จ่ายตรง)
              │                │                │
              ▼                ▼                ▼
     ┌────────────┐   ┌────────────┐   ┌────────────────┐
     │ balance_   │   │ balance_   │   │ Seller ได้เงิน  │
     │ pending    │   │ pending    │   │ ทันที           │
     │ (bid hold) │   │ (escrow)   │   └────────────────┘
     └─────┬──────┘   └─────┬──────┘
           │                │
     ┌─────┼──────┐   ┌─────┼──────────────┐
     │     │      │   │     │              │
  Outbid  Won   Lost  Receive          Dispute
     │     │      │   │                    │
     ▼     ▼      ▼   ▼                    ▼
   คืนเงิน  │   คืนเงิน  โอนให้ Seller    Admin ตัดสิน
  (refund)  │  (refund)                  ├── → คืน Buyer
            │                            └── → โอน Seller
            ▼
     เงิน bid ค้าง
     → รอ Confirm
```

### Transaction Types ทั้งหมด

| Type | เหตุการณ์ | Amount | ใครได้ |
|------|----------|--------|--------|
| `topup` | เติมเงิน | + | User |
| `withdraw` | ถอนเงิน | - | User |
| `bid_placed` | วางเงินประมูล | - | Buyer (hold) |
| `bid_refund` | คืนเงินคนแพ้ bid | + | Buyer (refund) |
| `auction_won` | ชนะ Buy Now | - | Buyer |
| `auction_sold` | ขายได้ (Buy Now / Dispute resolved) | + | Seller |
| `escrow_hold` | Buyer confirm, หักเงินเข้า escrow | - | Buyer (hold) |
| `escrow_release` | ปล่อย escrow ให้ seller | - | Buyer (ตัดออก) |
| `escrow_refund` | คืนเงิน escrow (dispute) | + | Buyer (refund) |

---

## 4. Order Status Lifecycle (วงจร Order)

```
                    ┌──────────────────────┐
                    │ pending_buyer_confirm │ ← สร้างจาก Close Auction / Buy Now
                    │   (deadline: 48 ชม.)  │
                    └──────────┬───────────┘
                               │ Buyer กด Confirm
                               │ (หักเงินเข้า Escrow)
                               ▼
                    ┌──────────────────────┐
                    │      confirmed       │
                    │   (deadline: 3 วัน)   │
                    └──────────┬───────────┘
                               │ Seller กด Ship
                               ▼
                    ┌──────────────────────┐
                    │       shipped        │
                    │   (deadline: 7 วัน)   │
                    └──────────┬───────────┘
                               │
                    ┌──────────┼──────────┐
                    │                     │
                    ▼                     ▼
         ┌──────────────┐      ┌──────────────┐
         │  completed   │      │   disputed   │
         │ (โอนเงิน     │      │ (เงินค้าง    │
         │  ให้ Seller)  │      │  ใน Escrow)  │
         └──────────────┘      └──────┬───────┘
                                      │ Admin ตัดสิน
                               ┌──────┼──────┐
                               │             │
                               ▼             ▼
                        ┌───────────┐ ┌───────────┐
                        │ cancelled │ │ completed │
                        │ (คืนเงิน  │ │ (โอนเงิน  │
                        │  Buyer)   │ │  Seller)  │
                        └───────────┘ └───────────┘
```

---

## 5. Notification System (ระบบแจ้งเตือน)

| เหตุการณ์ | ผู้รับ | Type | ข้อความ |
|-----------|--------|------|---------|
| มีคนเสนอราคาสูงกว่า | Bidder เดิม | bid | "มีคนเสนอราคาสูงกว่าคุณ" |
| แพ้ประมูล | Bidders ที่แพ้ | bid | "สินค้าถูกขายแล้ว" |
| ชนะประมูล | Winner | bid | "คุณชนะการประมูล!" |
| สินค้าขายแล้ว | Seller | order | "สินค้าของคุณถูกขายแล้ว" |
| Buyer confirm | Seller | order | "ผู้ซื้อยืนยันแล้ว กรุณาจัดส่ง" |
| Seller ship | Buyer | order | "สินค้าจัดส่งแล้ว" |
| ยืนยันรับสินค้า | ทั้ง 2 ฝ่าย | order | "ดำเนินการเสร็จสิ้น" |
| Dispute สร้าง | Seller | order | "มีการเปิด dispute" |
| Dispute resolved | ทั้ง 2 ฝ่าย | order | "Dispute ตัดสินแล้ว" |
| Report อัปเดต | Reporter | system | "Report ของคุณถูกอัปเดต" |
| ถูกแบน | User | system | "บัญชีถูกระงับ X วัน" |
| Certificate verified | เจ้าของสินค้า | system | "Certificate ผ่าน/ไม่ผ่าน" |

---

## 6. API Endpoints Summary (53 routes)

### Public Routes (ไม่ต้อง Login)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/register` | สมัครสมาชิก |
| POST | `/login` | เข้าสู่ระบบ |
| POST | `/forgot-password` | ขอ OTP reset password |
| POST | `/reset-password` | Reset password ด้วย OTP |
| GET | `/products` | ดูสินค้าทั้งหมด (filter/search/sort) |
| GET | `/products/{id}` | ดูรายละเอียดสินค้า |
| GET | `/categories` | ดูหมวดหมู่ทั้งหมด |
| GET | `/categories/{id}` | ดูหมวดหมู่ + subcategories |
| GET | `/subcategories` | ดู subcategories ทั้งหมด |
| GET | `/users/{id}/reviews` | ดูรีวิวผู้ขาย |

### Protected Routes (ต้อง Login)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/logout` | ออกจากระบบ |
| GET | `/me` | ดูข้อมูลตัวเอง |
| PATCH | `/profile` | แก้ไขโปรไฟล์ |
| POST | `/profile/image` | อัปโหลดรูปโปรไฟล์ |
| POST | `/change-password` | เปลี่ยนรหัสผ่าน |
| POST | `/wallet/topup` | เติมเงิน |
| POST | `/wallet/withdraw` | ถอนเงิน |
| GET | `/wallet/transactions` | ดูประวัติธุรกรรม |
| POST | `/products` | สร้างสินค้าใหม่ |
| DELETE | `/products/{id}` | ลบสินค้า |
| DELETE | `/products/{id}/images/{imageId}` | ลบรูปสินค้า |
| POST | `/products/{id}/bid` | เสนอราคาประมูล |
| POST | `/products/{id}/buy-now` | ซื้อทันที |
| GET | `/products/{id}/bids` | ดูประวัติ bid ของสินค้า |
| GET | `/users/me/bids` | ดู bid ของตัวเอง |
| GET | `/users/me/orders` | ดู orders ของตัวเอง |
| POST | `/products/{id}/close` | ปิดประมูล |
| POST | `/orders/{id}/confirm` | ยืนยัน order |
| GET | `/orders/{id}/detail` | ดูรายละเอียด order |
| POST | `/orders/{id}/ship` | แจ้งจัดส่ง |
| POST | `/orders/{id}/receive` | ยืนยันรับสินค้า |
| POST | `/orders/{id}/dispute` | เปิด dispute |
| GET | `/notifications` | ดูแจ้งเตือนทั้งหมด |
| GET | `/notifications/unread` | ดูแจ้งเตือนที่ยังไม่อ่าน |
| PATCH | `/notifications/read-all` | อ่านทั้งหมด |
| PATCH | `/notifications/{id}/read` | อ่านรายการเดียว |
| POST | `/reports` | สร้าง report |
| GET | `/reports` | ดู reports ตัวเอง + summary |
| GET | `/reports/{id}` | ดูรายละเอียด report |
| POST | `/orders/{id}/review` | รีวิวผู้ขาย |

### Admin Routes (ต้อง Login + Admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/dashboard` | สถิติรวม |
| GET | `/admin/reports` | ดู reports + disputes |
| PATCH | `/admin/reports/{id}` | อัปเดต report / resolve dispute |
| GET | `/admin/users` | ดู users ทั้งหมด |
| GET | `/admin/users/{id}` | ดูรายละเอียด user |
| POST | `/admin/users/{id}/ban` | แบน user |
| GET | `/admin/certificates` | ดู certificates |
| GET | `/admin/certificates/{id}` | ดูไฟล์ certificate |
| PATCH | `/admin/certificates/{id}/verify` | ตรวจสอบ certificate |

---

## 7. Rate Limiting

| กลุ่ม Route | Limit | ตัวอย่าง |
|-------------|-------|---------|
| Auth (register/login) | 10 req/min | ป้องกัน brute force |
| Public (products/categories) | 60 req/min | ดูสินค้าทั่วไป |
| Protected (ต้อง login) | 100 req/min | ทุก action ที่ต้อง login |
| Admin | 100 req/min | จัดการระบบ |

---

## 8. Security & Protection

```
Authentication:  Laravel Sanctum (Token-based)
Authorization:   Middleware auth:sanctum + admin
Ban System:      UserStrike (reason + banned_until)
                 → ตรวจก่อน Confirm order
Rate Limiting:   throttle middleware ทุก route group
Input Validation: FormRequest validate ทุก endpoint
File Upload:     จำกัด size (5MB รูป / 10MB cert)
                 จำกัดประเภท (jpeg/png/jpg/gif/webp)
Self-Protection: ห้าม bid สินค้าตัวเอง
                 ห้าม report ตัวเอง
                 ห้ามแบน admin
```

---

## 9. Full Business Flow Diagram (ภาพรวมทั้งหมด)

```
  ┌─────────┐     ┌─────────┐
  │ Register │────→│  Login  │
  └─────────┘     └────┬────┘
                       │
              ┌────────┼────────┐
              │        │        │
              ▼        ▼        ▼
         ┌────────┐ ┌──────┐ ┌───────┐
         │ Topup  │ │ ดู    │ │ สร้าง  │
         │ Wallet │ │ สินค้า │ │ สินค้า │
         └───┬────┘ └──┬───┘ └───┬───┘
             │         │         │
             │         ▼         │
             │    ┌──────────┐   │
             └───→│ ประมูล/   │←──┘
                  │ Buy Now  │
                  └────┬─────┘
                       │
                       ▼
                 ┌───────────┐
                 │ ปิดประมูล  │
                 │ (Seller)  │
                 └─────┬─────┘
                       │
                       ▼
              ┌────────────────┐
              │  Post-Auction  │
              │   Escrow Flow  │
              │                │
              │ Confirm → Ship │
              │   → Receive    │
              │   → Dispute    │
              └────────┬───────┘
                       │
              ┌────────┼────────┐
              │        │        │
              ▼        ▼        ▼
         ┌────────┐ ┌──────┐ ┌───────┐
         │ Review │ │Report│ │Withdraw│
         │ Seller │ │      │ │ เงิน   │
         └────────┘ └──────┘ └───────┘
```
