<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\License;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return view('Admin.Pages.UserManagement.index');
    }

    public function create()
    {
        return view('Admin.Pages.UserManagement.create');
    }

    // Remove the edit method to disable user editing
    
    public function fetchUsers()
    {
        $users = User::select([
                'id', 
                'first_name',
                'last_name',
                'email',
                'phone_no',
                'company_name',
                'address',
                'city',
                'state',
                'zip_code',
                'status',
                'is_license',
                'created_at'
            ])->with('license:id,user_id,file,status');

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
            ->addColumn('license_status', function ($user) {
                $badgeClass = $user->is_license == 0 ? 'bg-label-warning' : ($user->is_license == 2 ? 'bg-label-danger' : 'bg-label-success');
                $statusText = $user->license_status_text;
                return '<span class="badge '.$badgeClass.'">'.$statusText.'</span>';
            })
            ->addColumn('actions', function ($user) {
                $actions = '
                    <a href="javascript:void(0);" class="view-details text-info me-2" data-id="'.$user->id.'">
                        <i class="fa-regular fa-eye"></i>
                    </a>';
                // Remove the edit button to disable user editing from index page
                
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
            ->rawColumns(['status', 'license_status', 'actions'])
            ->orderColumn('DT_RowIndex', 'id $1')
            ->filterColumn('DT_RowIndex', function ($query, $keyword) {
            })
            ->filterColumn('name', function ($query, $keyword) {
                $keyword = addslashes($keyword);
                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('email', function ($query, $keyword) {
                $query->where('email', 'LIKE', "%{$keyword}%");
            })
            ->filterColumn('phone_no', function ($query, $keyword) {
                $query->where('phone_no', 'LIKE', "%{$keyword}%");
            })
            ->make(true);
    }

    public function store(Request $request)
    {
        // Check if we're updating an existing user
        if ($request->has('id') && $request->id) {
            return $this->update($request, $request->id);
        }

        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email|max:255',
            'phone_no'     => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'address'      => 'required|string|max:255',
            'city'         => 'required|string|max:100',
            'state'        => 'required|string|max:100',
            'zip_code'     => 'required|string|max:20',
            'password'     => 'required|string|min:8|confirmed',
        ], [
            'first_name.required'   => 'The first name field is required.',
            'last_name.required'    => 'The last name field is required.',
            'email.required'        => 'The email field is required.',
            'email.email'           => 'The email must be a valid email address.',
            'email.unique'          => 'The email has already been taken.',
            'phone_no.required'     => 'The phone number field is required.',
            'company_name.required' => 'The company name field is required.',
            'address.required'      => 'The address field is required.',
            'city.required'         => 'The city field is required.',
            'state.required'        => 'The state field is required.',
            'zip_code.required'     => 'The zip code field is required.',
            'password.required'     => 'The password field is required.',
            'password.min'          => 'The password must be at least 8 characters.',
            'password.confirmed'    => 'The password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user               = new User();
        $user->first_name   = $request->first_name;
        $user->last_name    = $request->last_name;
        $user->email        = $request->email;
        $user->phone_no     = $request->phone_no;
        $user->company_name = $request->company_name;
        $user->address      = $request->address;
        $user->city         = $request->city;
        $user->state        = $request->state;
        $user->zip_code     = $request->zip_code;
        $user->password     = Hash::make($request->password);
        $user->status       = 1;
        $user->save();

        return response()->json(['message' => 'User created successfully'], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email,'.$user->id.'|max:255',
            'phone_no'     => 'required|string|max:20',
            'company_name' => 'required|string|max:255',
            'address'      => 'required|string|max:255',
            'city'         => 'required|string|max:100',
            'state'        => 'required|string|max:100',
            'zip_code'     => 'required|string|max:20',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $validator = Validator::make($request->all(), $rules, [
            'first_name.required'   => 'The first name field is required.',
            'last_name.required'    => 'The last name field is required.',
            'email.required'        => 'The email field is required.',
            'email.email'           => 'The email must be a valid email address.',
            'email.unique'          => 'The email has already been taken.',
            'phone_no.required'     => 'The phone number field is required.',
            'company_name.required' => 'The company name field is required.',
            'address.required'      => 'The address field is required.',
            'city.required'         => 'The city field is required.',
            'state.required'        => 'The state field is required.',
            'zip_code.required'     => 'The zip code field is required.',
            'password.required'     => 'The password field is required.',
            'password.min'          => 'The password must be at least 8 characters.',
            'password.confirmed'    => 'The password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->first_name   = $request->first_name;
        $user->last_name    = $request->last_name;
        $user->email        = $request->email;
        $user->phone_no     = $request->phone_no;
        $user->company_name = $request->company_name;
        $user->address      = $request->address;
        $user->city         = $request->city;
        $user->state        = $request->state;
        $user->zip_code     = $request->zip_code;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully'], 200);
    }

    public function changeStatus(Request $request)
    {
        $user = User::find($request->id);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();

        $statusText = $user->status == 1 ? 'activated' : 'blocked';

        return response()->json(['success' => "User {$statusText} successfully"]);
    }

    public function deleteUser(Request $request)
    {
        $user = User::find($request->id);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['success' => 'User deleted successfully']);
    }
    
    public function approveLicense(Request $request)
    {
        try {
            $userId = $request->user_id;
            $licenseId = $request->license_id;
            
            $license = License::find($licenseId);
            if (!$license || $license->user_id != $userId) {
                return response()->json(['error' => 'License not found'], 404);
            }
            
            $license->status = 1;
            $license->save();
            
            $user = User::find($userId);
            if ($user) {
                $user->is_license = 1;
                $user->save();
            }
            
            return response()->json(['success' => 'License approved successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while approving the license'], 500);
        }
    }
    
    public function declineLicense(Request $request)
    {
        try {
            $userId = $request->user_id;
            $licenseId = $request->license_id;
            
            $license = License::find($licenseId);
            if (!$license || $license->user_id != $userId) {
                return response()->json(['error' => 'License not found'], 404);
            }
            
            $license->status = 2;
            $license->save();
            
            $user = User::find($userId);
            if ($user) {
                $user->is_license = 2;
                $user->save();
            }
            
            return response()->json(['success' => 'License declined successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while declining the license'], 500);
        }
    }
    
    public function show($id)
    {
        $user = User::with('license')->findOrFail($id);
        return view('Admin.Pages.UserManagement.details', compact('user'));
    }
}