<?php
namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ChangePasswordController extends Controller
{
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password'              => 'required|string',
                'new_password'              => 'required|string|min:8',
                'new_password_confirmation' => 'required|string|same:new_password',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $admin = Auth::guard('admin')->user();

            if (! Hash::check($request->old_password, $admin->password)) {
                return response()->json([
                    'message' => 'The provided old password does not match our records.',
                ], 400);
            }

            $admin->password = Hash::make($request->new_password);
            $admin->save();

            return response()->json([
                'message' => 'Password changed successfully.',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while changing the password. Please try again.',
            ], 500);
        }
    }
}
