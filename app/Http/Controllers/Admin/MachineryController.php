<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
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
        $machinery = Machinery::with('category')->select([
                'id', 
                'name',
                'category_id',
                'year',
                'weight',
                'fuel_type',
                'buy_now_price',
                'bid_start_price',
                'status',
                'created_at',
                'updated_at'
            ]);

        return DataTables::of($machinery)
            ->addIndexColumn()
            ->addColumn('category_name', function ($machine) {
                return $machine->category ? $machine->category->category_name : 'N/A';
            })
            ->addColumn('status_badge', function ($machine) {
                return $machine->status_badge;
            })
            ->addColumn('created_date', function ($machine) {
                return $machine->created_date;
            })
            ->addColumn('updated_date', function ($machine) {
                return $machine->updated_date;
            })
            ->addColumn('actions', function ($machine) {
                $actions = '
                    <a href="javascript:void(0);" class="edit-machine text-info me-2" data-id="'.$machine->id.'" data-name="'.$machine->name.'">
                        <i class="fa-regular fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" class="delete-machine text-danger" data-id="'.$machine->id.'" data-name="'.$machine->name.'">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>';
                
                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
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
            'fuel_type' => 'required|string|max:50',
            'buy_now_price' => 'required|numeric|min:0',
            'bid_start_price' => 'required|numeric|min:0',
            'bid_end_time' => 'required|date',
            'description' => 'nullable|string',
            'images' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'status' => 'required|in:1,2,3',
        ], [
            'name.required' => 'The machinery name field is required.',
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'year.required' => 'The year field is required.',
            'weight.required' => 'The weight field is required.',
            'fuel_type.required' => 'The fuel type field is required.',
            'buy_now_price.required' => 'The buy now price field is required.',
            'buy_now_price.numeric' => 'The buy now price must be a number.',
            'buy_now_price.min' => 'The buy now price must be at least 0.',
            'bid_start_price.required' => 'The bid start price field is required.',
            'bid_start_price.numeric' => 'The bid start price must be a number.',
            'bid_start_price.min' => 'The bid start price must be at least 0.',
            'bid_end_time.required' => 'The bid end time field is required.',
            'bid_end_time.date' => 'The bid end time must be a valid date.',
            'images.image' => 'The file must be an image.',
            'images.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'images.max' => 'The image may not be greater than 2MB.',
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
        $machinery->fuel_type = $request->fuel_type;
        $machinery->buy_now_price = $request->buy_now_price;
        $machinery->bid_start_price = $request->bid_start_price;
        $machinery->bid_end_time = $request->bid_end_time;
        $machinery->description = $request->description;
        $machinery->status = $request->status;
        
        if ($request->hasFile('images')) {
            $destinationPath = public_path('machinery');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $imageName = time() . '_' . Str::random(10) . '.' . $request->file('images')->getClientOriginalExtension();
            
            $request->file('images')->move($destinationPath, $imageName);
            
            $machinery->images = $imageName;
        }
        
        $machinery->save();

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
            'fuel_type' => 'required|string|max:50',
            'buy_now_price' => 'required|numeric|min:0',
            'bid_start_price' => 'required|numeric|min:0',
            'bid_end_time' => 'required|date',
            'description' => 'nullable|string',
            'images' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:20480',
            'status' => 'required|in:1,2,3',
        ], [
            'name.required' => 'The machinery name field is required.',
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'year.required' => 'The year field is required.',
            'weight.required' => 'The weight field is required.',
            'fuel_type.required' => 'The fuel type field is required.',
            'buy_now_price.required' => 'The buy now price field is required.',
            'buy_now_price.numeric' => 'The buy now price must be a number.',
            'buy_now_price.min' => 'The buy now price must be at least 0.',
            'bid_start_price.required' => 'The bid start price field is required.',
            'bid_start_price.numeric' => 'The bid start price must be a number.',
            'bid_start_price.min' => 'The bid start price must be at least 0.',
            'bid_end_time.required' => 'The bid end time field is required.',
            'bid_end_time.date' => 'The bid end time must be a valid date.',
            'images.image' => 'The file must be an image.',
            'images.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'images.max' => 'The image may not be greater than 2MB.',
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
        $machinery->fuel_type = $request->fuel_type;
        $machinery->buy_now_price = $request->buy_now_price;
        $machinery->bid_start_price = $request->bid_start_price;
        $machinery->bid_end_time = $request->bid_end_time;
        $machinery->description = $request->description;
        $machinery->status = $request->status;
        
        if ($request->hasFile('images')) {
            if ($machinery->images) {
                $oldImagePath = public_path('machinery/' . $machinery->images);
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }
            
            $destinationPath = public_path('machinery');
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            $imageName = time() . '_' . Str::random(10) . '.' . $request->file('images')->getClientOriginalExtension();
            
            $request->file('images')->move($destinationPath, $imageName);
            
            $machinery->images = $imageName;
        }
        
        $machinery->save();

        return response()->json(['message' => 'Machinery updated successfully'], 200);
    }
    
    public function destroy(Request $request)
    {
        $machinery = Machinery::find($request->id);
        
        if (!$machinery) {
            return response()->json(['error' => 'Machinery not found'], 404);
        }
        
        if ($machinery->images) {
            $imagePath = public_path('machinery/' . $machinery->images);
            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }
        
        $machinery->delete();
        
        return response()->json(['success' => 'Machinery deleted successfully']);
    }
    
    public function getMachinery(Request $request)
    {
        $machinery = Machinery::with('category')->find($request->id);
        
        if (!$machinery) {
            return response()->json(['error' => 'Machinery not found'], 404);
        }
        
        return response()->json(['machinery' => $machinery], 200);
    }
}