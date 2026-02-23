# BidKhong - Chen Notation ER Diagram (สำหรับวาดใน Figma)

## สัญลักษณ์ที่ใช้

| รูปทรง | สี/ขอบ | ความหมาย |
|--------|--------|----------|
| ⬭ วงรี | ขอบแดง | PK (Primary Key) |
| ⬭ วงรี | ขอบฟ้า | FK (Foreign Key) |
| ⬭ วงรี | ขอบดำ/ขาว | Attribute ปกติ |
| ▭ สี่เหลี่ยม | - | Entity (ตาราง) |
| ◇ ข้าวหลามตัด | - | Relationship |
| เส้น + ตัวเลข | 1, M, N | Cardinality |

---

## Entity ทั้งหมด (17 ตาราง)

---

### 1. User (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| u_id | 🔴 PK | |
| u_name | ⚪ ปกติ | |
| u_password | ⚪ ปกติ | |
| u_email | ⚪ ปกติ | UNIQUE |
| phone_number | ⚪ ปกติ | UNIQUE |
| u_role | ⚪ ปกติ | user / admin |
| profile_image | ⚪ ปกติ | |

---

### 2. Wallet (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| w_id | 🔴 PK | |
| u_id | 🔵 FK | → User |
| balance_available | ⚪ ปกติ | |
| balance_total | ⚪ ปกติ | |
| balance_pending | ⚪ ปกติ | |
| withdraw | ⚪ ปกติ | |
| deposit | ⚪ ปกติ | |

---

### 3. Wallet_Transaction (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| wt_id | 🔴 PK | |
| u_id | 🔵 FK | → User |
| w_id | 🔵 FK | → Wallet |
| wt_type | ⚪ ปกติ | topup / withdraw / bid_placed / bid_refund / auction_won / auction_sold / escrow_hold / escrow_release / escrow_refund |
| wt_amount | ⚪ ปกติ | |
| wt_description | ⚪ ปกติ | |
| reference_type | ⚪ ปกติ | |
| reference_id | ⚪ ปกติ | |
| balance_after | ⚪ ปกติ | |

---

### 4. User_Strike (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| us_id | 🔴 PK | |
| u_id | 🔵 FK | → User |
| o_id | 🔵 FK | → Order (nullable) |
| us_reason | ⚪ ปกติ | |
| banned_until | ⚪ ปกติ | |

---

### 5. Category (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| c_id | 🔴 PK | |
| c_name | ⚪ ปกติ | |

---

### 6. Subcategory (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| sc_id | 🔴 PK | |
| c_id | 🔵 FK | → Category |
| sc_name | ⚪ ปกติ | |

---

### 7. Products (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| p_id | 🔴 PK | |
| u_id | 🔵 FK | → User (seller) |
| p_category | 🔵 FK | → Category |
| p_subcategory | 🔵 FK | → Subcategory |
| p_name | ⚪ ปกติ | |
| p_description | ⚪ ปกติ | |
| p_location | ⚪ ปกติ | |
| p_picture | ⚪ ปกติ | |
| p_start | ⚪ ปกติ | starting_price |
| p_min | ⚪ ปกติ | bid_increment |
| p_buyout | ⚪ ปกติ | buyout_price |
| p_remain | ⚪ ปกติ | current_price |
| p_status | ⚪ ปกติ | active / completed / cancelled |
| p_ending | ⚪ ปกติ | auction_end_time |
| p_start_time | ⚪ ปกติ | auction_start_time |

---

### 8. Product_Image (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| pi_id | 🔴 PK | |
| p_id | 🔵 FK | → Products |
| pi_url | ⚪ ปกติ | image_url |
| pi_sort | ⚪ ปกติ | sort_order |

---

### 9. Product_Certificate (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| pc_id | 🔴 PK | |
| p_id | 🔵 FK | → Products |
| pc_file | ⚪ ปกติ | file_path |
| pc_name | ⚪ ปกติ | original_name |
| pc_status | ⚪ ปกติ | pending / approved / rejected |
| pc_note | ⚪ ปกติ | admin_note |
| u_id | 🔵 FK | → User (verified_by) |
| pc_verified_at | ⚪ ปกติ | |

---

### 10. Bid (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| b_id | 🔴 PK | |
| u_id | 🔵 FK | → User (bidder) |
| p_id | 🔵 FK | → Products |
| b_price | ⚪ ปกติ | |
| b_time | ⚪ ปกติ | |
| b_status | ⚪ ปกติ | active / outbid / won / lost |

---

