<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use App\Models\MachineryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class MachineryController extends Controller
{
    /**
     * Get all machinery with pagination and search
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $query = Machinery::with('category', 'images')->select([
                'id',
                'category_id',
                'make',
                'model',
                'year',
                'weight',
                'working_hours',
                'condition',
                'fuel',
                'serial_number',
                'buy_now_price',
                'bid_start_price',
                'bid_end_time',
                'description',
                'specification',
                'offer',
                'video_path',
                'status',
                'created_at',
                'updated_at'
            ]);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('make', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%")
                      ->orWhere('year', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            $machinery = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

            $machineryWithUrls = $machinery->getCollection()->map(function ($item) {
                // Add image URLs
                $item->image_urls = $item->images->map(function ($image) {
                    if (filter_var($image->image_path, FILTER_VALIDATE_URL)) {
                        return $image->image_path;
                    }
                    return asset('machinery/' . ltrim($image->image_path, '/'));
                })->toArray();

                // Add video URL
                if ($item->video_path) {
                    if (filter_var($item->video_path, FILTER_VALIDATE_URL)) {
                        $item->video_url = $item->video_path;
                    } else {
                        $item->video_url = asset('machinery/' . ltrim($item->video_path, '/'));
                    }
                } else {
                    $item->video_url = null;
                }

                // Remove images relationship from response
                unset($item->images);
                return $item;
            });

            return response()->json([
                'status' => true,
                'message' => 'Machinery retrieved successfully',
                'data' => $machineryWithUrls,
                'pagination' => [
                    'current_page' => $machinery->currentPage(),
                    'last_page' => $machinery->lastPage(),
                    'per_page' => $machinery->perPage(),
                    'total' => $machinery->total(),
                    'from' => $machinery->firstItem(),
                    'to' => $machinery->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single machinery by ID
     */
    public function show(Request $request)
    {
        try {
            $id = $request->id;
            $machinery = Machinery::with('category', 'images')->find($id);

            if (!$machinery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            // Add image URLs
            $machinery->image_urls = $machinery->images->map(function ($image) {
                if (filter_var($image->image_path, FILTER_VALIDATE_URL)) {
                    return $image->image_path;
                }
                return asset('machinery/' . ltrim($image->image_path, '/'));
            })->toArray();

            // Add video URL
            if ($machinery->video_path) {
                if (filter_var($machinery->video_path, FILTER_VALIDATE_URL)) {
                    $machinery->video_url = $machinery->video_path;
                } else {
                    $machinery->video_url = asset('machinery/' . ltrim($machinery->video_path, '/'));
                }
            } else {
                $machinery->video_url = null;
            }

            // Remove images relationship from response
            unset($machinery->images);

            return response()->json([
                'status' => true,
                'message' => 'Machinery retrieved successfully',
                'data' => $machinery,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Create new machinery
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
                'make' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'year' => 'required|string|max:4',
                'weight' => 'required|string|max:50',
                'working_hours' => 'required|string|max:50',
                'condition' => 'required|string|max:50',
                'fuel' => 'required|string|max:50',
                'serial_number' => 'nullable|string|max:100',
                'buy_now_price' => 'required|numeric|min:0',
                'bid_start_price' => 'nullable|numeric|min:0',
                'bid_end_time' => 'required|date',
                'description' => 'nullable|string',
                'specification' => 'nullable',
                'offer' => 'nullable|string|max:255',
                'image_urls' => 'required|array',
                'image_urls.*' => 'required|url',
                'video_url' => 'nullable',
                'status' => 'required|in:1,2,3',
            ], [
                'category_id.required' => 'The category field is required.',
                'category_id.exists' => 'The selected category does not exist.',
                'make.required' => 'The make field is required.',
                'model.required' => 'The model field is required.',
                'year.required' => 'The year field is required.',
                'weight.required' => 'The weight field is required.',
                'working_hours.required' => 'The working hours field is required.',
                'condition.required' => 'The condition field is required.',
                'fuel.required' => 'The fuel field is required.',
                'buy_now_price.required' => 'The buy now price field is required.',
                'bid_end_time.required' => 'The bid end time field is required.',
                'image_urls.required' => 'At least one image URL is required.',
                'image_urls.array' => 'Image URLs must be an array.',
                'image_urls.*.url' => 'Each image URL must be a valid URL.',
                'status.required' => 'The status field is required.',
                'status.in' => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $machinery = new Machinery();
            $machinery->category_id = $request->category_id;
            $machinery->make = $request->make;
            $machinery->model = $request->model;
            $machinery->year = $request->year;
            $machinery->weight = $request->weight;
            $machinery->working_hours = $request->working_hours;
            $machinery->condition = $request->condition;
            $machinery->fuel = $request->fuel;
            $machinery->serial_number = $request->serial_number;
            $machinery->buy_now_price = $request->buy_now_price;
            $machinery->bid_start_price = $request->bid_start_price ?? ($request->buy_now_price * 0.9);
            $machinery->bid_end_time = $request->bid_end_time;
            $machinery->description = $request->description;
            $machinery->offer = $request->offer;
            $machinery->status = $request->status;

            // Handle specification (accept array or JSON string)
            if ($request->has('specification') && $request->specification !== null) {
                if (is_array($request->specification)) {
                    $machinery->specification = $request->specification;
                } elseif (is_string($request->specification)) {
                    $decoded = json_decode($request->specification, true);
                    $machinery->specification = json_last_error() === JSON_ERROR_NONE ? $decoded : $request->specification;
                }
            }

            // Handle video URL (accept string or array - if array, take first item)
            if ($request->has('video_url') && $request->video_url !== null) {
                if (is_array($request->video_url) && count($request->video_url) > 0) {
                    // Validate first URL in array
                    $firstUrl = $request->video_url[0];
                    if (filter_var($firstUrl, FILTER_VALIDATE_URL)) {
                        $machinery->video_path = $firstUrl;
                    }
                } elseif (is_string($request->video_url) && filter_var($request->video_url, FILTER_VALIDATE_URL)) {
                    $machinery->video_path = $request->video_url;
                }
            }

            $machinery->save();

            // Store image URLs
            if ($request->has('image_urls') && is_array($request->image_urls)) {
                foreach ($request->image_urls as $index => $imageUrl) {
                    // Extract filename from URL
                    $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                    MachineryImage::create([
                        'machinery_id' => $machinery->id,
                        'image_path' => $imageUrl, // Store full URL
                        'sort_order' => $index
                    ]);
                }
            }

            // Load relationships for response
            $machinery->load('category', 'images');

            // Format response
            $machinery->image_urls = $machinery->images->pluck('image_path')->toArray();
            $machinery->video_url = $machinery->video_path;
            unset($machinery->images);

            return response()->json([
                'status' => true,
                'message' => 'Machinery created successfully',
                'data' => $machinery,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update machinery
     */
    public function update(Request $request)
    {
        try {
            $id = $request->id;
            $machinery = Machinery::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
                'make' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'year' => 'required|string|max:4',
                'weight' => 'required|string|max:50',
                'working_hours' => 'required|string|max:50',
                'condition' => 'required|string|max:50',
                'fuel' => 'required|string|max:50',
                'serial_number' => 'nullable|string|max:100',
                'buy_now_price' => 'required|numeric|min:0',
                'bid_start_price' => 'nullable|numeric|min:0',
                'bid_end_time' => 'required|date',
                'description' => 'nullable|string',
                'specification' => 'nullable',
                'offer' => 'nullable|string|max:255',
                'image_urls' => 'sometimes|array',
                'image_urls.*' => 'required|url',
                'video_url' => 'nullable',
                'status' => 'required|in:1,2,3',
            ], [
                'category_id.required' => 'The category field is required.',
                'category_id.exists' => 'The selected category does not exist.',
                'make.required' => 'The make field is required.',
                'model.required' => 'The model field is required.',
                'year.required' => 'The year field is required.',
                'weight.required' => 'The weight field is required.',
                'working_hours.required' => 'The working hours field is required.',
                'condition.required' => 'The condition field is required.',
                'fuel.required' => 'The fuel field is required.',
                'buy_now_price.required' => 'The buy now price field is required.',
                'bid_end_time.required' => 'The bid end time field is required.',
                'image_urls.array' => 'Image URLs must be an array.',
                'image_urls.*.url' => 'Each image URL must be a valid URL.',
                'status.required' => 'The status field is required.',
                'status.in' => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $machinery->category_id = $request->category_id;
            $machinery->make = $request->make;
            $machinery->model = $request->model;
            $machinery->year = $request->year;
            $machinery->weight = $request->weight;
            $machinery->working_hours = $request->working_hours;
            $machinery->condition = $request->condition;
            $machinery->fuel = $request->fuel;
            $machinery->serial_number = $request->serial_number;
            $machinery->buy_now_price = $request->buy_now_price;
            $machinery->bid_start_price = $request->bid_start_price ?? ($request->buy_now_price * 0.9);
            $machinery->bid_end_time = $request->bid_end_time;
            $machinery->description = $request->description;
            $machinery->offer = $request->offer;
            $machinery->status = $request->status;

            // Handle specification (accept array or JSON string)
            if ($request->has('specification') && $request->specification !== null) {
                if (is_array($request->specification)) {
                    $machinery->specification = $request->specification;
                } elseif (is_string($request->specification)) {
                    $decoded = json_decode($request->specification, true);
                    $machinery->specification = json_last_error() === JSON_ERROR_NONE ? $decoded : $request->specification;
                }
            }

            // Handle video URL (accept string or array - if array, take first item)
            if ($request->has('video_url') && $request->video_url !== null) {
                if (is_array($request->video_url) && count($request->video_url) > 0) {
                    // Validate first URL in array
                    $firstUrl = $request->video_url[0];
                    if (filter_var($firstUrl, FILTER_VALIDATE_URL)) {
                        $machinery->video_path = $firstUrl;
                    }
                } elseif (is_string($request->video_url) && filter_var($request->video_url, FILTER_VALIDATE_URL)) {
                    $machinery->video_path = $request->video_url;
                }
            }

            $machinery->save();

            // Update images if provided
            if ($request->has('image_urls') && is_array($request->image_urls)) {
                // Delete existing images
                $machinery->images()->delete();

                // Create new images
                foreach ($request->image_urls as $index => $imageUrl) {
                    MachineryImage::create([
                        'machinery_id' => $machinery->id,
                        'image_path' => $imageUrl, // Store full URL
                        'sort_order' => $index
                    ]);
                }
            }

            // Load relationships for response
            $machinery->load('category', 'images');

            // Format response
            $machinery->image_urls = $machinery->images->pluck('image_path')->toArray();
            $machinery->video_url = $machinery->video_path;
            unset($machinery->images);

            return response()->json([
                'status' => true,
                'message' => 'Machinery updated successfully',
                'data' => $machinery,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Machinery not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete machinery
     */
    public function delete(Request $request)
    {
        try {
            $id = $request->id;
            $machinery = Machinery::find($id);

            if (!$machinery) {
                return response()->json([
                    'status' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            // Delete associated images (images are stored as URLs now, so just delete records)
            $machinery->images()->delete();

            $machinery->delete();

            return response()->json([
                'status' => true,
                'message' => 'Machinery deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}

