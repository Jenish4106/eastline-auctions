<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;

Route::post('/register',[RegisterController::class,'register']);
Route::post('/login',[LoginController::class,'login']);
Route::post('/forgot-password',[ForgotPasswordController::class,'forgotPassword']);
Route::post('/verify-otp',[ForgotPasswordController::class,'verifyOtp']);
Route::post('/reset-password',[ForgotPasswordController::class,'resetPassword']);