### 11. Order (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| o_id | 🔴 PK | |
| u_id | 🔵 FK | → User (buyer) |
| s_id | 🔵 FK | → User (seller) |
| p_id | 🔵 FK | → Products |
| o_price | ⚪ ปกติ | final_price |
| o_status | ⚪ ปกติ | pending_confirm / pending_buyer_confirm / confirmed / shipped / completed / disputed / cancelled |
| o_confirmed | ⚪ ปกติ | buyer_confirmed_at |
| o_shipped | ⚪ ปกติ | shipped_at |
| o_received | ⚪ ปกติ | received_at |
| o_confirm_deadline | ⚪ ปกติ | |
| o_ship_deadline | ⚪ ปกติ | |
| o_receive_deadline | ⚪ ปกติ | |

---

### 12. Order_Confirmation (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| oc_id | 🔴 PK | |
| o_id | 🔵 FK | → Order |
| u_id | 🔵 FK | → User |
| oc_role | ⚪ ปกติ | buyer / seller |
| oc_phone | ⚪ ปกติ | |
| oc_line | ⚪ ปกติ | LINE ID |
| oc_facebook | ⚪ ปกติ | |
| oc_note | ⚪ ปกติ | |

---

### 13. Report (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| r_id | 🔴 PK | |
| reporter_id | 🔵 FK | → User (ผู้แจ้ง) |
| reported_user_id | 🔵 FK | → User (ผู้ถูกแจ้ง) |
| reported_product_id | 🔵 FK | → Products |
| o_id | 🔵 FK | → Order (สำหรับ dispute) |
| r_type | ⚪ ปกติ | scam / fake_product / harassment / inappropriate_content / other / dispute |
| r_description | ⚪ ปกติ | |
| r_evidence | ⚪ ปกติ | evidence_images (JSON) |
| r_status | ⚪ ปกติ | pending / reviewing / resolved / dismissed / open / resolved_buyer / resolved_seller |
| r_admin_note | ⚪ ปกติ | |
| r_resolved_at | ⚪ ปกติ | |
| r_reviewing_at | ⚪ ปกติ | |
| r_admin_reply | ⚪ ปกติ | |
| r_admin_reply_at | ⚪ ปกติ | |
| admin_reply_by | 🔵 FK | → User (admin ที่ตอบ) |

---

### 14. Review (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| rv_id | 🔴 PK | |
| o_id | 🔵 FK | → Order (UNIQUE) |
| reviewer_id | 🔵 FK | → User (buyer) |
| seller_id | 🔵 FK | → User (seller) |
| rv_rating | ⚪ ปกติ | 1-5 |
| rv_comment | ⚪ ปกติ | |

---

### 15. Notification (สี่เหลี่ยม)

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| n_id | 🔴 PK | |
| u_id | 🔵 FK | → User |
| n_type | ⚪ ปกติ | outbid / won / lost / sold / new_bid / order / system |
| n_title | ⚪ ปกติ | |
| n_message | ⚪ ปกติ | |
| p_id | 🔵 FK | → Products |
| n_read | ⚪ ปกติ | is_read (boolean) |

---

### 16. Personal_Access_Token (สี่เหลี่ยม) [System]

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| pat_id | 🔴 PK | |
| tokenable_type | ⚪ ปกติ | |
| tokenable_id | ⚪ ปกติ | |
| pat_name | ⚪ ปกติ | |
| pat_token | ⚪ ปกติ | UNIQUE |
| pat_abilities | ⚪ ปกติ | |
| last_used_at | ⚪ ปกติ | |
| expires_at | ⚪ ปกติ | |

---

### 17. Password_Reset_Token (สี่เหลี่ยม) [System]

| Attribute | สี | หมายเหตุ |
|-----------|-----|----------|
| prt_email | 🔴 PK | |
| prt_token | ⚪ ปกติ | |

---

---

## Relationships (ข้าวหลามตัด ◇) + Cardinality

### สัญลักษณ์ Cardinality
- **1** = หนึ่งตัว
- **M** = หลายตัว (Many)
- **N** = หลายตัว (Many) ใช้คู่กับ M

---

### User เป็นศูนย์กลาง

| Entity A | Cardinality | ◇ Relationship | Cardinality | Entity B |
|----------|:-----------:|:--------------:|:-----------:|----------|
| User | 1 | ◇ Has | 1 | Wallet |
| User | 1 | ◇ Makes | M | Wallet_Transaction |
| User | 1 | ◇ Receives | M | User_Strike |
| User | 1 | ◇ Add | M | Products |
| User | 1 | ◇ Auction | M | Bid |
| User | 1 | ◇ Buys | M | Order (as buyer) |
| User | 1 | ◇ Sells | M | Order (as seller) |
| User | 1 | ◇ Provides | M | Order_Confirmation |
| User | 1 | ◇ Files | M | Report (as reporter) |
| User | 1 | ◇ Writes | M | Review (as reviewer) |
| User | 1 | ◇ Gets | M | Notification |
| User | 1 | ◇ Verifies | M | Product_Certificate |
| User | 1 | ◇ Replies | M | Report (as admin) |

