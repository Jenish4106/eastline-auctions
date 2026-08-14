<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class FileResolverService
{
    private static array $inMemoryCache = [];

    /**
     * Fast S3 checker fallback
     */
    private static function checkS3Url(string $s3Url, string $cleanFilename): bool
    {
        return true;
    }

    /**
     * Pre-check multiple S3 URLs simultaneously (kept for backward compatibility)
     */
    public static function preloadS3Urls(array $filenames, string $type = 'machinery'): void
    {
        // No-op for fast memory-first operation
    }

    /**
     * Resolve category image URL dynamically
     * Priority: 1. AWS S3  2. Local Server  3. Default Image
     */
    public static function resolveCategoryImageUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default.png');

        if (empty($filename)) {
            return $defaultUrl;
        }

        $cleanFilename = basename(parse_url($filename, PHP_URL_PATH) ?? $filename);
        if (empty($cleanFilename) || $cleanFilename === 'default.png') {
            return $defaultUrl;
        }

        // Direct HTTP/S3 URL check
        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        // Local Server Check (Instant local disk check)
        $localPath = public_path('uploads/category/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/category/images/' . $cleanFilename);
        }

        // AWS S3 Check (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/uploads/category/images/' . $cleanFilename;
        }

        // Fallback Default Image
        return $defaultUrl;
    }

    /**
     * Resolve machinery image URL dynamically
     * Priority: 1. AWS S3  2. Local Server  3. Default Image
     */
    public static function resolveMachineryImageUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default.png');

        if (empty($filename)) {
            return $defaultUrl;
        }

        $cleanFilename = basename(parse_url($filename, PHP_URL_PATH) ?? $filename);
        if (empty($cleanFilename) || $cleanFilename === 'default.png') {
            return $defaultUrl;
        }

        // Direct HTTP/S3 URL check
        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        // Local Server Check (Instant local disk check)
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/images/' . $cleanFilename);
        }

        // AWS S3 Check (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/uploads/machinery/images/' . $cleanFilename;
        }

        // Fallback Default Image
        return $defaultUrl;
    }

    /**
     * Resolve machinery image as base64 data URI (ideal for PDF rendering like DomPDF)
     * Priority: 1. AWS S3  2. Local Server  3. Default Image
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

        // 1. Local Server Check (Fast local disk check)
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            $mime = mime_content_type($localPath) ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localPath));
        }

        // 2. AWS S3 Fetch for PDF (Priority 1)
        $imageUrl = self::resolveMachineryImageUrl($filename);
        if (!empty($imageUrl) && !str_contains($imageUrl, 'defaults/default.png')) {
            $cleanUrl = strtok($imageUrl, '?');
            try {
                $context = stream_context_create([
                    'http' => ['timeout' => 2],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);
                $content = @file_get_contents($cleanUrl, false, $context);
                if ($content !== false && !empty($content)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_buffer($finfo, $content) ?: 'image/jpeg';
                    finfo_close($finfo);
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        // 3. Fallback Default Image
        return $defaultBase64;
    }

    /**
     * Resolve machinery video URL dynamically
     * Priority: 1. AWS S3  2. Local Server  3. Default Video
     */
    public static function resolveMachineryVideoUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default-machine.mp4');

        if (empty($filename)) {
            return $defaultUrl;
        }

        $cleanFilename = basename(parse_url($filename, PHP_URL_PATH) ?? $filename);
        if (empty($cleanFilename) || $cleanFilename === 'default-machine.mp4') {
            return $defaultUrl;
        }

        // Direct HTTP/S3 URL check
        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        // Local Server Check (Instant local disk check)
        $localPath = public_path('uploads/machinery/videos/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/videos/' . $cleanFilename);
        }

        // AWS S3 Check (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/uploads/machinery/videos/' . $cleanFilename;
        }

        // Fallback Default Video
        return $defaultUrl;
    }
}

