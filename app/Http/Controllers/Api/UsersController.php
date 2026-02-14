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

    public function getUserDetails(Request $request) {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access. Please provide a valid token.',
                ], 401);
            }

            return response()->json([
                'status' => true,
                'message' => 'User details retrieved successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
    public function updateLicenseStatus(Request $request)
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
                'status' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()->first(),
                ], 422);
            }

            $latestLicense = License::where('user_id', $user->id)->latest()->first();

            $status = $request->status;

            if ($latestLicense) {
                $latestLicense->update(['status' => $status]);
            }
            
            $user->update(['is_license' => $status]);

            return response()->json([
                'status' => true,
                'message' => 'License status updated successfully',
                'data' => [
                    'user_id' => $user->id,
                    'license_status' => $status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}