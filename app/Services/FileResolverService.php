<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class FileResolverService
{
    /**
     * Fast check with 24-hour cache key per filename.
     * New uploads create new filenames, so they resolve instantly.
     * Missing S3 files (404) cache false and fallback to default.png.
     */
    private static function isS3FileValid(string $s3Url, string $cleanFilename): bool
    {
        $cacheKey = 's3_exists_' . md5($cleanFilename);

        return Cache::remember($cacheKey, 86400, function () use ($s3Url) {
            try {
                $ch = curl_init($s3Url);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, 300);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 200);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                return $statusCode !== 404;
            } catch (\Throwable $e) {
                return true;
            }
        });
    }

    /**
     * Resolve category image URL dynamically
     * Priority: 1. AWS S3  2. Local Server  3. Default Image
     */
    public static function resolveCategoryImageUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();

        if (empty($filename)) {
            return $defaultUrl;
        }

        $cleanFilename = basename(parse_url($filename, PHP_URL_PATH) ?? $filename);
        if (empty($cleanFilename) || $cleanFilename === 'default.png') {
            return $defaultUrl;
        }

        // 1. AWS S3 Check (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        $s3Url = str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')
            ? $filename
            : (!empty($awsUrl) ? rtrim($awsUrl, '/') . '/uploads/category/images/' . $cleanFilename : null);

        if (!empty($s3Url)) {
            if (self::isS3FileValid($s3Url, $cleanFilename)) {
                return $s3Url;
            }
        }

        // 2. Local Server Check (Priority 2)
        $localPath = public_path('uploads/category/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/category/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Fallback Default Image (Priority 3)
        return $defaultUrl;
    }

    /**
     * Resolve machinery image URL dynamically
     * Priority: 1. AWS S3  2. Local Server  3. Default Image
     */
    public static function resolveMachineryImageUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();

        if (empty($filename)) {
            return $defaultUrl;
        }

        $cleanFilename = basename(parse_url($filename, PHP_URL_PATH) ?? $filename);
        if (empty($cleanFilename) || $cleanFilename === 'default.png') {
            return $defaultUrl;
        }

        // 1. AWS S3 Check (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        $s3Url = str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')
            ? $filename
            : (!empty($awsUrl) ? rtrim($awsUrl, '/') . '/uploads/machinery/images/' . $cleanFilename : null);

        if (!empty($s3Url)) {
            if (self::isS3FileValid($s3Url, $cleanFilename)) {
                return $s3Url;
            }
        }

        // 2. Local Server Check (Priority 2)
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Fallback Default Image (Priority 3)
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

        // 1. AWS S3 Fetch for PDF (Priority 1)
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

        // 2. Local Server Check (Priority 2)
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            $mime = mime_content_type($localPath) ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localPath));
        }

        // 3. Fallback Default Image (Priority 3)
        return $defaultBase64;
    }

    /**
     * Resolve machinery video URL dynamically
     * Priority: 1. AWS S3  2. Local Server  3. Default Video
     */
    public static function resolveMachineryVideoUrl(?string $filename): string
    {
        $defaultUrl = asset('public/uploads/defaults/default-machine.mp4') . '?time=' . time();

        if (empty($filename)) {
            return $defaultUrl;
        }

        $cleanFilename = basename(parse_url($filename, PHP_URL_PATH) ?? $filename);
        if (empty($cleanFilename) || $cleanFilename === 'default-machine.mp4') {
            return $defaultUrl;
        }

        // 1. AWS S3 Check (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        $s3Url = str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')
            ? $filename
            : (!empty($awsUrl) ? rtrim($awsUrl, '/') . '/uploads/machinery/videos/' . $cleanFilename : null);

        if (!empty($s3Url)) {
            if (self::isS3FileValid($s3Url, $cleanFilename)) {
                return $s3Url;
            }
        }

        // 2. Local Server Check (Priority 2)
        $localPath = public_path('uploads/machinery/videos/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/videos/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Fallback Default Video (Priority 3)
        return $defaultUrl;
    }
}
