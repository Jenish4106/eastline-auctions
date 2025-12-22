<?php
namespace App\Http\Controllers\Api;

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

            if (isset($settings['logo'])) {
                $settings['logo'] = asset($settings['logo']);
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
                'logo'                   => 'nullable|file|mimes:jpeg,png,jpg|max:20480',
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

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $file     = $request->file('logo');
                $fileName = time() . '_logo.' . $file->getClientOriginalExtension();

                $destinationPath = public_path('settings');
                if (! file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);
                $logoPath = 'settings/' . $fileName;

                $oldLogo = Settings::get('logo');
                if ($oldLogo && file_exists(public_path($oldLogo))) {
                    unlink(public_path($oldLogo));
                }
            }

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

            if ($logoPath) {
                $settingsToUpdate['logo'] = $logoPath;
            }

            foreach ($settingsToUpdate as $key => $value) {
                if ($value !== null) {
                    Settings::set($key, $value);
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
