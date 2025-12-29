<?php
namespace App\Http\Controllers\Api\Auth;

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
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $credentials = $request->only('email', 'password');

            if (! $token = auth('api')->attempt($credentials)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid email or password',
                ], 401);
            }

            $user = auth('api')->user();
            
            if ($user && $user->status == 2) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Your account has been blocked. Please contact administrator.',
                ], 401);
            }

            return response()->json([
                'status'     => true,
                'message'    => 'Login successful',
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user'       => auth('api')->user(),
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
        auth('api')->logout();

        return response()->json([
            'status' => true,
            'message' => 'Logout successful'
        ]);
    }
}
