<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileResolverService
{
    private static array $s3Cache = [];

    /**
     * Fast S3 file existence check with short timeout (prevents hanging & 404 broken images)
     */
    private static function checkS3FileExists(string $s3Url, string $s3Path): bool
    {
        if (isset(self::$s3Cache[$s3Path])) {
            return self::$s3Cache[$s3Path];
        }

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

            if ($statusCode === 404) {
                self::$s3Cache[$s3Path] = false;
                return false;
            }

            self::$s3Cache[$s3Path] = true;
            return true;
        } catch (\Throwable $e) {
            self::$s3Cache[$s3Path] = true;
            return true;
        }
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

        // 1. AWS S3 Check
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            $s3Url = rtrim($awsUrl, '/') . '/uploads/category/images/' . $cleanFilename;
            $s3Path = 'uploads/category/images/' . $cleanFilename;

            if (self::checkS3FileExists($s3Url, $s3Path)) {
                return $s3Url;
            }
        }

        // 2. Local Server Check
        $localPath = public_path('uploads/category/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/category/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Default Image Fallback
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

        // 1. AWS S3 Check
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            $s3Url = rtrim($awsUrl, '/') . '/uploads/machinery/images/' . $cleanFilename;
            $s3Path = 'uploads/machinery/images/' . $cleanFilename;

            if (self::checkS3FileExists($s3Url, $s3Path)) {
                return $s3Url;
            }
        }

        // 2. Local Server Check
        $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
        if (file_exists($localPath)) {
            return asset('public/uploads/machinery/images/' . $cleanFilename) . '?time=' . time();
        }

        // 3. Default Image Fallback
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

        // 1. AWS S3 Check
        $imageUrl = self::resolveMachineryImageUrl($filename);
        if (!empty($imageUrl) && !str_contains($imageUrl, 'defaults/default.png')) {
            $cleanUrl = strtok($imageUrl, '?');
            try {
                $context = stream_context_create([
                    'http' => ['timeout' => 3],
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

        // 1. AWS S3 Check
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            $s3Url = rtrim($awsUrl, '/') . '/uploads/machinery/videos/' . $cleanFilename;
            $s3Path = 'uploads/machinery/videos/' . $cleanFilename;

            if (self::checkS3FileExists($s3Url, $s3Path)) {
                return $s3Url;
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
