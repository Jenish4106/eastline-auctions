<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function settings(Request $request)
    {
        try {
            $action = $request->action;

            if (! $action) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action is required.',
                ], 400);
            }

            switch($action) {
                case "login-check":
                    $user = auth('api')->user();
                    
                    if($user) {
                        return response()->json([
                            'success' => true,
                            'is_logged_in' => true,
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'is_logged_in' => false,
                        ], 200);
                    }
                    break;
                
                case "license-verify":
                    $user = auth('api')->user();
                    
                    $hasUploaded = \App\Models\License::where('user_id', $user->id)->exists();
                    
                    $latestLicense = null;
                    if ($hasUploaded) {
                        $latestLicense = \App\Models\License::where('user_id', $user->id)->latest()->first();
                    }
                    
                    $isVerified = false;
                    $isRejected = false;
                    if ($latestLicense) {
                        $isVerified = ($latestLicense->status == 1);
                        $isRejected = ($latestLicense->status == 2);
                    } else {
                        $isVerified = ($user->is_license == 1);
                        $isRejected = ($user->is_license == 2);
                    }
                    
                    if($hasUploaded && $isVerified) {
                        return response()->json([
                            'success' => true,
                            'is_verify' => true,
                            'is_upload' => true,
                            'is_reject' => false,
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => $hasUploaded, 
                            'is_verify' => false,
                            'is_upload' => $hasUploaded,
                            'is_reject' => $isRejected,
                        ], 200);
                    }
                    break;
                
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid action.',
                    ], 400);
                    break;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}
