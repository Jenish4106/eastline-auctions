<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        return view('Admin.Pages.UserManagement.index');
    }

    public function fetchUsers()
    {
        $users = User::select([
                'id', 
                'first_name', 
                'last_name', 
                'email', 
                'phone_no', 
                'company_name', 
                'city', 
                'state', 
                'zip_code',
                'status',
                'created_at'
            ])
            ->orderBy('id', 'DESC');

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('name', function ($user) {
                return $user->first_name . ' ' . $user->last_name;
            })
            ->addColumn('address', function ($user) {
                return $user->city . ', ' . $user->state . ' ' . $user->zip_code;
            })
            ->addColumn('registration_date', function ($user) {
                return $user->created_at->format('F d, Y');
            })
            ->addColumn('status', function ($user) {
                $badgeClass = $user->status == 1 ? 'bg-label-success' : 'bg-label-danger';
                $statusText = $user->status_text;
                return '<span class="badge '.$badgeClass.'">'.$statusText.'</span>';
            })
            ->addColumn('actions', function ($user) {
                $actions = '
                    <a href="javascript:void(0);" class="view-details text-info me-2" data-id="'.$user->id.'">
                        <i class="fa-regular fa-eye"></i>
                    </a>';
                
                if ($user->status == 1) {
                    $actions .= '<a href="javascript:void(0);" class="block-user text-warning me-2" data-id="'.$user->id.'" data-name="'.($user->first_name . ' ' . $user->last_name).'">
                        <i class="fa-solid fa-ban"></i>
                    </a>';
                } else {
                    $actions .= '<a href="javascript:void(0);" class="unblock-user text-success me-2" data-id="'.$user->id.'" data-name="'.($user->first_name . ' ' . $user->last_name).'">
                        <i class="fa-solid fa-check"></i>
                    </a>';
                }
                
                $actions .= '<a href="javascript:void(0);" class="delete-user text-danger" data-id="'.$user->id.'" data-name="'.($user->first_name . ' ' . $user->last_name).'">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>';
                
                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }
    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'phone_no' => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'first_name.required' => 'The first name field is required.',
            'last_name.required' => 'The last name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'phone_no.required' => 'The phone number field is required.',
            'company_name.required' => 'The company name field is required.',
            'address.required' => 'The address field is required.',
            'city.required' => 'The city field is required.',
            'state.required' => 'The state field is required.',
            'zip_code.required' => 'The zip code field is required.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone_no = $request->phone_no;
        $user->company_name = $request->company_name;
        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip_code = $request->zip_code;
        $user->password = Hash::make($request->password);
        $user->status = 1; // Default to active
        $user->save();

        return response()->json(['message' => 'User created successfully'], 200);
    }
    
    public function changeStatus(Request $request)
    {
        $user = User::find($request->id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Toggle status: if active (1) make blocked (0), if blocked (0) make active (1)
        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();
        
        $statusText = $user->status == 1 ? 'activated' : 'blocked';
        
        return response()->json(['success' => "User {$statusText} successfully"]);
    }
    
    public function deleteUser(Request $request)
    {
        $user = User::find($request->id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        $user->delete();
        
        return response()->json(['success' => 'User deleted successfully']);
    }
}