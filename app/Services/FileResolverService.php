<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileResolverService
{
    private static array $s3Cache = [];

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

        // 1. S3 Check
        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'))) {
            $s3Path = 'uploads/category/images/' . $cleanFilename;
            try {
                if (!isset(self::$s3Cache[$s3Path])) {
                    self::$s3Cache[$s3Path] = Storage::disk('s3')->exists($s3Path);
                }
                if (self::$s3Cache[$s3Path]) {
                    return Storage::disk('s3')->url($s3Path);
                }
            } catch (\Throwable $e) {
                // S3 check failed, fallback to local server
            }
        }

        // 2. Local Server Check
        $localPath = public_path('uploads/category/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/category/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Default Image Fallback (if image is missing or broken)
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

        // 1. S3 Check
        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'))) {
            $s3Path = 'uploads/machinery/images/' . $cleanFilename;
            try {
                if (!isset(self::$s3Cache[$s3Path])) {
                    self::$s3Cache[$s3Path] = Storage::disk('s3')->exists($s3Path);
                }
                if (self::$s3Cache[$s3Path]) {
                    return Storage::disk('s3')->url($s3Path);
                }
            } catch (\Throwable $e) {
                // S3 check failed, fallback to local server
            }
        }

        // 2. Local Server Check
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Default Image Fallback (if image is missing or broken)
        return $defaultUrl;
    }

    /**
     * Resolve machinery image as base64 data URI (ideal for PDF rendering like DomPDF)
     * Priority: 1. AWS S3  2. Local Public Server  3. Default Image
     */
    public static function resolveMachineryImageBase64(?string $filename): ?string
    {
        $defaultLocalPath = public_path('uploads/defaults/default.png');
        $defaultBase64 = file_exists($defaultLocalPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($defaultLocalPath))
            : null;

        if (empty($filename)) {
            return $defaultBase64;
        }

        $cleanFilename = basename(parse_url($filename, PHP_URL_PATH) ?? $filename);

        // 1. S3 Check
        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'))) {
            $s3Path = 'uploads/machinery/images/' . $cleanFilename;
            try {
                if (!isset(self::$s3Cache[$s3Path])) {
                    self::$s3Cache[$s3Path] = Storage::disk('s3')->exists($s3Path);
                }
                if (self::$s3Cache[$s3Path]) {
                    $s3Url = Storage::disk('s3')->url($s3Path);
                    $context = stream_context_create([
                        'http' => ['timeout' => 3],
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                    ]);
                    $content = @file_get_contents($s3Url, false, $context);
                    if ($content !== false && !empty($content)) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_buffer($finfo, $content) ?: 'image/jpeg';
                        finfo_close($finfo);
                        return 'data:' . $mime . ';base64,' . base64_encode($content);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to local server
            }
        }

        // 2. Local Server Check
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            $mime = mime_content_type($localPath) ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localPath));
        }

        // 3. Default Image Fallback
        return $defaultBase64;
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

        // 1. S3 Check
        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'))) {
            $s3Path = 'uploads/machinery/videos/' . $cleanFilename;
            try {
                if (!isset(self::$s3Cache[$s3Path])) {
                    self::$s3Cache[$s3Path] = Storage::disk('s3')->exists($s3Path);
                }
                if (self::$s3Cache[$s3Path]) {
                    return Storage::disk('s3')->url($s3Path);
                }
            } catch (\Throwable $e) {
                // S3 check failed, fallback to local server
            }
        }

        // 2. Local Server Check
        $localPath = public_path('uploads/machinery/videos/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/videos/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Default Video Fallback
        return $defaultUrl;
    }
}
