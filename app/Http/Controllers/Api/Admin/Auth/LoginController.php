<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $credentials = $request->only('email', 'password');

            if (! $token = auth('admin-api')->setTTL(1440)->attempt($credentials)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid email or password',
                ], 401);
            }

            return response()->json([
                'status'     => true,
                'message'    => 'Login successful',
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => 1440 * 60,
                'admin'      => auth('admin-api')->user(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            auth('admin-api')->logout();

            return response()->json([
                'status'  => true,
                'message' => 'Logout successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}

