<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MachineryController extends Controller
{
    /**
     * Get all machinery with pagination and search
     */
    public function index(Request $request)
    {
        try {
            $search    = $request->input('search', '');
            $perPage   = $request->input('per_page', 10);
            $page      = $request->input('page', 1);
            $sortBy    = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = [
                'id','auction_id', 'category_id', 'make', 'model', 'year', 'weight',
                'working_hours', 'condition', 'fuel', 'serial_number',
                'buy_now_price', 'bid_start_price', 'bid_end_time',
                'description', 'offer', 'video_path', 'status',
                'created_at', 'updated_at',
            ];
            $allowedSortOrders = ['asc', 'desc'];

            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (! in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Machinery::with('category:id,category_name', 'images')->select([
                'id',
                'auction_id',
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
                'status',
                'created_at',
                'updated_at',
            ]);

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('auction_id', 'LIKE', "%{$search}%")
                        ->orWhere('make', 'LIKE', "%{$search}%")
                        ->orWhere('model', 'LIKE', "%{$search}%")
                        ->orWhere('year', 'LIKE', "%{$search}%")
                        ->orWhere('weight', 'LIKE', "%{$search}%")
                        ->orWhere('working_hours', 'LIKE', "%{$search}%")
                        ->orWhere('condition', 'LIKE', "%{$search}%")
                        ->orWhere('fuel', 'LIKE', "%{$search}%")
                        ->orWhere('serial_number', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhere('specification', 'LIKE', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->where('category_name', 'LIKE', "%{$search}%");
                        });
                });
            }

            $machinery = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $machineryWithUrls = $machinery->getCollection()->map(function ($item) {
                $images = $item->images->filter(function ($file) {
                    return $file->type === 'image';
                });

                $item->image_urls = $images->map(function ($image) {

                    $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                    if (file_exists($machineryImagePath)) {
                        return asset('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                    } else {
                        return null;
                    }
                })->filter()->values()->toArray();

                $videos = $item->images->filter(function ($file) {
                    return $file->type === 'video';
                });

                $item->video_urls = $videos->map(function ($video) {
                    $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                    if (file_exists($machineryVideoPath)) {
                        return asset('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                    } else {
                        return null;
                    }
                })->filter()->values()->toArray();

                unset($item->images);
                return $item;
            });

            if ($machineryWithUrls->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'No machinery found',
                ], 200);
            }

            return response()->json([
                'status'     => true,
                'message'    => 'Machinery retrieved successfully',
                'data'       => $machineryWithUrls,
                'pagination' => [
                    'current_page' => $machinery->currentPage(),
                    'last_page'    => $machinery->lastPage(),
                    'per_page'     => $machinery->perPage(),
                    'total'        => $machinery->total(),
                    'from'         => $machinery->firstItem(),
                    'to'           => $machinery->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Get single machinery by ID
     */
    public function show(Request $request)
    {
        try {
            $id        = $request->id;
            $machinery = Machinery::with('category:id,category_name', 'images')->find($id);

            if (! $machinery) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $images = $machinery->images->filter(function ($file) {
                return $file->type === 'image';
            });

            $machinery->image_urls = $images->map(function ($image) {

                $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                if (file_exists($machineryImagePath)) {
                    return asset('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                } else {
                    return null;
                }
            })->filter()->values()->toArray();

            $videos = $machinery->images->filter(function ($file) {
                return $file->type === 'video';
            });

            $machinery->video_urls = $videos->map(function ($video) {
                $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                if (file_exists($machineryVideoPath)) {
                    return asset('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                } else {
                    return null;
                }
            })->filter()->values()->toArray();

            unset($machinery->images);

            return response()->json([
                'status'  => true,
                'message' => 'Machinery retrieved successfully',
                'data'    => $machinery,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
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
                'category_id'     => 'required|exists:categories,id',
                'make'            => 'required|string|max:255',
                'model'           => 'required|string|max:255',
                'year'            => 'required|string|max:4',
                'weight'          => 'required|string|max:50',
                'working_hours'   => 'required|string|max:50',
                'condition'       => 'required|string|max:50',
                'fuel'            => 'required|string|max:50',
                'serial_number'   => 'nullable|string|max:100',
                'buy_now_price'   => 'required|numeric|min:0',
                'bid_start_price' => 'nullable|numeric|min:0',
                'bid_end_time'    => 'required|date_format:Y-m-d H:i:s',
                'description'     => 'nullable|string',
                'specification'   => 'nullable',
                'offer'           => 'nullable|string|max:255',
                'image_urls'      => 'required|array',
                'image_urls.*'    => 'required|url',
                'video_urls'      => 'nullable|array',
                'video_urls.*'    => 'nullable|url',
                'status'          => 'required|in:1,2,3',
            ], [
                'category_id.required'     => 'The category field is required.',
                'category_id.exists'       => 'The selected category does not exist.',
                'make.required'            => 'The make field is required.',
                'model.required'           => 'The model field is required.',
                'year.required'            => 'The year field is required.',
                'weight.required'          => 'The weight field is required.',
                'working_hours.required'   => 'The working hours field is required.',
                'condition.required'       => 'The condition field is required.',
                'fuel.required'            => 'The fuel field is required.',
                'buy_now_price.required'   => 'The buy now price field is required.',
                'bid_end_time.required'    => 'The bid end time field is required.',
                'bid_end_time.date_format' => 'The bid end time must be in Y-m-d H:i:s format.',
                'image_urls.required'      => 'At least one image URL is required.',
                'image_urls.array'         => 'Image URLs must be an array.',
                'image_urls.*.url'         => 'Each image URL must be a valid URL.',
                'video_urls.array'         => 'Video URLs must be an array.',
                'video_urls.*.url'         => 'Each video URL must be a valid URL.',
                'status.required'          => 'The status field is required.',
                'status.in'                => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $machinery                  = new Machinery();
            $machinery->category_id     = $request->category_id;
            $machinery->make            = $request->make;
            $machinery->model           = $request->model;
            $machinery->year            = $request->year;
            $machinery->weight          = $request->weight;
            $machinery->working_hours   = $request->working_hours;
            $machinery->condition       = $request->condition;
            $machinery->fuel            = $request->fuel;
            $machinery->serial_number   = $request->serial_number;
            $machinery->buy_now_price   = $request->buy_now_price;
            $machinery->bid_start_price = $request->bid_start_price ?? ($request->buy_now_price * 0.9);
            $machinery->bid_end_time    = $request->bid_end_time;
            $machinery->description     = $request->description;
            $machinery->offer           = $request->offer;
            $machinery->status          = $request->status;

            if ($request->has('specification') && $request->specification !== null) {
                if (is_array($request->specification)) {
                    $machinery->specification = $request->specification;
                } elseif (is_string($request->specification)) {
                    $decoded                  = json_decode($request->specification, true);
                    $machinery->specification = json_last_error() === JSON_ERROR_NONE ? $decoded : $request->specification;
                }
            }

            $machinery->save();

            if ($request->has('video_urls') && $request->video_urls !== null) {
                $videoUrls = is_array($request->video_urls) ? $request->video_urls : [$request->video_urls];

                foreach ($videoUrls as $videoUrl) {
                    if (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                        $filename = basename(parse_url($videoUrl, PHP_URL_PATH));
                        MachineryFileManager::create([
                            'machinery_id' => $machinery->id,
                            'image_path'   => $filename,
                            'type'         => 'video',
                        ]);
                    }
                }
            }

            if ($request->has('image_urls') && is_array($request->image_urls)) {
                foreach ($request->image_urls as $index => $imageUrl) {
                    $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                    MachineryFileManager::create([
                        'machinery_id' => $machinery->id,
                        'image_path'   => $filename,
                        'type'         => 'image',
                    ]);
                }
            }

            $machinery->load('category', 'images');

            $images = $machinery->images->filter(function ($file) {
                return $file->type === 'image';
            });

            $machinery->image_urls = $images->map(function ($image) {

                $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                if (file_exists($machineryImagePath)) {
                    return asset('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                } else {
                    return null;
                }
            })->filter()->values()->toArray();

            $videos = $machinery->images->filter(function ($file) {
                return $file->type === 'video';
            });

            $machinery->video_urls = $videos->map(function ($video) {
                $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                if (file_exists($machineryVideoPath)) {
                    return asset('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                } else {
                    return null;
                }
            })->filter()->values()->toArray();

            unset($machinery->images);

            return response()->json([
                'status'  => true,
                'message' => 'Machinery created successfully',
                'data'    => $machinery,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Update machinery
     */
    public function update(Request $request)
    {
        try {
            $id        = $request->id;
            $machinery = Machinery::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'category_id'     => 'required|exists:categories,id',
                'make'            => 'required|string|max:255',
                'model'           => 'required|string|max:255',
                'year'            => 'required|string|max:4',
                'weight'          => 'required|string|max:50',
                'working_hours'   => 'required|string|max:50',
                'condition'       => 'required|string|max:50',
                'fuel'            => 'required|string|max:50',
                'serial_number'   => 'nullable|string|max:100',
                'buy_now_price'   => 'required|numeric|min:0',
                'bid_start_price' => 'nullable|numeric|min:0',
                'bid_end_time'    => 'required|date_format:Y-m-d H:i:s',
                'description'     => 'nullable|string',
                'specification'   => 'nullable',
                'offer'           => 'nullable|string|max:255',
                'image_urls'      => 'sometimes|array',
                'image_urls.*'    => 'required|url',
                'video_urls'      => 'nullable|array',
                'video_urls.*'    => 'nullable|url',
                'status'          => 'required|in:1,2,3',
            ], [
                'category_id.required'     => 'The category field is required.',
                'category_id.exists'       => 'The selected category does not exist.',
                'make.required'            => 'The make field is required.',
                'model.required'           => 'The model field is required.',
                'year.required'            => 'The year field is required.',
                'weight.required'          => 'The weight field is required.',
                'working_hours.required'   => 'The working hours field is required.',
                'condition.required'       => 'The condition field is required.',
                'fuel.required'            => 'The fuel field is required.',
                'buy_now_price.required'   => 'The buy now price field is required.',
                'bid_end_time.required'    => 'The bid end time field is required.',
                'bid_end_time.date_format' => 'The bid end time must be in Y-m-d H:i:s format.',
                'image_urls.array'         => 'Image URLs must be an array.',
                'image_urls.*.url'         => 'Each image URL must be a valid URL.',
                'video_urls.array'         => 'Video URLs must be an array.',
                'video_urls.*.url'         => 'Each video URL must be a valid URL.',
                'status.required'          => 'The status field is required.',
                'status.in'                => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $machinery->category_id     = $request->category_id;
            $machinery->make            = $request->make;
            $machinery->model           = $request->model;
            $machinery->year            = $request->year;
            $machinery->weight          = $request->weight;
            $machinery->working_hours   = $request->working_hours;
            $machinery->condition       = $request->condition;
            $machinery->fuel            = $request->fuel;
            $machinery->serial_number   = $request->serial_number;
            $machinery->buy_now_price   = $request->buy_now_price;
            $machinery->bid_start_price = $request->bid_start_price ?? ($request->buy_now_price * 0.9);
            $machinery->bid_end_time    = $request->bid_end_time;
            $machinery->description     = $request->description;
            $machinery->offer           = $request->offer;
            $machinery->status          = $request->status;

            if ($request->has('specification') && $request->specification !== null) {
                if (is_array($request->specification)) {
                    $machinery->specification = $request->specification;
                } elseif (is_string($request->specification)) {
                    $decoded                  = json_decode($request->specification, true);
                    $machinery->specification = json_last_error() === JSON_ERROR_NONE ? $decoded : $request->specification;
                }
            }

            if ($request->has('video_urls') && $request->video_urls !== null) {
                $existingVideos = $machinery->images()->where('type', 'video')->get();

                $incomingVideoFilenames = [];
                $videoUrls              = is_array($request->video_urls) ? $request->video_urls : [$request->video_urls];

                foreach ($videoUrls as $videoUrl) {
                    if (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                        $incomingVideoFilenames[] = basename(parse_url($videoUrl, PHP_URL_PATH));
                    }
                }

                foreach ($existingVideos as $existingVideo) {
                    $existingFilename = $existingVideo->image_path;
                    if (! in_array($existingFilename, $incomingVideoFilenames)) {
                        $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($existingFilename, '/'));

                        if (file_exists($machineryVideoPath)) {
                            unlink($machineryVideoPath);
                        }
                    }
                }

                $machinery->images()->where('type', 'video')->whereNotIn('image_path', $incomingVideoFilenames)->delete();

                foreach ($incomingVideoFilenames as $filename) {
                    $existingVideo = $machinery->images()->where('type', 'video')->where('image_path', $filename)->first();

                    if (! $existingVideo) {
                        MachineryFileManager::create([
                            'machinery_id' => $machinery->id,
                            'image_path'   => $filename,
                            'type'         => 'video',
                        ]);
                    }
                }
            }

            $machinery->save();

            if ($request->has('image_urls') && is_array($request->image_urls)) {

                $existingImages = $machinery->images()->where('type', 'image')->get();

                $incomingFilenames = [];
                foreach ($request->image_urls as $imageUrl) {
                    $incomingFilenames[] = basename(parse_url($imageUrl, PHP_URL_PATH));
                }

                foreach ($existingImages as $existingImage) {
                    $existingFilename = $existingImage->image_path;
                    if (! in_array($existingFilename, $incomingFilenames)) {

                        $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($existingFilename, '/'));

                        if (file_exists($machineryImagePath)) {
                            unlink($machineryImagePath);
                        }
                    }
                }

                $machinery->images()->where('type', 'image')->whereNotIn('image_path', $incomingFilenames)->delete();

                foreach ($request->image_urls as $imageUrl) {
                    $filename      = basename(parse_url($imageUrl, PHP_URL_PATH));
                    $existingImage = $machinery->images()->where('type', 'image')->where('image_path', $filename)->first();

                    if (! $existingImage) {

                        MachineryFileManager::create([
                            'machinery_id' => $machinery->id,
                            'image_path'   => $filename,
                            'type'         => 'image',
                        ]);
                    }
                }
            }

            $machinery->load('category', 'images');

            $images = $machinery->images->filter(function ($file) {
                return $file->type === 'image';
            });

            $machinery->image_urls = $images->map(function ($image) {
                $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                if (file_exists($machineryImagePath)) {
                    return asset('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                } else {
                    return null;
                }
            })->filter()->values()->toArray();

            $videos = $machinery->images->filter(function ($file) {
                return $file->type === 'video';
            });

            $machinery->video_urls = $videos->map(function ($video) {
                $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                if (file_exists($machineryVideoPath)) {
                    return asset('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                } else {
                    return null;
                }
            })->filter()->values()->toArray();

            unset($machinery->images);

            return response()->json([
                'status'  => true,
                'message' => 'Machinery updated successfully',
                'data'    => $machinery,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Machinery not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Delete machinery
     */
    public function delete(Request $request)
    {
        try {
            $id        = $request->id;
            $machinery = Machinery::find($id);

            if (! $machinery) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $files = $machinery->images;
            foreach ($files as $file) {
                if ($file->type === 'video') {

                    $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($file->image_path, '/'));

                    if (file_exists($machineryVideoPath)) {
                        unlink($machineryVideoPath);
                    }
                } else {

                    $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($file->image_path, '/'));

                    if (file_exists($machineryImagePath)) {
                        unlink($machineryImagePath);
                    }
                }
            }

            $machinery->images()->delete();

            $machinery->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Machinery deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}
