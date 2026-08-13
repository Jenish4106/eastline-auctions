<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Get all categories with pagination and search
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');
            
            $perPage = $request->input('per_page');
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['id', 'category_name', 'total_machinery', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

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
                    $q->where('id', 'LIKE', "%{$search}%")
                      ->orWhere('category_name', 'LIKE', "%{$search}%")
                      ->orWhere('total_machinery', 'LIKE', "%{$search}%");
                });
            }

            $perPageData = $perPage ?? Category::count();
            $categories = $query->orderBy($sortBy, $sortOrder)->paginate($perPageData, ['*'], 'page', $page);

            $categoriesWithImages = $categories->getCollection()->map(function ($category) {
                if ($category->image) {
                    $imageArray = json_decode($category->image, true);
                    if (is_array($imageArray)) {
                        $imageUrls = [];
                        foreach ($imageArray as $filename) {
                            $imageUrls[] = $this->resolveCategoryImageUrl($filename);
                        }
                        $category->image_urls = collect($imageUrls)->filter()->values()->toArray();
                    } else {
                        $category->image_urls = [$this->resolveCategoryImageUrl($category->image)];
                    }
                } else {
                    $category->image_urls = [];
                }
                
                unset($category->image);
                return $category;
            });

            if ($categoriesWithImages->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'No categories found',
                ], 200);
            }

            return response()->json([
                'status'     => true,
                'message'    => 'Categories retrieved successfully',
                'data'       => $categoriesWithImages,
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page'    => $categories->lastPage(),
                    'per_page'     => $categories->perPage(),
                    'total'        => $categories->total(),
                    'from'         => $categories->firstItem(),
                    'to'           => $categories->lastItem(),
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
     * Get single category by ID
     */
    public function show(Request $request)
    {
        try {
            $id       = $request->id;
            $category = Category::find($id);

            if (! $category) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Category not found',
                ], 404);
            }

            if ($category->image) {
                $imageArray = json_decode($category->image, true);
                if (is_array($imageArray)) {
                    $imageUrls = [];
                    foreach ($imageArray as $filename) {
                        $imageUrls[] = $this->resolveCategoryImageUrl($filename);
                    }
                    $category->image_urls = collect($imageUrls)->filter()->values()->toArray();
                } else {
                    $category->image_urls = [$this->resolveCategoryImageUrl($category->image)];
                }
            } else {
                $category->image_urls = [asset('public/uploads/defaults/default.png') . '?time=' . time()];
            }
            
            unset($category->image);

            return response()->json([
                'status'  => true,
                'message' => 'Category retrieved successfully',
                'data'    => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
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
                'category_name'   => 'required|string|max:255',
                'image_urls'      => 'required|array',
                'image_urls.*'    => 'required|url',
                'total_machinery' => 'required|integer|min:0',
            ], [
                'category_name.required'   => 'The category name field is required.',
                'image_urls.required'      => 'The category image URLs field is required.',
                'image_urls.array'         => 'The image URLs must be an array.',
                'image_urls.*.required'    => 'Each image URL is required.',
                'image_urls.*.url'         => 'Each image URL must be a valid URL.',
                'total_machinery.required' => 'The total machinery field is required.',
                'total_machinery.integer'  => 'The total machinery must be an integer.',
                'total_machinery.min'      => 'The total machinery must be at least 0.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $category                  = new Category();
            $category->category_name   = $request->category_name;
            $category->total_machinery = $request->total_machinery;
            $category->image           = json_encode($request->image_urls);
            $category->save();

            $category->image_urls = $request->image_urls;
            unset($category->image);

            return response()->json([
                'status'  => true,
                'message' => 'Category created successfully',
                'data'    => $category,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update category
     */
    public function update(Request $request)
    {
        try {
            $id       = $request->id;
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'category_name'   => 'required|string|max:255',
                'image_urls'      => 'sometimes|array',
                'image_urls.*'    => 'required|url',
                'total_machinery' => 'required|integer|min:0',
            ], [
                'category_name.required'   => 'The category name field is required.',
                'image_urls.array'         => 'The image URLs must be an array.',
                'image_urls.*.required'    => 'Each image URL is required.',
                'image_urls.*.url'         => 'Each image URL must be a valid URL.',
                'total_machinery.required' => 'The total machinery field is required.',
                'total_machinery.integer'  => 'The total machinery must be an integer.',
                'total_machinery.min'      => 'The total machinery must be at least 0.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $category->category_name   = $request->category_name;
            $category->total_machinery = $request->total_machinery;

            if ($request->has('image_urls') && is_array($request->image_urls)) {
                $category->image = json_encode($request->image_urls);
            }

            $category->save();
            
            if ($request->has('image_urls') && is_array($request->image_urls)) {
                $category->image_urls = $request->image_urls;
            } else {
                $category->image_urls = json_decode($category->image, true) ?? [];
            }
            unset($category->image);

            return response()->json([
                'status'  => true,
                'message' => 'Category updated successfully',
                'data'    => $category,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Category not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
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
            $id       = $request->id;
            $category = Category::find($id);

            if (! $category) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Category not found',
                ], 404);
            }

            if ($category->image) {
                $imageArray = json_decode($category->image, true);
                if (is_array($imageArray)) {
                    foreach ($imageArray as $image) {
                        $filename = is_string($image) ? basename(parse_url($image, PHP_URL_PATH)) : null;
                        if ($filename) {
                            $categoryImagePath = public_path('uploads/category/images/' . $filename);
                            
                            if (file_exists($categoryImagePath)) {
                                unlink($categoryImagePath);
                            }
                        }
                    }
                } else {
                    $filename = is_string($category->image) ? basename(parse_url($category->image, PHP_URL_PATH)) : null;
                    if ($filename) {
                        $categoryImagePath = public_path('uploads/category/images/' . $filename);
                        
                        if (file_exists($categoryImagePath)) {
                            unlink($categoryImagePath);
                        }
                    }
                }
            }

            $category->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Category deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Resolve category image URL dynamically
     */
    private function resolveCategoryImageUrl($filename)
    {
        if (empty($filename)) {
            return asset('public/uploads/defaults/default.png') . '?time=' . time();
        }

        if (str_starts_with($filename, 'http://') || str_starts_with($filename, 'https://')) {
            return $filename;
        }

        $cleanFilename = ltrim($filename, '/');

        $categoryImagePath = public_path('uploads/category/images/' . $cleanFilename);
        if (file_exists($categoryImagePath)) {
            return asset('public/uploads/category/images/' . $cleanFilename) . '?time=' . time();
        }

        // If not found locally, use S3 URL if S3 is configured
        // if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.secret')) {
        //     return Storage::disk('s3')->url('uploads/category/images/' . $cleanFilename);
        // }

        return asset('public/uploads/defaults/default.png') . '?time=' . time();
    }
}
