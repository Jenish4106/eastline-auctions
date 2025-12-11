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

        $category = Category::find($categoryId);
        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $machineryList = Machinery::with('images')
            ->where('category_id', $categoryId)
            ->select([
                'id',
                'name',
                'working_hours',
                'buy_now_price',
                'category_id',
            ])
            ->get();

        $machineryWithImages = $machineryList->map(function ($machinery) {
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
