<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        try {
            // Validate the email
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Get the user
            $user = User::where('email', $request->email)->first();

            // Generate a 4-digit OTP
            $otp = rand(1000, 9999);

            // Set expiration time (10 minutes from now)
            $expireAt = Carbon::now()->addMinutes(10);

            // Delete any existing password reset records for this user
            PasswordReset::where('user_id', $user->id)->delete();

            // Create a new password reset record
            $passwordReset = PasswordReset::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'otp' => $otp,
                'expire_at' => $expireAt,
            ]);

            // Send the OTP via email
            Mail::to($request->email)->send(new SendOtpMail($otp, $request->email));

            return response()->json([
                'status' => true,
                'message' => 'OTP sent successfully to your email'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|numeric|digits:4'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $passwordReset = PasswordReset::where('email', $request->email)
                ->where('otp', $request->otp)
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid OTP or email',
                ], 404);
            }

            if (Carbon::now()->greaterThan($passwordReset->expire_at)) {
                $passwordReset->delete();
                
                return response()->json([
                    'status' => false,
                    'message' => 'OTP has expired',
                ], 400);
            }

            $passwordReset->verified_at = Carbon::now();
            $passwordReset->save();

            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:6|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $passwordReset = PasswordReset::where('email', $request->email)
                ->whereNotNull('verified_at')
                ->first();

            if (!$passwordReset) {
                return response()->json([
                    'status' => false,
                    'message' => 'No verified OTP found for this email',
                ], 404);
            }

            if (Carbon::now()->greaterThan($passwordReset->expire_at)) {
                $passwordReset->delete();
                
                return response()->json([
                    'status' => false,
                    'message' => 'OTP has expired',
                ], 400);
            }

            $user = User::where('email', $request->email)->first();

            $user->password = Hash::make($request->password);
            $user->save();

            $passwordReset->delete();

            return response()->json([
                'status' => true,
                'message' => 'Password reset successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }
}