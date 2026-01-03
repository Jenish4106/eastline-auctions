<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get settings by keys without authentication - key-wise data retrieval
     * If no keys provided, returns all settings
     * If keys provided, returns only those specific settings
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings(Request $request)
    {
        $keys = $request->input('keys', []);

        if (empty($keys)) {
            $settings = Settings::all();
        } else {
            $settings = Settings::whereIn('key', $keys)->get();
        }

        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting->key] = $setting->value;
        }

        return response()->json([
            'success' => true,
            'data' => $settingsArray
        ], 200);
    }
}