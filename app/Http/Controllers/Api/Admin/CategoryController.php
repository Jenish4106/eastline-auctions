<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\S3StorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
                            $imageUrls[] = $this->getImageUrl($filename, 'category');
                        }
                        $category->image_urls = array_values(array_filter($imageUrls));
                    } else {
                        $category->image_urls = [$this->getImageUrl($category->image, 'category')];
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
                        $imageUrls[] = $this->getImageUrl($filename, 'category');
                    }
                    $category->image_urls = array_values(array_filter($imageUrls));
                } else {
                    $category->image_urls = [$this->getImageUrl($category->image, 'category')];
                }
            } else {
                $category->image_urls = [$this->getImageUrl(null, 'category')];
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
                'image_urls.*'    => 'required|string',
                'total_machinery' => 'required|integer|min:0',
            ], [
                'category_name.required'   => 'The category name field is required.',
                'image_urls.required'      => 'The category image URLs field is required.',
                'image_urls.array'         => 'The image URLs must be an array.',
                'image_urls.*.required'    => 'Each image URL is required.',
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
            
            $imageFilenames = [];
            foreach ($request->image_urls as $imageUrl) {
                $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                if ($filename) {
                    $imageFilenames[] = $filename;
                }
            }

            $category->image = json_encode($imageFilenames);
            $category->save();

            $imageUrls = [];
            foreach ($imageFilenames as $filename) {
                $imageUrls[] = $this->getImageUrl($filename, 'category');
            }
            $category->image_urls = array_values(array_filter($imageUrls));
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
                'image_urls.*'    => 'required|string',
                'total_machinery' => 'required|integer|min:0',
            ], [
                'category_name.required'   => 'The category name field is required.',
                'image_urls.array'         => 'The image URLs must be an array.',
                'image_urls.*.required'    => 'Each image URL is required.',
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

            if ($request->has('image_urls')) {
                $imageFilenames = [];
                foreach ($request->image_urls as $imageUrl) {
                    $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                    if ($filename) {
                        $imageFilenames[] = $filename;
                    }
                }
                $category->image = json_encode($imageFilenames);
            }

            $category->save();

            if ($request->has('image_urls')) {
                $imageUrls = [];
                foreach ($imageFilenames as $filename) {
                    $imageUrls[] = $this->getImageUrl($filename, 'category');
                }
                $category->image_urls = array_values(array_filter($imageUrls));
                unset($category->image);
            }

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
                            S3StorageService::delete('uploads/category/images/' . $filename);
                        }
                    }
                } else {
                    $filename = is_string($category->image) ? basename(parse_url($category->image, PHP_URL_PATH)) : null;
                    if ($filename) {
                        S3StorageService::delete('uploads/category/images/' . $filename);
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
     * Resolve image URL from full URL, local asset, or Cloudflare S3
     */
    private function getImageUrl($item, $type = 'category')
    {
        if (empty($item)) {
            return S3StorageService::getUrl('uploads/defaults/default.png');
        }

        $filename = basename(parse_url($item, PHP_URL_PATH));

        return S3StorageService::getUrl('uploads/' . $type . '/images/' . $filename);
    }
}
