<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        try {
            $settings = Settings::allToArray();

            if (! $settings) {
                return response()->json([
                    'success' => true,
                    'message' => 'No settings found.',
                ], 200);
            }

            if (isset($settings['white_logo'])) {
                $settings['white_logo'] = asset($settings['white_logo']);
            }
            
            if (isset($settings['dark_logo'])) {
                $settings['dark_logo'] = asset($settings['dark_logo']);
            }

            return response()->json([
                'success' => true,
                'data'    => $settings,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings.',
            ], 500);
        }
    }

    /**
     * Update settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_name'           => 'nullable|string|max:255',
                'email'                  => 'nullable|string|email|max:255',
                'phone_no'               => 'nullable|string|max:20',
                'address'                => 'nullable|string',
                'white_logo'             => 'nullable|file|mimes:jpeg,png,jpg|max:20480',
                'dark_logo'              => 'nullable|file|mimes:jpeg,png,jpg|max:20480',
                'per_mile_delivery_cost' => 'nullable|numeric|min:0',
                'facebook'               => 'nullable|string|max:255',
                'twitter'                => 'nullable|string|max:255',
                'instagram'              => 'nullable|string|max:255',
                'linkedin'               => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $whiteLogoPath = null;
            $darkLogoPath = null;
            
            if ($request->hasFile('white_logo')) {
                $file     = $request->file('white_logo');
                $fileName = time() . '_white_logo.' . $file->getClientOriginalExtension();

                $destinationPath = public_path('settings');
                if (! file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $whiteLogoPath = 'settings/' . $fileName;

                $oldWhiteLogo = Settings::get('white_logo');
                if ($oldWhiteLogo && file_exists(public_path($oldWhiteLogo))) {
                    unlink(public_path($oldWhiteLogo));
                }
            }
            
            if ($request->hasFile('dark_logo')) {
                $file     = $request->file('dark_logo');
                $fileName = time() . '_dark_logo.' . $file->getClientOriginalExtension();

                $destinationPath = public_path('settings');
                if (! file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $darkLogoPath = 'settings/' . $fileName;

                $oldDarkLogo = Settings::get('dark_logo');
                if ($oldDarkLogo && file_exists(public_path($oldDarkLogo))) {
                    unlink(public_path($oldDarkLogo));
                }
            }

            $existingSettings = Settings::first();
            $isFirstTime = $existingSettings === null;
            
            $settingsToUpdate = [
                'company_name'           => $request->input('company_name'),
                'email'                  => $request->input('email'),
                'phone_no'               => $request->input('phone_no'),
                'address'                => $request->input('address'),
                'per_mile_delivery_cost' => $request->input('per_mile_delivery_cost'),
                'facebook'               => $request->input('facebook'),
                'twitter'                => $request->input('twitter'),
                'instagram'              => $request->input('instagram'),
                'linkedin'               => $request->input('linkedin'),
            ];

            if ($whiteLogoPath) {
                $settingsToUpdate['white_logo'] = $whiteLogoPath;
            }
            
            if ($darkLogoPath) {
                $settingsToUpdate['dark_logo'] = $darkLogoPath;
            }

            foreach ($settingsToUpdate as $key => $value) {
                if ($value !== null) {
                    Settings::set($key, $value);
                }
            }
            
            if ($isFirstTime) {
                $defaultSettings = [
                    'company_name'           => $request->input('company_name', ''),
                    'email'                  => $request->input('email', ''),
                    'phone_no'               => $request->input('phone_no', ''),
                    'address'                => $request->input('address', ''),
                    'per_mile_delivery_cost' => $request->input('per_mile_delivery_cost', 0),
                    'facebook'               => $request->input('facebook', ''),
                    'twitter'                => $request->input('twitter', ''),
                    'instagram'              => $request->input('instagram', ''),
                    'linkedin'               => $request->input('linkedin', ''),
                ];
                
                foreach ($defaultSettings as $key => $value) {
                    if (!Settings::where('key', $key)->exists()) {
                        Settings::set($key, $value);
                    }
                }
            }

            $updatedSettings = Settings::allToArray();

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data'    => $updatedSettings,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings.',
            ], 500);
        }
    }

    public function changeAdminPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password'     => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8|same:new_password',
            ], [
                'confirm_password.same' => 'New password and confirm password do not match.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $admin = auth('admin-api')->user();

            if (! Hash::check($request->current_password, $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            $admin->password = Hash::make($request->new_password);
            $admin->save();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}