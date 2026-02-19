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
});

// Protected Routes (ต้อง login) - Rate limited to 100 requests per minute
Route::middleware(['auth:sanctum', 'throttle:100,1'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class , 'logout']);
    Route::get('/me', [AuthController::class , 'me']);
    Route::post('/profile/image', [AuthController::class , 'updateProfileImage']);
    Route::patch('/profile', [AuthController::class , 'updateProfile']);
    Route::post('/change-password', [AuthController::class , 'changePassword']);

    // Wallet
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

    // Orders
    Route::get('/users/me/orders', [OrderController::class , 'myOrders']);
    Route::post('/products/{id}/close', [OrderController::class , 'closeAuction']);
    Route::patch('/orders/{id}/verify', [OrderController::class , 'verifyOrder']);

    // Post-Auction (confirm → ship → receive → dispute)
    Route::post('/orders/{id}/confirm', [PostAuctionController::class , 'confirm']);
    Route::get('/orders/{id}/detail', [PostAuctionController::class , 'detail']);
    Route::post('/orders/{id}/ship', [PostAuctionController::class , 'ship']);
    Route::post('/orders/{id}/receive', [PostAuctionController::class , 'receive']);
    Route::post('/orders/{id}/dispute', [PostAuctionController::class , 'dispute']);

    // Notifications
    Route::get('/notifications', [NotificationController::class , 'index']);
    Route::get('/notifications/unread', [NotificationController::class , 'unread']);
    Route::patch('/notifications/read-all', [NotificationController::class , 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class , 'markAsRead']);

    // Reports
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports', [ReportController::class, 'index']);
});

// Admin Routes (ต้อง login + เป็น admin) - Rate limited to 100 requests per minute
Route::middleware(['auth:sanctum', 'admin', 'throttle:100,1'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Reports
    Route::get('/reports', [AdminController::class, 'reports']);
    Route::patch('/reports/{id}', [AdminController::class, 'updateReport']);

    // Disputes
    Route::get('/disputes', [AdminController::class, 'disputes']);
    Route::patch('/disputes/{id}/resolve', [AdminController::class, 'resolveDispute']);

    // Users
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/users/{id}', [AdminController::class, 'userDetail']);
    Route::post('/users/{id}/ban', [AdminController::class, 'banUser']);
});