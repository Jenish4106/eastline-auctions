<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class S3StorageService
{
    /**
     * Get configured default storage disk (s3 or local)
     */
    public static function disk()
    {
        return config('filesystems.default', 's3');
    }

    /**
     * Upload an uploaded file object or file content string to storage
     *
     * @param mixed $file UploadedFile instance or raw content string
     * @param string $directory Subfolder path (e.g. 'category/images', 'invoices', 'licenses')
     * @param string|null $filename Custom filename, if null auto-generates
     * @return array Contains 'filename', 'relative_path', 'url'
     */
    public static function upload($file, string $directory, ?string $filename = null): array
    {
        $disk = self::disk();

        if (is_object($file) && method_exists($file, 'getClientOriginalExtension')) {
            $extension = $file->getClientOriginalExtension();
            if (!$filename) {
                $filename = time() . '_' . Str::random(10) . '_' . uniqid() . '.' . $extension;
            }
            $relativePath = trim($directory, '/') . '/' . $filename;

            if ($disk === 's3') {
                Storage::disk('s3')->putFileAs(trim($directory, '/'), $file, $filename);
            } else {
                $destinationPath = public_path('uploads/' . trim($directory, '/'));
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
            }
        } else {
            // Raw binary string (e.g. PDF output, decoded base64 image/signature)
            if (!$filename) {
                $filename = time() . '_' . Str::random(10) . '_' . uniqid();
            }
            $relativePath = trim($directory, '/') . '/' . $filename;

            if ($disk === 's3') {
                Storage::disk('s3')->put($relativePath, $file);
            } else {
                $destinationPath = public_path('uploads/' . trim($directory, '/'));
                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }
                File::put($destinationPath . '/' . $filename, $file);
            }
        }

        return [
            'filename' => $filename,
            'relative_path' => $relativePath,
            'url' => self::getUrl($relativePath),
        ];
    }

    /**
     * Get full public URL for a given relative storage path
     */
    public static function getUrl(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        if (strpos($relativePath, 'http://') === 0 || strpos($relativePath, 'https://') === 0) {
            return $relativePath;
        }

        $disk = self::disk();
        $relativePath = ltrim($relativePath, '/');

        if ($disk === 's3') {
            $awsUrl = env('AWS_URL');
            if ($awsUrl) {
                return rtrim($awsUrl, '/') . '/' . $relativePath;
            }
            return Storage::disk('s3')->url($relativePath);
        }

        return asset('public/uploads/' . $relativePath);
    }

    /**
     * Delete a file from storage
     */
    public static function delete(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $disk = self::disk();
        $relativePath = ltrim($relativePath, '/');

        if ($disk === 's3') {
            if (Storage::disk('s3')->exists($relativePath)) {
                return Storage::disk('s3')->delete($relativePath);
            }
        } else {
            $fullPath = public_path('uploads/' . $relativePath);
            if (File::exists($fullPath)) {
                return File::delete($fullPath);
            }
            $directPath = public_path($relativePath);
            if (File::exists($directPath)) {
                return File::delete($directPath);
            }
        }

        return false;
    }

    /**
     * Check if a file exists in storage
     */
    public static function exists(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $disk = self::disk();
        $relativePath = ltrim($relativePath, '/');

        if ($disk === 's3') {
            return Storage::disk('s3')->exists($relativePath);
        }

        return File::exists(public_path('uploads/' . $relativePath)) || File::exists(public_path($relativePath));
    }
}
