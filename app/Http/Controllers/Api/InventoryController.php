<?php
namespace App\Http\Controllers\Api;

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
                $category->image_url = asset('categories/' . $category->image);
            } else {
                $category->image_url = null;
            }
            return $category;
        });

        return response()->json([
            'success' => true,
            'data'    => $categoriesWithImages,
        ], 200);
    }

    public function getMachineryByCategory(Request $request)
    {
        $categoryId = $request->input('categoryId');
        $fromYear = $request->input('from_year');
        $toYear = $request->input('to_year');
        $sortBy = $request->input('sort_by', 'newest');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $category = Category::find($categoryId);
        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $machineryQuery = Machinery::with('images')
            ->where('category_id', $categoryId);

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

        $machineryList = $machineryQuery->select([
                'id',
                'name',
                'working_hours',
                'buy_now_price',
                'category_id',
                'year',
                'make',
                'model',
                'bid_end_time',
                'created_at'
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        $machineryWithImages = $machineryList->getCollection()->map(function ($machinery) {
            $year = $machinery->year ?? '';
            $make = $machinery->make ?? '';
            $model = $machinery->model ?? '';
            $machinery->name = trim("$year $make $model");
            
            if ($machinery->images && $machinery->images->count() > 0) {
                $firstImage                 = $machinery->images->first()->image_path;
                $machinery->first_image_url = asset('machinery/' . ltrim($firstImage, '/'));
            } else {
                $machinery->first_image_url = null;
            }

            unset($machinery->images);

            return $machinery;
        });

        if ($machineryWithImages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No machinery found in this category',
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
        $machineryId = $request->input('machineryId');
        $machinery   = Machinery::with('category', 'images')->find($machineryId);

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

        if ($machinery->images) {
            $machinery->images = $machinery->images->map(function ($image) {
                $image->full_url = asset('machinery/' . ltrim($image->image_path, '/'));
                return $image;
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $machinery,
        ], 200);
    }
}