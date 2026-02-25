<?php

use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "web" middleware group. Make something great!
 * |
 */

Route::get('/', function () {
    return response()->json(['message' => 'Welcome to the API']);
});

Route::get('/uploads/{folder}/{filename}', function ($folder, $filename) {
    if (!in_array($folder, ['invoices', 'machinery_files', 'payment_slips', 'signatures'])) {
        abort(404);
    }

    $path = public_path('uploads/' . $folder . '/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }

    $token = request()->bearerToken() ?: request()->input('token');

    if (!$token) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized Access. Please login.'], 401);
    }

    $admin = null;
    $user = null;

    try {
        $admin = auth('admin-api')->setToken($token)->user();
    } catch (\Exception $e) {
    }

    if (!$admin) {
        try {
            $user = auth('user')->setToken($token)->user();
        } catch (\Exception $e) {
        }
    }

    if ($admin || $user) {
        return response()->file($path);
    }

    return response()->json(['status' => 'error', 'message' => 'Unauthorized Access. Invalid token.'], 401);
});
