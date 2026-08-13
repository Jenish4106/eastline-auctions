<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UploadController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Upload single or multiple images
     * Accepts 'images' parameter which can be single file or array of files
     * Accepts 'type' parameter to determine folder structure (category or machinery)
     */
    public function uploadImage(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            set_time_limit(300);

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
                    'images' => $isMultiple ? 'required|array|max:100' : 'required',
                    'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:20480',
                    'type' => 'sometimes|in:category,machinery',
                ],
                [
                    'images.required' => 'Please provide at least one image.',
                    'images.array' => 'Images must be an array.',
                    'images.max' => 'You cannot upload more than 100 images at once.',
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

            $uploadedImages = $this->uploadService->uploadImages($filesArray, $type);

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

            $uploadedVideos = $this->uploadService->uploadVideos($files, $type);

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
