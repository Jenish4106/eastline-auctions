<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
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
            $type = $request->input('type', 'general'); // Default to 'general' if no type provided
            
            // Validate type parameter
            $allowedTypes = ['category', 'machinery'];
            if (!in_array($type, $allowedTypes)) {
                $type = 'general'; // Fallback to general if invalid type
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

            $uploadedImages = [];
            $destinationPath = public_path('uploads/' . $type . '/images');
            
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            foreach ($filesArray as $image) {
                $originalName = $image->getClientOriginalName();
                $size = $image->getSize();
                $mimeType = $image->getMimeType();
                
                $imageName = time() . '_' . Str::random(10) . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move($destinationPath, $imageName);
                
                $imageUrl = asset('uploads/' . $type . '/images/' . $imageName);
                
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
            
            if (!$request->hasFile('videos')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please provide videos.',
                ], 422);
            }

            $files = $request->file('videos');
            $isMultiple = is_array($files);
            $type = $request->input('type', 'general'); // Default to 'general' if no type provided
            
            // Validate type parameter
            $allowedTypes = ['category', 'machinery'];
            if (!in_array($type, $allowedTypes)) {
                $type = 'general'; // Fallback to general if invalid type
            }

            $filesArray = $isMultiple ? $files : [$files];

            $validator = Validator::make(
                $request->all(),
                [
                    'videos' => $isMultiple ? 'required|array' : 'required',
                    'videos.*' => 'required|mimes:mp4,avi,mov,wmv,flv,webm,3gp|max:204800',
                    'type' => 'sometimes|in:category,machinery',
                ],
                [
                    'videos.required' => 'Please provide at least one video.',
                    'videos.array' => 'Videos must be an array.',
                    'videos.*.required' => 'Each video is required.',
                    'videos.*.mimes' => 'Videos must be of type: mp4, avi, mov, wmv, flv, webm, 3gp.',
                    'videos.*.max' => 'Each video may not be greater than 200MB.',
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
                $video->move($destinationPath, $videoName);
                
                $videoUrl = asset('uploads/' . $type . '/videos/' . $videoName);
                
                $uploadedVideos[] = [
                    'filename' => $videoName,
                    'url' => $videoUrl,
                ];
            }

            return response()->json([
                'status' => true,
                'message' => count($uploadedVideos) > 1 ? 'Videos uploaded successfully' : 'Video uploaded successfully',
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
