<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ChangePasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\MachineryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'index'])->name('admin.login');
Route::post('/login-check', [LoginController::class, 'loginCheck'])->name('admin.login.check');
Route::get('/logout', [LoginController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth.admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('change.password');
    
    Route::prefix('user-management')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.users.management');
        Route::get('/{id}', [UserController::class, 'show'])->name('admin.users.show');
        Route::get('/fetch', [UserController::class, 'fetchUsers'])->name('admin.users.fetch');
        Route::post('/store', [UserController::class, 'store'])->name('admin.users.store');
        Route::post('/delete', [UserController::class, 'deleteUser'])->name('admin.users.delete');
        Route::post('/change-status', [UserController::class, 'changeStatus'])->name('admin.users.change.status');
        Route::post('/license/approve', [UserController::class, 'approveLicense'])->name('admin.license.approve');
        Route::post('/license/decline', [UserController::class, 'declineLicense'])->name('admin.license.decline');
    });
    
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('admin.categories');
        Route::get('/fetch', [CategoryController::class, 'fetchCategories'])->name('admin.categories.fetch');
        Route::post('/store', [CategoryController::class, 'store'])->name('admin.categories.store');
        Route::get('/get', [CategoryController::class, 'getCategory'])->name('admin.categories.get');
        Route::post('/update/{id}', [CategoryController::class, 'update'])->name('admin.categories.update');
        Route::post('/delete', [CategoryController::class, 'destroy'])->name('admin.categories.delete');
    });
    
    Route::prefix('machinery')->group(function () {
        Route::get('/', [MachineryController::class, 'index'])->name('admin.machinery');
        Route::get('/fetch', [MachineryController::class, 'fetchMachinery'])->name('admin.machinery.fetch');
        Route::post('/store', [MachineryController::class, 'store'])->name('admin.machinery.store');
        Route::get('/get', [MachineryController::class, 'getMachinery'])->name('admin.machinery.get');
        Route::post('/update/{id}', [MachineryController::class, 'update'])->name('admin.machinery.update');
        Route::post('/delete', [MachineryController::class, 'destroy'])->name('admin.machinery.delete');
    });
});