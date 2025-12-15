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

    public function create()
    {
        $categories = Category::all();
        return view('Admin.Pages.Machinery.create', compact('categories'));
    }

    public function edit($id)
    {
        $machinery = Machinery::with('images')->findOrFail($id);
        $categories = Category::all();
        return view('Admin.Pages.Machinery.edit', compact('machinery', 'categories'));
    }

    public function fetchMachinery()
    {
        $machinery = Machinery::with('category', 'images')->select([
                'id', 
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
                $firstImage = $machine->images->first();
                if ($firstImage) {
                    return '<img src="' . asset('machinery/' . $firstImage->image_path) . '" alt="Machine Image" width="50" class="rounded">';
                }
                return '<span class="badge bg-label-secondary">No Image</span>';
            })
            ->addColumn('category_name', function ($machine) {
                return $machine->category ? $machine->category->category_name : 'N/A';
            })
            ->addColumn('status_badge', function ($machine) {
                switch ($machine->status) {
                    case 1:
                        return '<span class="badge bg-label-success">Active</span>';
                    case 2:
                        return '<span class="badge bg-label-warning">Sold</span>';
                    case 3:
                        return '<span class="badge bg-label-danger">Closed</span>';
                    default:
                        return '<span class="badge bg-label-secondary">Unknown</span>';
                }
            })
            ->addColumn('actions', function ($machine) {
                $actions = '
                    <a href="'.route('admin.machinery.edit', $machine->id).'" class="edit-machine text-info me-2">
                        <i class="fa-regular fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" class="delete-machine text-danger" data-id="'.$machine->id.'">
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
            'category_id' => 'required|exists:categories,id',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|string|max:4',
            'weight' => 'required|string|max:50',
            'working_hours' => 'required|string|max:50',
            'condition' => 'required|string|max:50',
            'fuel' => 'required|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'buy_now_price' => 'required|numeric|min:0',
            'bid_start_price' => 'nullable|numeric|min:0',
            'bid_end_time' => 'required|date',
            'description' => 'nullable|string',
            'specification' => 'nullable|json',
            'offer' => 'nullable|string|max:255',
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'video' => 'nullable|file|mimes:mp4,avi,mov,wmv|max:102400',
            'status' => 'required|in:1,2,3',
        ], [
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'make.required' => 'The make field is required.',
            'make.string' => 'The make must be a string.',
            'make.max' => 'The make may not be greater than 255 characters.',
            'model.required' => 'The model field is required.',
            'model.string' => 'The model must be a string.',
            'model.max' => 'The model may not be greater than 255 characters.',
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
            'offer.string' => 'The offer must be a string.',
            'offer.max' => 'The offer may not be greater than 255 characters.',
            'images.required' => 'At least one image is required.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You may not upload more than 10 images.',
            'images.*.required' => 'Each image is required.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif, svg.',
            'images.*.max' => 'Each image may not be greater than 20MB.',
            'video.file' => 'The video must be a file.',
            'video.mimes' => 'The video must be a file of type: mp4, avi, mov, wmv.',
            'video.max' => 'The video may not be greater than 100MB.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $machinery = new Machinery();
        $machinery->category_id = $request->category_id;
        $machinery->make = $request->make;
        $machinery->model = $request->model;
        $machinery->year = $request->year;
        $machinery->weight = $request->weight;
        $machinery->working_hours = $request->working_hours;
        $machinery->condition = $request->condition;
        $machinery->fuel = $request->fuel;
        $machinery->serial_number = $request->serial_number;
        $machinery->buy_now_price = $request->buy_now_price;
        
        $machinery->bid_start_price = $request->buy_now_price * 0.9;
        
        $machinery->bid_end_time = $request->bid_end_time;
        $machinery->description = $request->description;
        $machinery->status = $request->status;
        
        if ($request->specification) {
            $machinery->specification = json_decode($request->specification, true);
        }
        
        // Handle single offer value as string
        if ($request->offer) {
            $machinery->offer = $request->offer;
        }
        
        $machinery->save();
        
        // Handle images
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
        
        // Handle video
        if ($request->hasFile('video')) {
            $destinationPath = public_path('machinery');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $video = $request->file('video');
            $videoName = time() . '_' . Str::random(10) . '.' . $video->getClientOriginalExtension();
            $video->move($destinationPath, $videoName);
            
            $machinery->video_path = $videoName;
            $machinery->save();
        }

        return response()->json(['message' => 'Machinery created successfully'], 200);
    }
    
    public function update(Request $request, $id)
    {
        $machinery = Machinery::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|string|max:4',
            'weight' => 'required|string|max:50',
            'working_hours' => 'required|string|max:50',
            'condition' => 'required|string|max:50',
            'fuel' => 'required|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'buy_now_price' => 'required|numeric|min:0',
            'bid_start_price' => 'nullable|numeric|min:0',
            'bid_end_time' => 'required|date',
            'description' => 'nullable|string',
            'specification' => 'nullable|json',
            'offer' => 'nullable|string|max:255',
            'images' => 'sometimes|array|max:10',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'video' => 'nullable|file|mimes:mp4,avi,mov,wmv|max:102400',
            'status' => 'required|in:1,2,3',
        ], [
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'make.required' => 'The make field is required.',
            'make.string' => 'The make must be a string.',
            'make.max' => 'The make may not be greater than 255 characters.',
            'model.required' => 'The model field is required.',
            'model.string' => 'The model must be a string.',
            'model.max' => 'The model may not be greater than 255 characters.',
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
            'offer.string' => 'The offer must be a string.',
            'offer.max' => 'The offer may not be greater than 255 characters.',
            'images.sometimes' => 'The images field is optional.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You may not upload more than 10 images.',
            'images.*.sometimes' => 'Each image is optional.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif, svg.',
            'images.*.max' => 'Each image may not be greater than 20MB.',
            'video.file' => 'The video must be a file.',
            'video.mimes' => 'The video must be a file of type: mp4, avi, mov, wmv.',
            'video.max' => 'The video may not be greater than 100MB.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be 1 (Active), 2 (Sold), or 3 (Closed).',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $machinery->category_id = $request->category_id;
        $machinery->make = $request->make;
        $machinery->model = $request->model;
        $machinery->year = $request->year;
        $machinery->weight = $request->weight;
        $machinery->working_hours = $request->working_hours;
        $machinery->condition = $request->condition;
        $machinery->fuel = $request->fuel;
        $machinery->serial_number = $request->serial_number;
        $machinery->buy_now_price = $request->buy_now_price;
        
        $machinery->bid_start_price = $request->buy_now_price * 0.9;
        
        $machinery->bid_end_time = $request->bid_end_time;
        $machinery->description = $request->description;
        $machinery->status = $request->status;
        
        if ($request->specification) {
            $machinery->specification = json_decode($request->specification, true);
        }
        
        // Handle single offer value as string
        if ($request->offer) {
            $machinery->offer = $request->offer;
        }
        
        $machinery->save();
        
        // Handle images
        if ($request->hasFile('images')) {
            $destinationPath = public_path('machinery');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
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
        
        // Handle video
        if ($request->hasFile('video')) {
            // Delete old video if exists
            if ($machinery->video_path) {
                $oldVideoPath = public_path('machinery/' . $machinery->video_path);
                if (File::exists($oldVideoPath)) {
                    File::delete($oldVideoPath);
                }
            }
            
            $destinationPath = public_path('machinery');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $video = $request->file('video');
            $videoName = time() . '_' . Str::random(10) . '.' . $video->getClientOriginalExtension();
            $video->move($destinationPath, $videoName);
            
            $machinery->video_path = $videoName;
            $machinery->save();
        }

        return response()->json(['message' => 'Machinery updated successfully'], 200);
    }

    public function destroy(Request $request)
    {
        $machinery = Machinery::find($request->id);

        if (!$machinery) {
            return response()->json(['error' => 'Machinery not found'], 404);
        }

        // Delete images
        foreach ($machinery->images as $image) {
            $imagePath = public_path('machinery/' . $image->image_path);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
            $image->delete();
        }
        
        // Delete video if exists
        if ($machinery->video_path) {
            $videoPath = public_path('machinery/' . $machinery->video_path);
            if (File::exists($videoPath)) {
                File::delete($videoPath);
            }
        }

        $machinery->delete();

        return response()->json(['success' => 'Machinery deleted successfully']);
    }

    public function getMachinery(Request $request)
    {
        $machinery = Machinery::with('images')->find($request->id);

        if (!$machinery) {
            return response()->json(['error' => 'Machinery not found'], 404);
        }

        return response()->json(['machinery' => $machinery], 200);
    }

    public function view($id)
    {
        $machinery = Machinery::with('images')->findOrFail($id);
        return view('Admin.Pages.Machinery.view', compact('machinery'));
    }
}