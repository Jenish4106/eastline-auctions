<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use Carbon\Carbon;
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
                'bid_won_date',
                'description',
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
                        ->orWhereRaw("CONCAT_WS(' ', year, make, model) LIKE ?", ["%{$search}%"])
                        ->orWhere('weight', 'LIKE', "%{$search}%")
                        ->orWhere('working_hours', 'LIKE', "%{$search}%")
                        ->orWhere('condition', 'LIKE', "%{$search}%")
                        ->orWhere('fuel', 'LIKE', "%{$search}%")
                        ->orWhere('serial_number', 'LIKE', "%{$search}%")
                        ->orWhere('buy_now_price', 'LIKE', "%{$search}%")
                        ->orWhere('bid_start_price', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhereHas('category', function ($query) use ($search) {
                            $query->where('category_name', 'LIKE', "%{$search}%");
                        });
                        
                    $statusMap = ['draft' => 0, 'publish' => 1, 'sold' => 2];
                    foreach ($statusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('status', $value);
                        }
                    }
                });
            }

            $machinery = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $machineryWithUrls = $machinery->getCollection()->map(function ($item) {
                $images = $item->images->filter(function ($file) {
                    return $file->type === 'image';
                });

                if ($images->count() > 0) {
                    $item->image_urls = $images->map(function ($image) {
                        $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                        $apiPublicImagePath = base_path('api/public/uploads/machinery/images/' . ltrim($image->image_path, '/'));
                        if (file_exists($machineryImagePath) || file_exists($apiPublicImagePath)) {
                            return asset('public/uploads/machinery/images/' . ltrim($image->image_path, '/'));
                        } else {
                            return asset('public/uploads/defaults/default-machine.png');
                        }
                    })->filter()->values()->toArray();
                } else {
                    $item->image_urls = [asset('public/uploads/defaults/default-machine.png')];
                }

                $videos = $item->images->filter(function ($file) {
                    return $file->type === 'video';
                });

                if ($videos->count() > 0) {
                    $item->video_urls = $videos->map(function ($video) {
                        $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                        $apiPublicVideoPath = base_path('api/public/uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                        if (file_exists($machineryVideoPath) || file_exists($apiPublicVideoPath)) {
                            return asset('public/uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                        } else {
                            return asset('public/uploads/defaults/default-machine.mp4');
                        }
                    })->filter()->values()->toArray();
                } else {
                    $item->video_urls = [asset('public/uploads/defaults/default-machine.mp4')];
                }

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

            if ($images->count() > 0) {
                $machinery->image_urls = $images->map(function ($image) {
                    $machineryImagePath = public_path('uploads/machinery/images/' . ltrim($image->image_path, '/'));
                    $apiPublicImagePath = base_path('api/public/uploads/machinery/images/' . ltrim($image->image_path, '/'));
                    if (file_exists($machineryImagePath) || file_exists($apiPublicImagePath)) {
                        return asset('public/uploads/machinery/images/' . ltrim($image->image_path, '/'));
                    } else {
                        return asset('public/uploads/defaults/default-machine.png');
                    }
                })->filter()->values()->toArray();
            } else {
                $machinery->image_urls = [asset('public/uploads/defaults/default-machine.png')];
            }

            $videos = $machinery->images->filter(function ($file) {
                return $file->type === 'video';
            });

            if ($videos->count() > 0) {
                $machinery->video_urls = $videos->map(function ($video) {
                    $machineryVideoPath = public_path('uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                    $apiPublicVideoPath = base_path('api/public/uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                    if (file_exists($machineryVideoPath) || file_exists($apiPublicVideoPath)) {
                        return asset('public/uploads/machinery/videos/' . ltrim($video->image_path, '/'));
                    } else {
                        return asset('public/uploads/defaults/default-machine.mp4');
                    }
                })->filter()->values()->toArray();
            } else {
                $machinery->video_urls = [asset('public/uploads/defaults/default-machine.mp4')];
            }

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
                'bid_end_days'    => 'required|integer|min:1',
                'description'     => 'nullable|string',
                'offer'           => 'nullable|max:255',
                'image_urls'      => 'required|array',
                'image_urls.*'    => 'required|url',
                'video_urls'      => 'nullable|array',
                'video_urls.*'    => 'nullable|url',
                'status'          => 'nullable|in:0,1,2',
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
                'bid_end_days.required'    => 'The bid end days field is required.',
                'bid_end_days.integer'     => 'The bid end days must be an integer.',
                'bid_end_days.min'         => 'The bid end days must be at least 1.',
                'image_urls.required'      => 'At least one image URL is required.',
                'image_urls.array'         => 'Image URLs must be an array.',
                'image_urls.*.url'         => 'Each image URL must be a valid URL.',
                'video_urls.array'         => 'Video URLs must be an array.',
                'video_urls.*.url'         => 'Each video URL must be a valid URL.',
                'status.in'                => 'The status must be 0 (Draft), 1 (Publish), or 2 (Sold).',
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
            $machinery->bid_start_time  = now();
            $machinery->bid_end_days    = $request->bid_end_days;
            $machinery->bid_end_time    = Carbon::parse($machinery->bid_start_time)->addDays($request->bid_end_days);
            $machinery->description     = $request->description;
            $machinery->offer           = $request->offer;
            $machinery->status          = $request->input('status', 0);

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
                return asset('public/uploads/machinery/images/' . ltrim($image->image_path, '/'));
            })->filter()->values()->toArray();

            $videos = $machinery->images->filter(function ($file) {
                return $file->type === 'video';
            });

            $machinery->video_urls = $videos->map(function ($video) {
                return asset('public/uploads/machinery/videos/' . ltrim($video->image_path, '/'));
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
                'id'              => 'required|exists:machinery,id',
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
                'bid_end_days'    => 'required|integer|min:1',
                'description'     => 'nullable|string',
                'offer'           => 'nullable|max:255',
                'image_urls'      => 'sometimes|array',
                'image_urls.*'    => 'required|url',
                'video_urls'      => 'nullable|array',
                'video_urls.*'    => 'nullable|url',
                'status'          => 'nullable|in:0,1,2',
            ], [
                'id.required'              => 'The machinery id field is required.',
                'id.exists'                => 'The selected machinery does not exist.',
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
                'bid_end_days.required'    => 'The bid end days field is required.',
                'bid_end_days.integer'     => 'The bid end days must be an integer.',
                'bid_end_days.min'         => 'The bid end days must be at least 1.',
                'image_urls.array'         => 'Image URLs must be an array.',
                'image_urls.*.url'         => 'Each image URL must be a valid URL.',
                'video_urls.array'         => 'Video URLs must be an array.',
                'video_urls.*.url'         => 'Each video URL must be a valid URL.',
                'status.in'                => 'The status must be 0 (Draft), 1 (Publish), or 2 (Sold).',
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
            $machinery->bid_end_days    = $request->bid_end_days;
            $machinery->bid_end_time    = Carbon::parse($machinery->bid_start_time)->addDays($request->bid_end_days);
            $machinery->description     = $request->description;
            $machinery->offer           = $request->offer;
            if ($request->has('status')) {
                $machinery->status = $request->status;
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
                return asset('public/uploads/machinery/images/' . ltrim($image->image_path, '/'));
            })->filter()->values()->toArray();

            $videos = $machinery->images->filter(function ($file) {
                return $file->type === 'video';
            });

            $machinery->video_urls = $videos->map(function ($video) {
                return asset('public/uploads/machinery/videos/' . ltrim($video->image_path, '/'));
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

    /**
     * Regenerate Auction ID for machinery
     */
    public function regenerateAuctionId(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'machinery_id' => 'required|exists:machinery,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors(),
                ], 400);
            }

            $machineryId = $request->machinery_id;
            $machinery = Machinery::find($machineryId);

            if (!$machinery) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            do {
                $timestamp = date('His');
                $auctionId = substr($timestamp . rand(10, 99), 0, 6);
            } while (Machinery::where('auction_id', $auctionId)->exists());

            $machinery->auction_id = $auctionId;
            $machinery->status          = 1;
            $machinery->won_user        = null;
            $machinery->bid_won_date    = null;
            $machinery->contract_status = 0;
            $machinery->bid_status      = '0';
            $machinery->bid_start_time  = Carbon::now();
            $machinery->bid_end_time    = Carbon::now()->addDays($machinery->bid_end_days);

            $machinery->save();

            return response()->json([
                'status'  => true,
                'message' => 'Auction ID regenerated successfully',
                'data'    => [
                    'machinery_id' => $machinery->id,
                    'new_auction_id' => $machinery->auction_id
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Update machinery status only
     */
    public function updateStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id'     => 'required|exists:machinery,id',
                'status' => 'required|in:0,1,2',
            ], [
                'id.required'     => 'The machinery id field is required.',
                'id.exists'       => 'The selected machinery does not exist.',
                'status.required' => 'The status field is required.',
                'status.in'       => 'The status must be 0 (Draft), 1 (Publish), or 2 (Sold).',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $machinery = Machinery::find($request->id);

            if (! $machinery) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $machinery->status = (int) $request->status;
            $machinery->save();

            return response()->json([
                'status'  => true,
                'message' => 'Machinery status updated successfully',
                'data'    => [
                    'id' => $machinery->id,
                    'status' => $machinery->status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

}
