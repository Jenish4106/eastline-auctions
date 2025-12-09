<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ChangePasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'index'])->name('admin.login');
Route::post('/login-check', [LoginController::class, 'loginCheck'])->name('admin.login.check');
Route::get('/logout', [LoginController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth.admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('change.password');
    
    Route::prefix('user-management')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.user.management');
        Route::get('/fetch', [UserController::class, 'fetchUsers'])->name('admin.users.fetch');
        Route::post('/store', [UserController::class, 'store'])->name('admin.users.store');
        Route::post('/delete', [UserController::class, 'deleteUser'])->name('admin.users.delete');
        Route::post('/change-status', [UserController::class, 'changeStatus'])->name('admin.users.change.status');
    });
    
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('admin.categories');
        Route::get('/fetch', [CategoryController::class, 'fetchCategories'])->name('admin.categories.fetch');
        Route::post('/store', [CategoryController::class, 'store'])->name('admin.categories.store');
        Route::get('/get', [CategoryController::class, 'getCategory'])->name('admin.categories.get');
        Route::post('/update/{id}', [CategoryController::class, 'update'])->name('admin.categories.update');
        Route::post('/delete', [CategoryController::class, 'destroy'])->name('admin.categories.delete');
    });
});