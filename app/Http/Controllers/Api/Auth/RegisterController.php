<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name'            => 'required|string|max:255',
                'last_name'             => 'required|string|max:255',
                'email'                 => 'required|string|email|max:255|unique:users,email',
                'phone_no'              => 'required|string|max:20|unique:users,phone_no',
                'address'               => 'required|string',
                'company_name'          => 'required|string|max:255',
                'city'                  => 'required|string|max:255',
                'state'                 => 'required|string|max:255',
                'zip_code'              => 'required|string|max:10',
                'password'              => 'required|string|min:8',
                'password_confirmation' => 'required|string|same:password',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $user = User::create([
                'first_name'   => $request->first_name,
                'last_name'    => $request->last_name,
                'email'        => $request->email,
                'phone_no'     => $request->phone_no,
                'address'      => $request->address,
                'company_name' => $request->company_name,
                'city'         => $request->city,
                'state'        => $request->state,
                'zip_code'     => $request->zip_code,
                'password'     => Hash::make($request->password),
            ]);

            $credentials = $request->only('email', 'password');
            if (! $token = auth('api')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create token after registration',
                ], 500);
            }

            return response()->json([
                'success'    => true,
                'message'    => 'User registered successfully',
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'data'       => $user,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}