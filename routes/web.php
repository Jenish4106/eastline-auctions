<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;

Route::get('/',[LoginController::class,'index'])->name('admin.login');
Route::post('/login-check', [LoginController::class, 'loginCheck'])->name('admin.login.check');
Route::get('/logout', [LoginController::class, 'logout'])->name('admin.logout');