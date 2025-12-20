<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'key' => 'string',
        'value' => 'string',
    ];

    /**
     * Get the value of a setting by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set the value of a setting by key
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value)
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all settings as an associative array
     *
     * @return array
     */
    public static function allToArray()
    {
        return self::pluck('value', 'key')->toArray();
    }

    /**
     * Get the full URL of the logo
     *
     * @param string|null $logoPath
     * @param mixed $default
     * @return mixed
     */
    public static function getLogoUrl($logoPath = null, $default = null)
    {
        if ($logoPath === null) {
            $logoPath = self::get('logo');
        }
        
        if (!$logoPath) {
            return $default;
        }
        
        return asset($logoPath);
    }
}