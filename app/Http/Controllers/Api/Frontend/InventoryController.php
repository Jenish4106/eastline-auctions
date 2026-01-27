<?php
namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Machinery;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function getCategoryList()
    {
        $categories = Category::all();

        $categoriesWithImages = $categories->map(function ($category) {
            if ($category->image) {
                $imageArray = json_decode($category->image, true);
                if (is_array($imageArray)) {
                    $imageUrls = [];
                    foreach ($imageArray as $filename) {
                        $categoryImagePath = public_path('uploads/category/images/' . $filename);
                        if (file_exists($categoryImagePath)) {
                            $imageUrls[] = asset('uploads/category/images/' . $filename);
                        }
                    }
                    $category->image_url = !empty($imageUrls) ? $imageUrls[0] : null;
                } else {
                    $categoryImagePath = public_path('uploads/category/images/' . $category->image);
                    if (file_exists($categoryImagePath)) {
                        $category->image_url = asset('uploads/category/images/' . $category->image);
                    } else {
                        $category->image_url = null;
                    }
                }
            } else {
                $category->image_url = null;
            }

            $activeMachineryCount = Machinery::where('category_id', $category->id)
                ->where('bid_status', '!=', '2')
                ->where('bid_status', '!=', 2)
                ->where('bid_end_time', '>', now())
                ->count();

            $category->machinery_count = $activeMachineryCount;

            unset($category->image);
            return $category;
        });

        return response()->json([
            'success' => true,
            'data'    => $categoriesWithImages,
        ], 200);
    }

    public function getMachineryByCategory(Request $request)
    {
        $categoryName = $request->input('categoryName');
        $fromYear = $request->input('from_year');
        $toYear = $request->input('to_year');
        $sortBy = $request->input('sort_by', 'newest');
        $search = $request->input('search', '');
        $make = $request->input('make', '');
        $model = $request->input('model', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        if (is_array($categoryName)) {
            $categories = Category::whereIn('category_name', $categoryName)->get();
            if ($categories->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categories not found',
                ], 404);
            }
        } else {
            $category = Category::where('category_name', $categoryName)->first();
            if (! $category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                ], 404);
            }
        }

        if (is_array($categoryName)) {
            $machineryQuery = Machinery::whereIn('category_id', $categories->pluck('id'));
        } else {
            $machineryQuery = Machinery::where('category_id', $category->id);
        }

        if (!empty($search)) {
            $machineryQuery->where(function($query) use ($search) {
                $query->where('make', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%")
                      ->orWhere('year', 'LIKE', "%{$search}%")
                      ->orWhere('working_hours', 'LIKE', "%{$search}%")
                      ->orWhere('condition', 'LIKE', "%{$search}%")
                      ->orWhere('fuel', 'LIKE', "%{$search}%")
                      ->orWhere('serial_number', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhereHas('category', function($q) use ($search) {
                          $q->where('category_name', 'LIKE', "%{$search}%");
                      });
            });
        }

        if (!empty($make)) {
            $machineryQuery->where('make', 'LIKE', "%{$make}%");
        }

        if (!empty($model)) {
            $machineryQuery->where('model', 'LIKE', "%{$model}%");
        }

        if ($fromYear && $toYear) {
            $machineryQuery->whereBetween('year', [$fromYear, $toYear]);
        } elseif ($fromYear) {
            $machineryQuery->where('year', '>=', $fromYear);
        } elseif ($toYear) {
            $machineryQuery->where('year', '<=', $toYear);
        }

        switch ($sortBy) {
            case 'ending_soon':
                // Ending Time: Sooner to Later
                $machineryQuery->orderBy('bid_end_time', 'asc');
                break;
            case 'ending_late':
                // Ending Time: Later to Sooner
                $machineryQuery->orderBy('bid_end_time', 'desc');
                break;
            case 'price_low':
                // Price: Low to High
                $machineryQuery->orderBy('buy_now_price', 'asc');
                break;
            case 'price_high':
                // Price: High to Low
                $machineryQuery->orderBy('buy_now_price', 'desc');
                break;
            case 'newest':
            default:
                // Newest Added (default)
                $machineryQuery->orderBy('created_at', 'desc');
                break;
        }

        $machineryList = $machineryQuery
            ->with(['category:id,category_name'])
            ->paginate($perPage, [
                'id',
                'auction_id',
                'working_hours',
                'buy_now_price',
                'bid_start_price',
                'category_id',
                'is_purchase',
                'year',
                'make',
                'model',
                'bid_end_time',
                'created_at'
            ], 'page', $page);

        $machineryIds = $machineryList->getCollection()->pluck('id')->toArray();
        $images = \App\Models\MachineryFileManager::whereIn('machinery_id', $machineryIds)
            ->where('type', 'image')
            ->orderBy('id')
            ->get();

        $imagesByMachineryId = $images->groupBy('machinery_id');
        $machineryWithImages = $machineryList->getCollection()->map(function ($machinery) use ($imagesByMachineryId) {
            $year = $machinery->year ?? '';
            $make = $machinery->make ?? '';
            $model = $machinery->model ?? '';
            $workingHours = $machinery->working_hours ?? '';

            $machinery->name = trim("$year $make $model");
            $machinery->working_hours = $workingHours;

            $machinery->category = $machinery->category ? $machinery->category->category_name : null;

            if ($machinery->bid_end_time) {
                $bidEndTime = new \DateTime($machinery->bid_end_time);
                $currentTime = new \DateTime();
                $machinery->is_view = $bidEndTime > $currentTime ? 1 : 0;
            } else {
                $machinery->is_view = 0;
            }

            $machineryImages = $imagesByMachineryId->get($machinery->id, collect());

            if ($machineryImages && $machineryImages->count() > 0) {
                $firstImage = $machineryImages->first();
                if ($firstImage && $firstImage->type === 'image') {
                    $machineryImagePath = public_path('uploads/machinery/images/' . $firstImage->image_path);
                    if (file_exists($machineryImagePath)) {
                        $machinery->first_image_url = asset('uploads/machinery/images/' . $firstImage->image_path);
                    } else {
                        $machinery->first_image_url = null;
                    }
                } else {
                    $machinery->first_image_url = null;
                }
            } else {
                $machinery->first_image_url = null;
            }

            return $machinery;
        });

        if ($machineryWithImages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No machinery found in the specified category(s)',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $machineryWithImages,
            'pagination' => [
                'current_page' => $machineryList->currentPage(),
                'last_page' => $machineryList->lastPage(),
                'per_page' => $machineryList->perPage(),
                'total' => $machineryList->total(),
                'from' => $machineryList->firstItem(),
                'to' => $machineryList->lastItem(),
            ]
        ], 200);
    }

    public function getMachineryDetails(Request $request)
    {
        $category = $request->input('category');
        $make = $request->input('make');
        $model = $request->input('model');
        $workingHours = $request->input('working_hours');

        $machineryQuery = Machinery::with('category:id,category_name', 'images', 'bids');

        if ($category) {
            $category = Category::where('category_name', $category)->first();
            if ($category) {
                $machineryQuery->where('category_id', $category->id);
            }
        }

        if ($make) {
            $machineryQuery->where('make', $make);
        }

        if ($model) {
            $machineryQuery->where('model', $model);
        }

        if ($workingHours) {
            $machineryQuery->where('working_hours', $workingHours);
        }

        $machinery = $machineryQuery->first();

        if (! $machinery) {
            return response()->json([
                'success' => false,
                'message' => 'Machinery not found',
            ], 404);
        }

        $year = $machinery->year ?? '';
        $make = $machinery->make ?? '';
        $model = $machinery->model ?? '';
        $machinery->name = trim("$year $make $model");

        $highestBid = $machinery->bids->max('amount');
        $machinery->current_bid = $highestBid ?: $machinery->bid_start_price;

        $existingOffer = is_numeric($machinery->offer) ? (int)$machinery->offer : 0;
        $bidCount = $machinery->bids->count();
        $machinery->offer = $existingOffer + $bidCount;

        if ($machinery->images) {
            $machinery->images = $machinery->images->map(function ($image) {
                if ($image->type === 'video') {
                    $machineryFilePath = public_path('uploads/machinery/videos/' . $image->image_path);
                    if (file_exists($machineryFilePath)) {
                        $image->full_url = asset('uploads/machinery/videos/' . $image->image_path);
                    } else {
                        $image->full_url = null;
                    }
                } else {
                    $machineryFilePath = public_path('uploads/machinery/images/' . $image->image_path);
                    if (file_exists($machineryFilePath)) {
                        $image->full_url = asset('uploads/machinery/images/' . $image->image_path);
                    } else {
                        $image->full_url = null;
                    }
                }
                return $image;
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $machinery,
        ], 200);
    }

    public function getMakesOrModels(Request $request)
    {
        $type = $request->input('type');

        if (!$type || !in_array($type, ['make', 'model'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid type parameter. Must be make or model.',
            ], 400);
        }

        if ($type === 'make') {
            $results = Machinery::select('make')
                ->whereNotNull('make')
                ->where('make', '!=', '')
                ->distinct()
                ->orderBy('make')
                ->pluck('make');
        } else {
            $results = Machinery::select('model')
                ->whereNotNull('model')
                ->where('model', '!=', '')
                ->distinct()
                ->orderBy('model')
                ->pluck('model');
        }

        return response()->json([
            'success' => true,
            'data'    => $results,
        ], 200);
    }
}
