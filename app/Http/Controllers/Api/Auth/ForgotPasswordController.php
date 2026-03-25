<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtpMail;
use App\Mail\PasswordResetConfirmationMail;
use App\Models\User;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Services\SMTP2GOService;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            $otp = rand(1000, 9999);

            $expireAt = Carbon::now()->addMinutes(10);

            PasswordReset::where('user_id', $user->id)->delete();

            PasswordReset::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'otp' => $otp,
                'expire_at' => $expireAt,
            ]);

            $mail = new SendOtpMail($otp, $request->email);
            $smtp2goService = new SMTP2GOService();
            $htmlContent = $mail->renderHtmlContent();
            $result = $smtp2goService->sendEmail($request->email, $mail->getSubject(), $htmlContent);

            if (!$result) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to send OTP. Please try again.'
                ], 500);
            }

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
                    'message' => $validator->errors(),
                ], 422);
            }

            $passwordReset = PasswordReset::where('email', $request->email)
                ->where('otp', $request->otp)
                ->latest()
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
            $passwordReset->expire_at = Carbon::now()->addMinutes(10);

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
                    'message' => $validator->errors(),
                ], 422);
            }

            $passwordReset = PasswordReset::where('email', $request->email)
                ->whereNotNull('verified_at')
                ->latest()
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

            try {
                $mail = new PasswordResetConfirmationMail($user);
                $smtp2goService = new SMTP2GOService();
                $htmlContent = $mail->renderHtmlContent();
                $smtp2goService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => true,
                    'message' => 'Password reset successfully but failed to send confirmation email.',
                ], 200);
            }

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