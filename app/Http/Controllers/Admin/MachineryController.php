<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use App\Models\MachineryImage;
use App\Models\Category;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MachineryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return view('Admin.Pages.Machinery.index', compact('categories'));
    }

    public function fetchMachinery()
    {
        $machinery = Machinery::with('category', 'images')->select([
                'id', 
                'name',
                'category_id',
                'year',
                'working_hours',
                'buy_now_price',
                'bid_start_price',
                'status'
            ]);

        return DataTables::of($machinery)
            ->addIndexColumn()
            ->addColumn('image_thumb', function ($machine) {
                if ($machine->images && $machine->images->count() > 0) {
                    $firstImage = $machine->images->first()->image_path;
                    $imageUrl = asset('machinery/' . ltrim($firstImage, '/'));
                    return '<img src="' . $imageUrl . '" alt="Machinery Image" style="width: 50px; height: 50px; object-fit: cover;" class="img-thumbnail">';
                }
                return '<span class="text-muted">No Image</span>';
            })
            ->addColumn('category_name', function ($machine) {
                return $machine->category ? $machine->category->category_name : 'N/A';
            })
            ->addColumn('status_badge', function ($machine) {
                return $machine->status_badge;
            })
            ->addColumn('actions', function ($machine) {
                $actions = '
                    <a href="'.route('admin.machinery.view', $machine->id).'" class="view-machine text-primary me-2" target="_blank">
                        <i class="fa-regular fa-eye"></i>
                    </a>
                    <a href="javascript:void(0);" class="edit-machine text-info me-2" data-id="'.$machine->id.'" data-name="'.$machine->name.'">
                        <i class="fa-regular fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" class="delete-machine text-danger" data-id="'.$machine->id.'" data-name="'.$machine->name.'">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>';
                
                return $actions;
            })
            ->rawColumns(['image_thumb', 'status_badge', 'actions'])
            ->orderColumn('DT_RowIndex', 'id $1')
            ->make(true);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'year' => 'required|string|max:4',
            'weight' => 'required|string|max:50',
            'working_hours' => 'required|string|max:50',
            'condition' => 'required|string|max:50',
            'fuel' => 'required|string|max:50',
            'buy_now_price' => 'required|numeric|min:0',
            'bid_start_price' => 'nullable|numeric|min:0',
            'bid_end_time' => 'required|date',
            'description' => 'nullable|string',
            'specification' => 'nullable|json',
            'offer' => 'nullable|json',
            'images' => 'array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'status' => 'required|in:1,2,3',
        ], [
            'name.required' => 'The machinery name field is required.',
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'year.required' => 'The year field is required.',
            'weight.required' => 'The weight field is required.',
            'working_hours.required' => 'The working hours field is required.',
            'condition.required' => 'The condition field is required.',
            'fuel.required' => 'The fuel field is required.',
            'buy_now_price.required' => 'The buy now price field is required.',
            'buy_now_price.numeric' => 'The buy now price must be a number.',
            'buy_now_price.min' => 'The buy now price must be at least 0.',
            'bid_start_price.numeric' => 'The bid start price must be a number.',
            'bid_start_price.min' => 'The bid start price must be at least 0.',
            'bid_end_time.required' => 'The bid end time field is required.',
            'bid_end_time.date' => 'The bid end time must be a valid date.',
            'specification.json' => 'The specification must be a valid JSON.',
            'offer.json' => 'The offer must be a valid JSON.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You may not upload more than 10 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif, svg.',
            'images.*.max' => 'Each image may not be greater than 2MB.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $machinery = new Machinery();
        $machinery->name = $request->name;
        $machinery->category_id = $request->category_id;
        $machinery->year = $request->year;
        $machinery->weight = $request->weight;
        $machinery->working_hours = $request->working_hours;
        $machinery->condition = $request->condition;
        $machinery->fuel = $request->fuel;
        $machinery->buy_now_price = $request->buy_now_price;
        
        $machinery->bid_start_price = $request->buy_now_price * 0.9;
        
        $machinery->bid_end_time = $request->bid_end_time;
        $machinery->description = $request->description;
        $machinery->status = $request->status;
        
        if ($request->specification) {
            $machinery->specification = json_decode($request->specification, true);
        }
        
        if ($request->offer) {
            $machinery->offer = json_decode($request->offer, true);
        }
        
        $machinery->save();
        
        // Handle images if provided
        if ($request->hasFile('images')) {
            $destinationPath = public_path('machinery');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            foreach ($request->file('images') as $index => $image) {
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($destinationPath, $imageName);
                
                MachineryImage::create([
                    'machinery_id' => $machinery->id,
                    'image_path' => $imageName,
                    'sort_order' => $index
                ]);
            }
        }

        return response()->json(['message' => 'Machinery created successfully'], 200);
    }
    
    public function update(Request $request, $id)
    {
        $machinery = Machinery::find($id);
        
        if (!$machinery) {
            return response()->json(['error' => 'Machinery not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'year' => 'required|string|max:4',
            'weight' => 'required|string|max:50',
            'working_hours' => 'required|string|max:50',
            'condition' => 'required|string|max:50',
            'fuel' => 'required|string|max:50',
            'buy_now_price' => 'required|numeric|min:0',
            'bid_start_price' => 'nullable|numeric|min:0',
            'bid_end_time' => 'required|date',
            'description' => 'nullable|string',
            'specification' => 'nullable|json',
            'offer' => 'nullable|json',
            'images' => 'sometimes|array|max:10',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'status' => 'required|in:1,2,3',
            'remove_images' => 'sometimes|array',
            'remove_images.*' => 'integer'
        ], [
            'name.required' => 'The machinery name field is required.',
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'year.required' => 'The year field is required.',
            'weight.required' => 'The weight field is required.',
            'working_hours.required' => 'The working hours field is required.',
            'condition.required' => 'The condition field is required.',
            'fuel.required' => 'The fuel field is required.',
            'buy_now_price.required' => 'The buy now price field is required.',
            'buy_now_price.numeric' => 'The buy now price must be a number.',
            'buy_now_price.min' => 'The buy now price must be at least 0.',
            'bid_start_price.numeric' => 'The bid start price must be a number.',
            'bid_start_price.min' => 'The bid start price must be at least 0.',
            'bid_end_time.required' => 'The bid end time field is required.',
            'bid_end_time.date' => 'The bid end time must be a valid date.',
            'specification.json' => 'The specification must be a valid JSON.',
            'offer.json' => 'The offer must be a valid JSON.',
            'images.sometimes' => 'The images field is optional.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You may not upload more than 10 images.',
            'images.*.sometimes' => 'Each image is optional.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif, svg.',
            'images.*.max' => 'Each image may not be greater than 2MB.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $machinery->name = $request->name;
        $machinery->category_id = $request->category_id;
        $machinery->year = $request->year;
        $machinery->weight = $request->weight;
        $machinery->working_hours = $request->working_hours;
        $machinery->condition = $request->condition;
        $machinery->fuel = $request->fuel;
        $machinery->buy_now_price = $request->buy_now_price;
        
        $machinery->bid_start_price = $request->buy_now_price * 0.9;
        
        $machinery->bid_end_time = $request->bid_end_time;
        $machinery->description = $request->description;
        $machinery->status = $request->status;
        
        if ($request->specification) {
            $machinery->specification = json_decode($request->specification, true);
        }
        
        if ($request->offer) {
            $machinery->offer = json_decode($request->offer, true);
        }
        
        $machinery->save();
        
        // Handle removing specific images
        if ($request->has('remove_images') && is_array($request->remove_images)) {
            foreach ($request->remove_images as $imageId) {
                $imageToRemove = MachineryImage::find($imageId);
                if ($imageToRemove && $imageToRemove->machinery_id == $machinery->id) {
                    $imagePath = public_path('machinery/' . ltrim($imageToRemove->image_path, '/'));
                    if (File::exists($imagePath)) {
                        File::delete($imagePath);
                    }
                    $imageToRemove->delete();
                }
            }
        }
        
        // Handle adding new images
        if ($request->hasFile('images')) {
            $destinationPath = public_path('machinery');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            // Get current image count for proper sorting
            $currentImageCount = $machinery->images()->count();
            
            foreach ($request->file('images') as $index => $image) {
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($destinationPath, $imageName);
                
                MachineryImage::create([
                    'machinery_id' => $machinery->id,
                    'image_path' => $imageName,
                    'sort_order' => $currentImageCount + $index
                ]);
            }
        }

        return response()->json(['message' => 'Machinery updated successfully'], 200);
    }
    
    public function destroy(Request $request)
    {
        $machinery = Machinery::find($request->id);
        
        if (!$machinery) {
            return response()->json(['error' => 'Machinery not found'], 404);
        }
        
        // Delete associated images
        $images = $machinery->images;
        if ($images->count() > 0) {
            foreach ($images as $image) {
                $imagePath = public_path('machinery/' . ltrim($image->image_path, '/'));
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                }
            }
            // Delete records from database
            $machinery->images()->delete();
        }
        
        $machinery->delete();
        
        return response()->json(['success' => 'Machinery deleted successfully']);
    }
    
    public function getMachinery(Request $request)
    {
        $machinery = Machinery::with('category', 'images')->find($request->id);
        
        if (!$machinery) {
            return response()->json(['error' => 'Machinery not found'], 404);
        }
        
        return response()->json(['machinery' => $machinery], 200);
    }
    
    public function view($id)
    {
        $machinery = Machinery::with('category', 'images')->find($id);
        
        if (!$machinery) {
            return redirect()->back()->with('error', 'Machinery not found.');
        }
        
        return view('Admin.Pages.Machinery.view', compact('machinery'));
    }
}