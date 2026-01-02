<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\License;
use App\Models\Machinery;
use App\Models\Order;
use App\Models\User;

class UsersController extends Controller
{
    public function uploadLicense(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:20480', // Max 20MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors()->first(),
                ], 422);
            }

            $user = auth('api')->user();

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $existingLicense = License::where('user_id', $user->id)->first();

            if ($request->hasFile('file')) {
                $file     = $request->file('file');
                $fileName = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();

                $destinationPath = public_path('licenses');
                if (! file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                if ($existingLicense && $existingLicense->file) {
                    $oldFilePath = public_path($existingLicense->file);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $file->move($destinationPath, $fileName);
                $filePath = 'licenses/' . $fileName;

                $licenseData = [
                    'user_id' => $user->id,
                    'file'    => $filePath,
                    'status'  => 0,
                ];

                $license = License::create($licenseData);

                $user->update(['is_license' => 0]);

                return response()->json([
                    'status'  => true,
                    'message' => 'License uploaded successfully',
                    'data'    => $license,
                ], 200);
            } else {
                return response()->json([
                    'status'  => false,
                    'message' => 'No file provided',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'company_name' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'zip_code' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()->first(),
                ], 422);
            }

            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_no' => $request->phone,
                'address' => $request->address,
                'company_name' => $request->company_name,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getProfile()
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $licenseStatusText = 'Unverified';
            
            $latestLicense = $user->license()->latest()->first();
            
            if ($latestLicense) {
                switch ($latestLicense->status) {
                    case 0:
                        $licenseStatusText = 'Pending';
                        break;
                    case 1:
                        $licenseStatusText = 'Verified';
                        break;
                    case 2:
                        $licenseStatusText = 'Declined';
                        break;
                }
            }
            
            $userData = $user->toArray();
            $userData['license_status'] = $licenseStatusText;

            return response()->json([
                'status' => true,
                'message' => 'Profile retrieved successfully',
                'data' => $userData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}