<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostAuctionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchHistoryController;
use App\Http\Controllers\ProductWatchController;

// Auth Routes (ไม่ต้อง login) - Rate limited to 10 requests per minute
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class , 'register']);
    Route::post('/login', [AuthController::class , 'login']);
    Route::post('/forgot-password', [AuthController::class , 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class , 'resetPassword']);
});


// Public Routes - Rate limited to 60 requests per minute
Route::middleware('throttle:60,1')->group(function () {
    // Products
    Route::get('/products', [ProductController::class , 'index']);
    Route::get('/products/{id}', [ProductController::class , 'show']);

    // Categories
    Route::get('/categories', [CategoryController::class , 'index']);
    Route::get('/categories/{id}', [CategoryController::class , 'show']);
    Route::get('/subcategories', [CategoryController::class , 'subcategories']);

    // Seller Ratings (public)
    Route::get('/users/{id}/ratings', [ReviewController::class , 'getSellerRatings']);

    // Recommendations (similar items - public)
    Route::get('/products/{id}/similar', [RecommendationController::class , 'similar']);

    // Public Stats (สำหรับ welcome page)
    Route::get('/public/stats', function () {
        // เช็คว่า AI Recommendation พร้อมใช้งานหรือยัง
        $recommendationReady = false;
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->get(config('services.recommendation.base_url') . '/health');
            $recommendationReady = $response->json('model_loaded') ?? false;
        } catch (\Exception $e) {}

        return response()->json([
            'total_users' => \App\Models\User::count(),
            'active_auctions' => \App\Models\Product::where('status', 'active')
                ->where('auction_end_time', '>', now())
                ->count(),
            'recommendation_ready' => $recommendationReady,
        ]);
    });
});

// Protected Routes (ต้อง login + เช็คแบน) - Rate limited to 100 requests per minute
Route::middleware(['auth:sanctum', 'check-banned', 'throttle:100,1'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class , 'logout']);
    Route::get('/me', [AuthController::class , 'me']);
    Route::post('/profile/image', [AuthController::class , 'updateProfileImage']);
    Route::patch('/profile', [AuthController::class , 'updateProfile']);
    Route::post('/change-password', [AuthController::class , 'changePassword']);

    // Wallet
    Route::get('/wallet', [AuthController::class , 'getWallet']);
    Route::post('/wallet/topup', [AuthController::class , 'topup']);
    Route::post('/wallet/withdraw', [AuthController::class , 'withdraw']);
    Route::get('/wallet/transactions', [AuthController::class , 'getTransactions']);

    // Products
    Route::post('/products', [ProductController::class , 'store']);
    Route::delete('/products/{id}', [ProductController::class , 'destroy']);
    Route::delete('/products/{id}/images/{imageId}', [ProductController::class , 'deleteImage']);

    // Bidding
    Route::post('/products/{id}/bid', [BidController::class , 'bid']);
    Route::post('/products/{id}/buy-now', [BidController::class , 'buyNow']);
    Route::get('/products/{id}/bids', [BidController::class , 'getProductBids']);
    Route::get('/users/me/bids', [BidController::class , 'getUserBids']);
    Route::get('/users/me/products', [ProductController::class , 'myProducts']);

    // Orders
    Route::get('/users/me/orders', [OrderController::class , 'myOrders']);
    Route::post('/products/{id}/close', [OrderController::class , 'closeAuction']);
    // Post-Auction (confirm → ship → receive → dispute)
    Route::post('/orders/{id}/confirm', [PostAuctionController::class , 'confirm']);
    Route::get('/orders/{id}/detail', [PostAuctionController::class , 'detail']);
    Route::post('/orders/{id}/ship', [PostAuctionController::class , 'ship']);
    Route::post('/orders/{id}/receive', [PostAuctionController::class , 'receive']);

    // Notifications
    Route::get('/notifications', [NotificationController::class , 'index']);
    Route::get('/notifications/unread', [NotificationController::class , 'unread']);
    Route::patch('/notifications/read-all', [NotificationController::class , 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class , 'markAsRead']);

    // Reports
    Route::post('/reports', [ReportController::class , 'store']);
    Route::get('/reports', [ReportController::class , 'index']);
    Route::get('/reports/{id}', [ReportController::class , 'show']);

    // Search History
    Route::get('/search-history', [SearchHistoryController::class , 'index']);
    Route::post('/search-history', [SearchHistoryController::class , 'store']);
    Route::delete('/search-history', [SearchHistoryController::class , 'clearAll']);
    Route::delete('/search-history/{id}', [SearchHistoryController::class , 'destroy']);

    // Rate Seller
    Route::post('/users/{id}/rate', [ReviewController::class , 'rate']);

    // Product Watch (ติดตามสินค้า)
    Route::post('/products/{id}/watch', [ProductWatchController::class , 'toggle']);
    Route::get('/users/me/watchlist', [ProductWatchController::class , 'watchlist']);

    // Recommendations (personalized - ต้อง login)
    Route::get('/recommendations', [RecommendationController::class , 'forUser']);
});

// Admin Routes (ต้อง login + เป็น admin) - Rate limited to 100 requests per minute
Route::middleware(['auth:sanctum', 'admin', 'throttle:100,1'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class , 'dashboard']);

    // Product Approval (อนุมัติ/ปฏิเสธสินค้า)
    Route::get('/products', [AdminController::class , 'pendingProducts']);
    Route::patch('/products/{id}/approve', [AdminController::class , 'approveProduct']);
    Route::patch('/products/{id}/reject', [AdminController::class , 'rejectProduct']);

    // Reports + Disputes (รวมเป็นระบบเดียว)
    Route::get('/reports', [AdminController::class , 'reports']);
    Route::patch('/reports/{id}', [AdminController::class , 'updateReport']);

    // Users
    Route::get('/users', [AdminController::class , 'users']);
    Route::get('/users/{id}', [AdminController::class , 'userDetail']);
    Route::post('/users/{id}/ban', [AdminController::class , 'banUser']);
    Route::post('/users/{id}/unban', [AdminController::class , 'unbanUser']);

    // Withdrawals (จัดการการถอนเงิน)
    Route::get('/withdrawals', [AdminController::class , 'withdrawals']);
    Route::patch('/withdrawals/{id}/confirm', [AdminController::class , 'confirmWithdrawal']);
    Route::patch('/withdrawals/{id}/reject', [AdminController::class , 'rejectWithdrawal']);

    // Certificates
    Route::get('/certificates', [AdminController::class , 'certificates']);
    Route::get('/certificates/{id}', [AdminController::class , 'viewCertificate']);
    Route::patch('/certificates/{id}/verify', [AdminController::class , 'verifyCertificate']);
});