---

### Products เป็นศูนย์กลาง

| Entity A | Cardinality | ◇ Relationship | Cardinality | Entity B |
|----------|:-----------:|:--------------:|:-----------:|----------|
| Products | 1 | ◇ Has | M | Product_Image |
| Products | 1 | ◇ Has | 1 | Product_Certificate |
| Products | 1 | ◇ Receives | M | Bid |
| Products | 1 | ◇ Creates | M | Order |
| Products | 1 | ◇ Reported | M | Report |
| Products | 1 | ◇ Triggers | M | Notification |

---

### Category & Subcategory

| Entity A | Cardinality | ◇ Relationship | Cardinality | Entity B |
|----------|:-----------:|:--------------:|:-----------:|----------|
| Category | 1 | ◇ Has | M | Subcategory |
| Category | 1 | ◇ Contains | M | Products |
| Subcategory | 1 | ◇ Contains | M | Products |

---

### Wallet

| Entity A | Cardinality | ◇ Relationship | Cardinality | Entity B |
|----------|:-----------:|:--------------:|:-----------:|----------|
| Wallet | 1 | ◇ Records | M | Wallet_Transaction |

---

### Order เป็นศูนย์กลาง

| Entity A | Cardinality | ◇ Relationship | Cardinality | Entity B |
|----------|:-----------:|:--------------:|:-----------:|----------|
| Order | 1 | ◇ Has | M | Order_Confirmation |
| Order | 1 | ◇ Has | 1 | Review |
| Order | 1 | ◇ Has | M | Report (dispute) |
| Order | 1 | ◇ Causes | M | User_Strike |

---

### Bid ↔ Products ↔ Order (Auction Flow)

| Entity A | Cardinality | ◇ Relationship | Cardinality | Entity B |
|----------|:-----------:|:--------------:|:-----------:|----------|
| Bid | M | ◇ Auction | 1 | Products |
| Products | 1 | ◇ Choose | 1 | Order |

---

## Layout Guide (แนะนำการจัดวาง Figma)

```
                    ┌─────────────┐
                    │   Wallet    │
                    └──────┬──────┘
                      Has(1:1)
                           │
┌────────────┐    ┌────────┴───────┐    ┌──────────────────┐
│ Wallet_    │◄───│     User       │───►│  User_Strike     │
│ Transaction│    └───┬──┬──┬──┬───┘    └──────────────────┘
└────────────┘        │  │  │  │
          Add(1:M)────┘  │  │  └────Gets(1:M)──►┌──────────────┐
                         │  │                    │ Notification │
              Auction    │  │                    └──────────────┘
              (1:M)      │  │
                  ┌──────┘  └──────┐
                  │                │
           ┌──────┴──────┐  ┌─────┴─────┐
           │    Bid      │  │   Order   │
           └──────┬──────┘  └──┬──┬──┬──┘
                  │            │  │  │
           Auction(M:1)   Has  │  │  │ Has(1:1)
                  │       (1:M)│  │  │
           ┌──────┴──────────┐│  │  ┌┴──────────┐
           │                 ││  │  │  Review    │
           │   Products      ││  │  └───────────┘
           │                 ││  │
           └─┬──┬──┬──┬──┬──┘│  └──Has(1:M)──►┌───────────────────┐
             │  │  │  │  │   │                 │Order_Confirmation │
             │  │  │  │  │   │                 └───────────────────┘
             │  │  │  │  │   │
             │  │  │  │  │   └──Has(1:M)──►┌──────────┐
             │  │  │  │  │                 │  Report  │
             │  │  │  │  │                 └──────────┘
             │  │  │  │  │
         ┌───┘  │  │  │  └───┐
         │      │  │  │      │
    ┌────┴───┐  │  │  │  ┌───┴────────────┐
    │Product │  │  │  │  │Product         │
    │_Image  │  │  │  │  │_Certificate    │
    └────────┘  │  │  │  └────────────────┘
                │  │  │
           ┌────┘  │  └────┐
           │       │       │
      ┌────┴────┐  │  ┌────┴────────┐
      │Category │  │  │Subcategory  │
      └─────────┘  │  └─────────────┘
                   │
            ┌──────┘
            │ (System)
    ┌───────┴───────────┐
    │  Session          │
    │  Personal_Access  │
    │  Password_Reset   │
    └───────────────────┘
```

---

## สรุปจำนวน

| หมวด | จำนวน Entity | จำนวน Relationship (◇) |
|------|:-----------:|:---------------------:|
| User & Wallet | 4 | 4 |
| Product & Catalog | 5 | 5 |
| Auction & Order | 3 | 4 |
| Support & Admin | 3 | 5 |
| System | 3 | 1 |
| **รวม** | **18** | **~19** |
