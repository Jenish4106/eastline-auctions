<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('Admin.Pages.Category.index');
    }

    public function fetchCategories()
    {
        $categories = Category::select([
                'id', 
                'image',
                'category_name',
                'total_machinery',
                'created_at',
                'updated_at'
            ]);

        return DataTables::of($categories)
            ->addIndexColumn()
            ->addColumn('image', function ($category) {
                if ($category->image) {
                    return '<img src="' . asset('categories/' . $category->image) . '" alt="Category Image" width="50" class="clickable-image cursor-pointer rounded" data-src="' . asset('categories/' . $category->image) . '" data-name="' . $category->category_name . '">';
                }
                return '<span class="badge bg-label-secondary">No Image</span>';
            })
            ->addColumn('created_date', function ($category) {
                return $category->created_date;
            })
            ->addColumn('updated_date', function ($category) {
                return $category->updated_date;
            })
            ->addColumn('actions', function ($category) {
                $actions = '
                    <a href="javascript:void(0);" class="edit-category text-info me-2" data-id="'.$category->id.'" data-name="'.$category->category_name.'">
                        <i class="fa-regular fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" class="delete-category text-danger" data-id="'.$category->id.'" data-name="'.$category->category_name.'">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>';
                
                return $actions;
            })
            ->rawColumns(['image', 'actions'])
            ->orderColumn('DT_RowIndex', 'id $1')
            ->filterColumn('DT_RowIndex', function($query, $keyword) {
            })
            ->filterColumn('category_name', function($query, $keyword) {
                $keyword = addslashes($keyword);
                $query->where('category_name', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('total_machinery', function($query, $keyword) {
                $query->where('total_machinery', 'LIKE', "%{$keyword}%");
            })
            ->make(true);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:255|unique:categories,category_name',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'total_machinery' => 'required|integer|min:0',
        ], [
            'category_name.required' => 'The category name field is required.',
            'category_name.unique' => 'This category name already exists.',
            'image.required' => 'The category image field is required.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image may not be greater than 2MB.',
            'total_machinery.required' => 'The total machinery field is required.',
            'total_machinery.integer' => 'The total machinery must be an integer.',
            'total_machinery.min' => 'The total machinery must be at least 0.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = new Category();
        $category->category_name = $request->category_name;
        $category->total_machinery = $request->total_machinery;
        
        if ($request->hasFile('image')) {
            $destinationPath = public_path('categories');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $imageName = time() . '_' . Str::random(10) . '.' . $request->file('image')->getClientOriginalExtension();
            
            $request->file('image')->move($destinationPath, $imageName);
            
            $category->image = $imageName;
        }
        
        $category->save();

        return response()->json(['message' => 'Category created successfully'], 200);
    }
    
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:255|unique:categories,category_name,'.$category->id,
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'total_machinery' => 'required|integer|min:0',
        ], [
            'category_name.required' => 'The category name field is required.',
            'category_name.unique' => 'This category name already exists.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'image.max' => 'The image may not be greater than 2MB.',
            'total_machinery.required' => 'The total machinery field is required.',
            'total_machinery.integer' => 'The total machinery must be an integer.',
            'total_machinery.min' => 'The total machinery must be at least 0.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category->category_name = $request->category_name;
        $category->total_machinery = $request->total_machinery;
        
        if ($request->hasFile('image')) {
            if ($category->image) {
                $oldImagePath = public_path('categories/' . $category->image);
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }
            
            $destinationPath = public_path('categories');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $imageName = time() . '_' . Str::random(10) . '.' . $request->file('image')->getClientOriginalExtension();
            
            $request->file('image')->move($destinationPath, $imageName);
            
            $category->image = $imageName;
        }
        
        $category->save();

        return response()->json(['message' => 'Category updated successfully'], 200);
    }
    
    public function destroy(Request $request)
    {
        $category = Category::find($request->id);
        
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        
        if ($category->image) {
            $imagePath = public_path('categories/' . $category->image);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }
        
        $category->delete();
        
        return response()->json(['success' => 'Category deleted successfully']);
    }
    
    public function getCategory(Request $request)
    {
        $category = Category::find($request->id);
        
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        
        return response()->json(['category' => $category], 200);
    }
}