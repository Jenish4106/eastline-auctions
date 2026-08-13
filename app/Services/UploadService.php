<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    /**
     * Upload single or array of images to S3 / local storage
     *
     * @param UploadedFile|array $files
     * @param string $type
     * @return array
     */
    public function uploadImages($files, string $type = 'general'): array
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $filesArray = is_array($files) ? $files : [$files];
        $uploadedImages = [];
        $destinationPath = public_path('uploads/' . $type . '/images');

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        foreach ($filesArray as $image) {
            $imageName = time() . '_' . Str::random(10) . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $s3Path = 'uploads/' . $type . '/images/' . $imageName;

            if ($this->isS3Configured()) {
                Storage::disk('s3')->putFileAs('uploads/' . $type . '/images', $image, $imageName, 'public');
                $imageUrl = Storage::disk('s3')->url($s3Path);
            } else {
                $image->move($destinationPath, $imageName);
                $imageUrl = asset('public/uploads/' . $type . '/images/' . $imageName) . '?time=' . time();
            }

            $uploadedImages[] = [
                'filename' => $imageName,
                'url' => $imageUrl,
            ];
        }

        return $uploadedImages;
    }

    /**
     * Upload single or array of videos to S3 / local storage
     *
     * @param UploadedFile|array $files
     * @param string $type
     * @return array
     */
    public function uploadVideos($files, string $type = 'general'): array
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $filesArray = is_array($files) ? $files : [$files];
        $uploadedVideos = [];
        $destinationPath = public_path('uploads/' . $type . '/videos');

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        foreach ($filesArray as $video) {
            $originalName = $video->getClientOriginalName();
            $size = $video->getSize();
            $mimeType = $video->getMimeType();

            $videoName = time() . '_' . Str::random(10) . '_' . uniqid() . '.' . $video->getClientOriginalExtension();
            $s3Path = 'uploads/' . $type . '/videos/' . $videoName;

            if ($this->isS3Configured()) {
                Storage::disk('s3')->putFileAs('uploads/' . $type . '/videos', $video, $videoName, 'public');
                $videoUrl = Storage::disk('s3')->url($s3Path);
            } else {
                $video->move($destinationPath, $videoName);
                $videoUrl = asset('public/uploads/' . $type . '/videos/' . $videoName);
            }

            $uploadedVideos[] = [
                'original_name' => $originalName,
                'filename' => $videoName,
                'url' => $videoUrl,
                'size' => $size,
                'mime_type' => $mimeType,
            ];
        }

        return $uploadedVideos;
    }

    /**
     * Check if S3 credentials are set in configuration
     *
     * @return bool
     */
    public function isS3Configured(): bool
    {
        return !empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.secret'));
    }
}
