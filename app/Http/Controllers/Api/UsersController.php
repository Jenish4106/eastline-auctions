<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\License;
use App\Models\Machinery;
use App\Models\Order;

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

                if ($existingLicense) {
                    $existingLicense->update($licenseData);
                    $license = $existingLicense;
                } else {
                    $license = License::create($licenseData);
                }

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
}