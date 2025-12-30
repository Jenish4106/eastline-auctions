<?php

use App\Http\Controllers\Api\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Api\Admin\BiddingController as AdminBiddingController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\MachineryController as AdminMachineryController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\UploadController as AdminUploadController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\BiddingController;
use App\Http\Controllers\Api\Frontend\InventoryController;
use App\Http\Controllers\Api\SettingsController as UserSettingsController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::post('/admin/login', [AdminLoginController::class, 'login']);

Route::middleware(['auth.admin-api'])->prefix('admin')->group(function () {
    Route::post('/logout', [AdminLoginController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::prefix('upload')->group(function () {
        Route::post('/image', [AdminUploadController::class, 'uploadImage']);
        Route::post('/video', [AdminUploadController::class, 'uploadVideo']);
    });

    Route::prefix('categories')->group(function () {
        Route::post('/', [AdminCategoryController::class, 'index']);
        Route::post('/show', [AdminCategoryController::class, 'show']);
        Route::post('/store', [AdminCategoryController::class, 'store']);
        Route::post('/update', [AdminCategoryController::class, 'update']);
        Route::post('/delete', [AdminCategoryController::class, 'delete']);
    });

    Route::prefix('machinery')->group(function () {
        Route::post('/', [AdminMachineryController::class, 'index']);
        Route::post('/show', [AdminMachineryController::class, 'show']);
        Route::post('/store', [AdminMachineryController::class, 'store']);
        Route::post('/update', [AdminMachineryController::class, 'update']);
        Route::post('/delete', [AdminMachineryController::class, 'delete']);
    });

    Route::prefix('bidding')->group(function () {
        Route::post('/machinery-bidding-info', [AdminBiddingController::class, 'getMachineryBiddingInfo']);
        Route::post('/machinery-bidding-details', [AdminBiddingController::class, 'getMachineryBiddingDetails']);
        Route::post('/bidding-won-users', [AdminBiddingController::class, 'getBiddingWonUsers']);
        Route::post('/machinery-wise-won-details', [AdminBiddingController::class, 'getMachineryWiseWonDetails']);
        Route::post('/update-contract-status', [AdminBiddingController::class, 'updateContractStatus']);
    });

    Route::prefix('orders')->group(function () {
        Route::post('/update-status', [OrderController::class, 'updateOrderStatus']);
    });

    Route::prefix('users')->group(function () {
        Route::post('/', [AdminUserController::class, 'index']);
        Route::post('/show', [AdminUserController::class, 'show']);
        Route::post('/update', [AdminUserController::class, 'update']);
        Route::post('/delete', [AdminUserController::class, 'delete']);
        Route::post('/change-status', [AdminUserController::class, 'changeStatus']);
        Route::post('/license/manage', [AdminUserController::class, 'manageLicense']);
    });

    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'getSettings']);
        Route::post('/update', [SettingsController::class, 'updateSettings']);
        Route::post('/change-admin-password', [SettingsController::class, 'changeAdminPassword']);
    });
});

Route::middleware(['auth.user'])->group(function () {
    Route::post('/user/logout', [LoginController::class, 'logout']);
    Route::post('/user/upload-license', [UsersController::class, 'uploadLicense']);

    Route::post('/user/settings', [UserSettingsController::class, 'settings']);

    Route::post('/place-bid', [BiddingController::class, 'placeBid']);
    Route::post('/user/my-bids', [BiddingController::class, 'getMachineryWithBids']);
    Route::post('/user/machinery-bidding-details', [BiddingController::class, 'getMachineryBiddingDetails']);
    Route::post('/user/won-bids', [BiddingController::class, 'getUserWonBids']);
    Route::post('/user/single-won-bids', [BiddingController::class, 'getSingleWonBid']);

    Route::post('/user/sign-contract', [BiddingController::class, 'addSignatureToContract']);
    Route::post('/user/machinery-purchase', [BiddingController::class, 'purchaseMachinery']);
    Route::post('/user/orders', [BiddingController::class, 'getUserOrders']);
    Route::post('/user/order-details', [BiddingController::class, 'getOrderDetails']);
    
    Route::get('/user/dashboard', [UserDashboardController::class, 'index']);

    Route::post('/user/profile-update', [UsersController::class, 'updateProfile']);
    Route::post('/user/profile-update', [UsersController::class, 'getProfile']);
});

Route::get('/get-categories', [UsersController::class, 'getCategories']);

Route::get('/inventory/categories', [InventoryController::class, 'getCategoryList']);
Route::post('/inventory/machinery/category', [InventoryController::class, 'getMachineryByCategory']);
Route::post('/inventory/machinery', [InventoryController::class, 'getMachineryDetails']);
Route::post('/inventory/makes-models', [InventoryController::class, 'getMakesOrModels']);
