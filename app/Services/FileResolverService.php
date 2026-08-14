<?php

namespace App\Services;

class FileResolverService
{
    /**
     * Resolve category image URL dynamically (Instant ~ 0ms)
     * Priority: 1. AWS S3  2. Local Server  3. Default Image
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

        // 1. AWS S3 Direct URL (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/uploads/category/images/' . $cleanFilename;
        }

        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'))) {
            return 'https://' . config('filesystems.disks.s3.bucket') . '.s3.amazonaws.com/uploads/category/images/' . $cleanFilename;
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
     * Resolve machinery image URL dynamically (Instant ~ 0ms)
     * Priority: 1. AWS S3  2. Local Server  3. Default Image
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

        // 1. AWS S3 Direct URL (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/uploads/machinery/images/' . $cleanFilename;
        }

        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'))) {
            return 'https://' . config('filesystems.disks.s3.bucket') . '.s3.amazonaws.com/uploads/machinery/images/' . $cleanFilename;
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
     * Resolve machinery video URL dynamically (Instant ~ 0ms)
     * Priority: 1. AWS S3  2. Local Server  3. Default Video
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

        // 1. AWS S3 Direct URL (Priority 1)
        $awsUrl = config('filesystems.disks.s3.url');
        if (!empty($awsUrl)) {
            return rtrim($awsUrl, '/') . '/uploads/machinery/videos/' . $cleanFilename;
        }

        if (!empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'))) {
            return 'https://' . config('filesystems.disks.s3.bucket') . '.s3.amazonaws.com/uploads/machinery/videos/' . $cleanFilename;
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
