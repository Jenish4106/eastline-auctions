<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ChangePasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'index'])->name('admin.login');
Route::post('/login-check', [LoginController::class, 'loginCheck'])->name('admin.login.check');
Route::get('/logout', [LoginController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth.admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('change.password');
    
    // User Management Routes
    Route::get('/user-management', [UserController::class, 'index'])->name('admin.user.management');
    Route::get('/user-management/fetch', [UserController::class, 'fetchUsers'])->name('admin.users.fetch');
});