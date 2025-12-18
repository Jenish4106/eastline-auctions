<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\MachineryController as AdminMachineryController;
use App\Http\Controllers\Api\Admin\UploadController as AdminUploadController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\InventoryController;

Route::post('/register',[RegisterController::class,'register']);
Route::post('/login',[LoginController::class,'login']);
Route::post('/forgot-password',[ForgotPasswordController::class,'forgotPassword']);
Route::post('/verify-otp',[ForgotPasswordController::class,'verifyOtp']);
Route::post('/reset-password',[ForgotPasswordController::class,'resetPassword']);

Route::post('/admin/login',[AdminLoginController::class,'login']);

Route::middleware(['auth.admin-api'])->prefix('admin')->group(function () {
    Route::post('/logout',[AdminLoginController::class,'logout']);
    
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
});

Route::middleware(['auth.api'])->group(function () {
    Route::post('/user/upload-license',[UsersController::class,'uploadLicense']);

    Route::get('/get-categories',[UsersController::class,'getCategories']);

    Route::get('/inventory/categories',[InventoryController::class,'getCategoryList']);
    Route::post('/inventory/machinery/category',[InventoryController::class,'getMachineryByCategory']);
    Route::post('/inventory/machinery',[InventoryController::class,'getMachineryDetails']);
});