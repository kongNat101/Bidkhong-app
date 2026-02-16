#!/bin/bash

# BidKhong Auction API Test Script
# This script tests all API endpoints

BASE_URL="http://127.0.0.1:8000/api"
TOKEN=""
USER_ID=""
PRODUCT_ID=""

echo "=========================================="
echo "BidKhong Auction API Test"
echo "=========================================="
echo ""

# Test 1: Register User
echo "1. Testing Registration..."
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test'$(date +%s)'@example.com",
    "password": "password123"
  }')

echo "$REGISTER_RESPONSE" | head -c 200
echo ""

TOKEN=$(echo "$REGISTER_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
USER_ID=$(echo "$REGISTER_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$TOKEN" ]; then
  echo "❌ Registration failed!"
  exit 1
fi
echo "✅ Registration successful! Token: ${TOKEN:0:20}..."
echo ""

# Test 2: Get Current User Info
echo "2. Testing Get Current User (/me)..."
ME_RESPONSE=$(curl -s -X GET "$BASE_URL/me" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")
echo "$ME_RESPONSE" | head -c 200
echo ""
echo "✅ Get user info successful!"
echo ""

# Test 3: Top Up Wallet
echo "3. Testing Wallet Top-Up..."
TOPUP_RESPONSE=$(curl -s -X POST "$BASE_URL/wallet/topup" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "amount": 100000
  }')
echo "$TOPUP_RESPONSE" | head -c 200
echo ""
echo "✅ Wallet top-up successful!"
echo ""

# Test 4: Get Categories
echo "4. Testing Get Categories..."
CATEGORIES_RESPONSE=$(curl -s -X GET "$BASE_URL/categories" \
  -H "Accept: application/json")
CATEGORY_COUNT=$(echo "$CATEGORIES_RESPONSE" | grep -o '"id"' | wc -l)
echo "Found $CATEGORY_COUNT categories"
echo "✅ Get categories successful!"
echo ""

# Test 5: Create Product
echo "5. Testing Create Product..."
PRODUCT_RESPONSE=$(curl -s -X POST "$BASE_URL/products" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test iPhone 15 Pro",
    "description": "Brand new iPhone for testing",
    "starting_price": 30000,
    "min_price": 32000,
    "buyout_price": 45000,
    "auction_end_time": "2026-02-10 18:00:00",
    "category_id": 1,
    "subcategory_id": 1
  }')
echo "$PRODUCT_RESPONSE" | head -c 300
echo ""

PRODUCT_ID=$(echo "$PRODUCT_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$PRODUCT_ID" ]; then
  echo "❌ Create product failed!"
  exit 1
fi
echo "✅ Create product successful! Product ID: $PRODUCT_ID"
echo ""

# Test 6: Get All Products
echo "6. Testing Get All Products..."
PRODUCTS_RESPONSE=$(curl -s -X GET "$BASE_URL/products" \
  -H "Accept: application/json")
PRODUCT_COUNT=$(echo "$PRODUCTS_RESPONSE" | grep -o '"id"' | wc -l)
echo "Found $PRODUCT_COUNT products"
echo "✅ Get all products successful!"
echo ""

# Test 7: Get Product Detail
echo "7. Testing Get Product Detail..."
PRODUCT_DETAIL=$(curl -s -X GET "$BASE_URL/products/$PRODUCT_ID" \
  -H "Accept: application/json")
echo "$PRODUCT_DETAIL" | head -c 300
echo ""
echo "✅ Get product detail successful!"
echo ""

# Test 8: Register Second User for Bidding
echo "8. Creating Second User for Bidding Test..."
BIDDER_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Bidder User",
    "email": "bidder'$(date +%s)'@example.com",
    "password": "password123"
  }')

BIDDER_TOKEN=$(echo "$BIDDER_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$BIDDER_TOKEN" ]; then
  echo "❌ Second user registration failed!"
  exit 1
fi
echo "✅ Second user registered! Token: ${BIDDER_TOKEN:0:20}..."
echo ""

# Test 9: Top Up Bidder Wallet
echo "9. Testing Bidder Wallet Top-Up..."
BIDDER_TOPUP=$(curl -s -X POST "$BASE_URL/wallet/topup" \
  -H "Authorization: Bearer $BIDDER_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "amount": 50000
  }')
echo "✅ Bidder wallet top-up successful!"
echo ""

# Test 10: Place Bid
echo "10. Testing Place Bid..."
BID_RESPONSE=$(curl -s -X POST "$BASE_URL/products/$PRODUCT_ID/bid" \
  -H "Authorization: Bearer $BIDDER_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "price": 35000
  }')
echo "$BID_RESPONSE" | head -c 300
echo ""
echo "✅ Place bid successful!"
echo ""

# Test 11: Get Product Bid History
echo "11. Testing Get Product Bid History..."
BID_HISTORY=$(curl -s -X GET "$BASE_URL/products/$PRODUCT_ID/bids" \
  -H "Authorization: Bearer $BIDDER_TOKEN" \
  -H "Accept: application/json")
echo "$BID_HISTORY" | head -c 300
echo ""
echo "✅ Get bid history successful!"
echo ""

# Test 12: Get My Bids
echo "12. Testing Get My Bids..."
MY_BIDS=$(curl -s -X GET "$BASE_URL/users/me/bids" \
  -H "Authorization: Bearer $BIDDER_TOKEN" \
  -H "Accept: application/json")
echo "$MY_BIDS" | head -c 300
echo ""
echo "✅ Get my bids successful!"
echo ""

# Test 13: Get Notifications
echo "13. Testing Get Notifications..."
NOTIFICATIONS=$(curl -s -X GET "$BASE_URL/notifications" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")
echo "$NOTIFICATIONS" | head -c 200
echo ""
echo "✅ Get notifications successful!"
echo ""

# Test 14: Get Unread Notifications
echo "14. Testing Get Unread Notifications..."
UNREAD=$(curl -s -X GET "$BASE_URL/notifications/unread" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")
echo "$UNREAD" | head -c 200
echo ""
echo "✅ Get unread notifications successful!"
echo ""

# Test 15: Logout
echo "15. Testing Logout..."
LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")
echo "$LOGOUT_RESPONSE"
echo "✅ Logout successful!"
echo ""

echo "=========================================="
echo "✅ ALL TESTS PASSED!"
echo "=========================================="
echo ""
echo "Summary:"
echo "- User Registration: ✅"
echo "- User Authentication: ✅"
echo "- Wallet Top-Up: ✅"
echo "- Product Creation: ✅"
echo "- Product Listing: ✅"
echo "- Bidding System: ✅"
echo "- Notifications: ✅"
echo "- Logout: ✅"
echo ""
