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

                    if($user->is_license){
                        return response()->json([
                            'success' => true,
                            'is_verify' => true,
                        ], 200);
                    } else {
                        return response()->json([
                            'success' => false,
                            'is_verify' => false,
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
