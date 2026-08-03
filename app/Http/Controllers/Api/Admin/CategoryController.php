<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
                            $categoryImagePath = public_path('uploads/category/images/' . $filename);
                            $apiPublicImagePath = base_path('api/public/uploads/category/images/' . $filename);
                            if (file_exists($categoryImagePath) || file_exists($apiPublicImagePath)) {
                                $imageUrls[] = asset('public/uploads/category/images/' . $filename);
                            } else {
                                $imageUrls[] = asset('public/uploads/defaults/default-machine.png');
                            }
                        }

                        $category->image_urls = collect($imageUrls)->filter()->values()->toArray();
                    } else {
                        $categoryImagePath = public_path('uploads/category/images/' . $category->image);
                        $apiPublicImagePath = base_path('api/public/uploads/category/images/' . $category->image);
                        if (file_exists($categoryImagePath) || file_exists($apiPublicImagePath)) {
                            $category->image_urls = [asset('public/uploads/category/images/' . $category->image)];
                        } else {
                            $category->image_urls = [asset('public/uploads/defaults/default-machine.png')];
                        }

                        $category->image_urls = collect($category->image_urls)->filter()->values()->toArray();
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
                        $path1 = public_path('uploads/category/images/' . $filename);
                        $path2 = base_path('api/public/uploads/category/images/' . $filename);
                        if (file_exists($path1) || file_exists($path2)) {
                            $imageUrls[] = asset('public/uploads/category/images/' . $filename);
                        } else {
                            $imageUrls[] = asset('public/uploads/defaults/default-machine.png');
                        }
                    }
                    $category->image_urls = collect($imageUrls)->filter()->values()->toArray();
                } else {
                    $path1 = public_path('uploads/category/images/' . $category->image);
                    $path2 = base_path('api/public/uploads/category/images/' . $category->image);
                    if (file_exists($path1) || file_exists($path2)) {
                        $category->image_urls = [asset('public/uploads/category/images/' . $category->image)];
                    } else {
                        $category->image_urls = [asset('public/uploads/defaults/default-machine.png')];
                    }
                }
            } else {
                $category->image_urls = [asset('public/uploads/defaults/default-machine.png')];
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
            
            $filenames = [];
            foreach ($request->image_urls as $imageUrl) {
                $filenames[] = basename(parse_url($imageUrl, PHP_URL_PATH));
            }

            $category->image = json_encode($filenames);

            $category->save();
            
            $imageUrls = [];
            foreach ($filenames as $filename) {
                $path1 = public_path('uploads/category/images/' . $filename);
                $path2 = base_path('api/public/uploads/category/images/' . $filename);
                if (file_exists($path1) || file_exists($path2)) {
                    $imageUrls[] = asset('public/uploads/category/images/' . $filename);
                } else {
                    $imageUrls[] = asset('public/uploads/defaults/default-machine.png');
                }
            }
            $category->image_urls = collect($imageUrls)->filter()->values()->toArray();
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

            if ($request->has('image_urls')) {
                $existingImages = json_decode($category->image, true);
                if (!is_array($existingImages)) {
                    $existingImages = $existingImages ? [$existingImages] : [];
                }
                
                $incomingFilenames = [];
                foreach ($request->image_urls as $imageUrl) {
                    $incomingFilenames[] = basename(parse_url($imageUrl, PHP_URL_PATH));
                }
                
                foreach ($existingImages as $existingImage) {
                    $existingFilename = is_string($existingImage) ? basename(parse_url($existingImage, PHP_URL_PATH)) : null;
                    if ($existingFilename && !in_array($existingFilename, $incomingFilenames)) {
                        $categoryImagePath = public_path('uploads/category/images/' . $existingFilename);
                        $apiPublicImagePath = base_path('api/public/uploads/category/images/' . $existingFilename);
                        
                        if (file_exists($categoryImagePath)) {
                            unlink($categoryImagePath);
                        } elseif (file_exists($apiPublicImagePath)) {
                            unlink($apiPublicImagePath);
                        }
                    }
                }
                
                $filenames = [];
                foreach ($request->image_urls as $imageUrl) {
                    $filenames[] = basename(parse_url($imageUrl, PHP_URL_PATH));
                }
                $category->image = json_encode($filenames);
            }

            $category->save();
            
            if ($request->has('image_urls')) {
                $imageUrls = [];
                foreach ($filenames as $filename) {
                    $path1 = public_path('uploads/category/images/' . $filename);
                    $path2 = base_path('api/public/uploads/category/images/' . $filename);
                    if (file_exists($path1) || file_exists($path2)) {
                        $imageUrls[] = asset('public/uploads/category/images/' . $filename);
                    } else {
                        $imageUrls[] = asset('public/uploads/defaults/default-machine.png');
                    }
                }
                $category->image_urls = collect($imageUrls)->filter()->values()->toArray();
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
}
