<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Upload single or multiple images
     * Accepts 'images' parameter which can be single file or array of files
     * Accepts 'type' parameter to determine folder structure (category or machinery)
     */
    public function uploadImage(Request $request)
    {
        try {
            if (!$request->hasFile('images')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please provide images.',
                ], 422);
            }

            $files = $request->file('images');
            $isMultiple = is_array($files);
            $type = $request->input('type', 'general');
            
            $allowedTypes = ['category', 'machinery'];
            if (!in_array($type, $allowedTypes)) {
                $type = 'general';
            }

            $filesArray = $isMultiple ? $files : [$files];

            $validator = Validator::make(
                $request->all(),
                [
                    'images' => $isMultiple ? 'required|array' : 'required',
                    'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:20480',
                    'type' => 'sometimes|in:category,machinery',
                ],
                [
                    'images.required' => 'Please provide at least one image.',
                    'images.array' => 'Images must be an array.',
                    'images.*.required' => 'Each image is required.',
                    'images.*.image' => 'Each file must be an image.',
                    'images.*.mimes' => 'Images must be of type: jpeg, png, jpg, gif, svg, webp.',
                    'images.*.max' => 'Each image may not be greater than 20MB.',
                    'type.in' => 'Type must be either category or machinery.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $disk = config('filesystems.default', 's3');
            $uploadedImages = [];

            foreach ($filesArray as $image) {
                $imageName = time() . '_' . Str::random(10) . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $relativePath = $type . '/images/' . $imageName;

                if ($disk === 's3') {
                    Storage::disk('s3')->putFileAs($type . '/images', $image, $imageName);

                    $awsUrl = env('AWS_URL');
                    if ($awsUrl) {
                        $imageUrl = rtrim($awsUrl, '/') . '/' . $relativePath;
                    } else {
                        $imageUrl = Storage::disk('s3')->url($relativePath);
                    }
                } else {
                    $destinationPath = public_path('uploads/' . $type . '/images');
                    if (!File::exists($destinationPath)) {
                        File::makeDirectory($destinationPath, 0755, true);
                    }
                    $image->move($destinationPath, $imageName);
                    $imageUrl = asset('public/uploads/' . $type . '/images/' . $imageName) . '?time=' . time();
                }

                $uploadedImages[] = [
                    'filename' => $imageName,
                    'url' => $imageUrl,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => count($uploadedImages) > 1 ? 'Images uploaded successfully' : 'Image uploaded successfully',
                'data' => [
                    'images' => $uploadedImages,
                    'count' => count($uploadedImages),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Upload single or multiple videos
     * Accepts 'videos' parameter which can be single file or array of files
     * Accepts 'type' parameter to determine folder structure (category or machinery)
     */
    public function uploadVideo(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            if (
                !$request->hasFile('videos') &&
                !$request->hasFile('videos.*')
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please provide videos.',
                ], 422);
            }

            $files = $request->file('videos');

            if (!is_array($files)) {
                $files = [$files];
            }

            $type = $request->input('type', 'general');

            $allowedTypes = ['category', 'machinery'];

            if (!in_array($type, $allowedTypes)) {
                $type = 'general';
            }

            $validator = Validator::make(
                [
                    'videos' => $files,
                    'type' => $type,
                ],
                [
                    'videos' => 'required|array',
                    'videos.*' => [
                        'required',
                        'file',
                        'max:204800',
                        'mimes:mp4,m4v,avi,mov,wmv,flv,webm,3gp',
                        'mimetypes:video/mp4,video/x-m4v,video/x-msvideo,video/quicktime,video/x-ms-wmv,video/x-flv,video/webm,video/3gpp,application/octet-stream',
                    ],
                    'type' => 'sometimes|in:category,machinery',
                ],
                [
                    'videos.required' => 'Please provide at least one video.',
                    'videos.array' => 'Videos must be an array.',
                    'videos.*.required' => 'Each video is required.',
                    'videos.*.mimes' => 'Videos must be of type: mp4, avi, mov, wmv, flv, webm, 3gp.',
                    'videos.*.mimetypes' => 'Invalid video format detected.',
                    'videos.*.max' => 'Each video may not be greater than 200MB.',
                    'type.in' => 'Type must be either category or machinery.',
                ]
            );

            if ($validator->fails()) {
                $debug = [];

                foreach ($files as $video) {
                    $debug[] = [
                        'name' => $video->getClientOriginalName(),
                        'extension' => $video->getClientOriginalExtension(),
                        'mime_type' => $video->getMimeType(),
                    ];
                }

                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                    'debug' => $debug,
                ], 422);
            }

            $disk = config('filesystems.default', 's3');
            $uploadedVideos = [];

            foreach ($files as $video) {
                $originalName = $video->getClientOriginalName();
                $size = $video->getSize();
                $mimeType = $video->getMimeType();

                $videoName = time() . '_' . Str::random(10) . '_' . uniqid() . '.' . $video->getClientOriginalExtension();
                $relativePath = $type . '/videos/' . $videoName;

                if ($disk === 's3') {
                    Storage::disk('s3')->putFileAs($type . '/videos', $video, $videoName);

                    $awsUrl = env('AWS_URL');
                    if ($awsUrl) {
                        $videoUrl = rtrim($awsUrl, '/') . '/' . $relativePath;
                    } else {
                        $videoUrl = Storage::disk('s3')->url($relativePath);
                    }
                } else {
                    $destinationPath = public_path('uploads/' . $type . '/videos');
                    if (!File::exists($destinationPath)) {
                        File::makeDirectory($destinationPath, 0755, true);
                    }
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

            return response()->json([
                'status' => true,
                'message' => count($uploadedVideos) > 1
                    ? 'Videos uploaded successfully'
                    : 'Video uploaded successfully',
                'data' => [
                    'videos' => $uploadedVideos,
                    'count' => count($uploadedVideos),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}
