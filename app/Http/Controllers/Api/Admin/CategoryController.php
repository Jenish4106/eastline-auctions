<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Get all categories with pagination and search
     */
    public function index(Request $request)
    {
        try {
            $search = $request->request->get('search', '');
            
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $query = Category::select([
                'id',
                'image',
                'category_name',
                'total_machinery',
                'created_at',
                'updated_at'
            ]);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('category_name', 'LIKE', "%{$search}%")
                      ->orWhere('total_machinery', 'LIKE', "%{$search}%");
                });
            }

            $categories = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

            $categoriesWithImages = $categories->getCollection()->map(function ($category) {
                if ($category->image) {
                    if (!is_array($category->image)) {
                        if (filter_var($category->image, FILTER_VALIDATE_URL)) {
                            $category->image = [$category->image];
                        } else {
                            $category->image = [asset('categories/' . $category->image)];
                        }
                    }
                } else {
                    $category->image = [];
                }
                return $category;
            });

            return response()->json([
                'status' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categoriesWithImages,
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
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
     * Get single category by ID
     */
    public function show(Request $request)
    {
        try {
            $id = $request->id;
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                ], 404);
            }

            if ($category->image) {
                if (!is_array($category->image)) {
                    if (filter_var($category->image, FILTER_VALIDATE_URL)) {
                        $category->image = [$category->image];
                    } else {
                        $category->image = [asset('categories/' . $category->image)];
                    }
                }
            } else {
                $category->image = [];
            }

            return response()->json([
                'status' => true,
                'message' => 'Category retrieved successfully',
                'data' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Create new category
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_name' => 'required|string|max:255',
                'image_urls' => 'required|array',
                'image_urls.*' => 'required|url',
                'total_machinery' => 'required|integer|min:0',
            ], [
                'category_name.required' => 'The category name field is required.',
                'image_urls.required' => 'The category image URLs field is required.',
                'image_urls.array' => 'The image URLs must be an array.',
                'image_urls.*.required' => 'Each image URL is required.',
                'image_urls.*.url' => 'Each image URL must be a valid URL.',
                'total_machinery.required' => 'The total machinery field is required.',
                'total_machinery.integer' => 'The total machinery must be an integer.',
                'total_machinery.min' => 'The total machinery must be at least 0.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $category = new Category();
            $category->category_name = $request->category_name;
            $category->total_machinery = $request->total_machinery;
            $category->image = $request->image_urls;

            $category->save();

            return response()->json([
                'status' => true,
                'message' => 'Category created successfully',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Update category
     */
    public function update(Request $request)
    {
        try {
            $id = $request->id;
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'category_name' => 'required|string|max:255',
                'image_urls' => 'sometimes|array',
                'image_urls.*' => 'required|url',
                'total_machinery' => 'required|integer|min:0',
            ], [
                'category_name.required' => 'The category name field is required.',
                'image_urls.array' => 'The image URLs must be an array.',
                'image_urls.*.required' => 'Each image URL is required.',
                'image_urls.*.url' => 'Each image URL must be a valid URL.',
                'total_machinery.required' => 'The total machinery field is required.',
                'total_machinery.integer' => 'The total machinery must be an integer.',
                'total_machinery.min' => 'The total machinery must be at least 0.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $category->category_name = $request->category_name;
            $category->total_machinery = $request->total_machinery;

            if ($request->has('image_urls')) {
                $category->image = $request->image_urls;
            }

            $category->save();

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully',
                'data' => $category,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Delete category
     */
    public function delete(Request $request)
    {
        try {
            $id = $request->id;
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                ], 404);
            }

            if ($category->image) {
                if (is_array($category->image)) {
                    foreach ($category->image as $imageUrl) {
                        $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                        if ($filename) {
                            $imagePath = public_path('uploads/images/' . $filename);
                            if (File::exists($imagePath)) {
                                File::delete($imagePath);
                            }
                            $oldImagePath = public_path('categories/' . $filename);
                            if (File::exists($oldImagePath)) {
                                File::delete($oldImagePath);
                            }
                        }
                    }
                } else {
                    if (filter_var($category->image, FILTER_VALIDATE_URL)) {
                        $filename = basename(parse_url($category->image, PHP_URL_PATH));
                        if ($filename) {
                            $imagePath = public_path('uploads/images/' . $filename);
                            if (File::exists($imagePath)) {
                                File::delete($imagePath);
                            }
                        }
                    } else {
                        $imagePath = public_path('categories/' . $category->image);
                        if (File::exists($imagePath)) {
                            File::delete($imagePath);
                        }
                        $imagePath2 = public_path('uploads/images/' . $category->image);
                        if (File::exists($imagePath2)) {
                            File::delete($imagePath2);
                        }
                    }
                }
            }

            $category->delete();

            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}

