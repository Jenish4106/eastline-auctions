<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileResolverService
{
    /**
     * Resolve category image URL dynamically
     * Priority: 1. AWS S3  2. Local Public Server  3. Default Image
     */
    public static function resolveCategoryImageUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();

        if (empty($filename)) {
            return $defaultUrl;
        }

        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        $cleanFilename = basename(ltrim($filename, '/'));
        if (empty($cleanFilename) || $cleanFilename === 'default.png') {
            return $defaultUrl;
        }

        // 1. Try S3 first
        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'))) {
            $s3Path = 'uploads/category/images/' . $cleanFilename;
            try {
                if (Storage::disk('s3')->exists($s3Path)) {
                    return Storage::disk('s3')->url($s3Path);
                }
            } catch (\Throwable $e) {
                // S3 check failed or unreachable, fallback to public server
            }
        }

        // 2. Try Public/Local Server
        $localPath = public_path('uploads/category/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/category/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Fallback Default Image
        return $defaultUrl;
    }

    /**
     * Resolve machinery image URL dynamically
     * Priority: 1. AWS S3  2. Local Public Server  3. Default Image
     */
    public static function resolveMachineryImageUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();

        if (empty($filename)) {
            return $defaultUrl;
        }

        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        $cleanFilename = basename(ltrim($filename, '/'));
        if (empty($cleanFilename) || $cleanFilename === 'default.png') {
            return $defaultUrl;
        }

        // 1. Try S3 first
        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'))) {
            $s3Path = 'uploads/machinery/images/' . $cleanFilename;
            try {
                if (Storage::disk('s3')->exists($s3Path)) {
                    return Storage::disk('s3')->url($s3Path);
                }
            } catch (\Throwable $e) {
                // S3 check failed or unreachable, fallback to public server
            }
        }

        // 2. Try Public/Local Server
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Fallback Default Image
        return $defaultUrl;
    }

    /**
     * Resolve machinery video URL dynamically
     * Priority: 1. AWS S3  2. Local Public Server  3. Default Video
     */
    public static function resolveMachineryVideoUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default-machine.mp4') . '?time=' . time();

        if (empty($filename)) {
            return $defaultUrl;
        }

        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        $cleanFilename = basename(ltrim($filename, '/'));
        if (empty($cleanFilename) || $cleanFilename === 'default-machine.mp4') {
            return $defaultUrl;
        }

        // 1. Try S3 first
        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'))) {
            $s3Path = 'uploads/machinery/videos/' . $cleanFilename;
            try {
                if (Storage::disk('s3')->exists($s3Path)) {
                    return Storage::disk('s3')->url($s3Path);
                }
            } catch (\Throwable $e) {
                // S3 check failed or unreachable, fallback to public server
            }
        }

        // 2. Try Public/Local Server
        $localPath = public_path('uploads/machinery/videos/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/videos/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Fallback Default Video
        return $defaultUrl;
    }
}
