<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    /**
     * Get all users with pagination and search
     */
    public function index(Request $request)
    {
        try {
            $search = $request->input('search', '');
            
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['id', 'first_name', 'last_name', 'email', 'phone_no', 'company_name', 'address', 'city', 'state', 'zip_code', 'status', 'is_license', 'created_at', 'updated_at'];
            $allowedSortOrders = ['asc', 'desc'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            
            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = User::select([
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
                'created_at',
                'updated_at',
            ])->with('license:id,user_id,file,status');

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone_no', 'LIKE', "%{$search}%")
                      ->orWhere('company_name', 'LIKE', "%{$search}%");
                });
            }

            $users = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $usersWithFormattedData = $users->getCollection()->map(function ($user) {
                $user->name = $user->first_name . ' ' . $user->last_name;
                $user->created_at = $user->created_at->format('M d, Y h:i A');
                $user->updated_at = $user->updated_at->format('M d, Y h:i A');
                
                $statusMap = [
                    0 => 'Inactive',
                    1 => 'Active',
                    2 => 'Blocked',
                ];
                $user->status_text = isset($statusMap[$user->status]) ? $statusMap[$user->status] : 'Unknown';
                
                $licenseStatusMap = [
                    0 => 'Pending',
                    1 => 'Approved',
                    2 => 'Rejected',
                ];
                $user->license_status = $user->license ? $user->license->status : null;
                $user->license_status_text = $user->license ? (isset($licenseStatusMap[$user->license->status]) ? $licenseStatusMap[$user->license->status] : 'Unknown') : 'No License';
                
                unset($user->license);
                return $user;
            });

            if ($usersWithFormattedData->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'No users found',
                ], 200);
            }

            return response()->json([
                'status'     => true,
                'message'    => 'Users retrieved successfully',
                'data'       => $usersWithFormattedData->makeHidden(['created_at', 'updated_at']),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                    'from'         => $users->firstItem(),
                    'to'           => $users->lastItem(),
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
     * Get single user by ID
     */
    public function show(Request $request)
    {
        try {
            $id    = $request->id;
            $user = User::with('license')->find($id);

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User not found',
                ], 404);
            }

            $user->name = $user->first_name . ' ' . $user->last_name;
            $user->created_at = $user->created_at->format('M d, Y h:i A');
            $user->updated_at = $user->updated_at->format('M d, Y h:i A');
            
            $statusMap = [
                0 => 'Inactive',
                1 => 'Active',
                2 => 'Blocked',
            ];
            $user->status_text = isset($statusMap[$user->status]) ? $statusMap[$user->status] : 'Unknown';
            
            $licenseStatusMap = [
                0 => 'Pending',
                1 => 'Approved',
                2 => 'Rejected',
            ];
            
            if ($user->license) {
                if ($user->license->file) {
                    $user->license->file_url = url($user->license->file);
                }
                
                $user->license_status_text = isset($licenseStatusMap[$user->license->status]) ? $licenseStatusMap[$user->license->status] : 'Unknown';
            } else {
                $user->license_status_text = 'No License';
            }

            return response()->json([
                'status'  => true,
                'message' => 'User retrieved successfully',
                'data'    => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Update user
     */
    public function update(Request $request)
    {
        try {
            $id    = $request->id;
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'first_name'   => 'sometimes|nullable|string|max:255',
                'last_name'    => 'sometimes|nullable|string|max:255',
                'email'        => 'sometimes|nullable|email|unique:users,email,'.$user->id,
                'phone_no'     => 'sometimes|nullable|string|max:20',
                'company_name' => 'sometimes|nullable|string|max:255',
                'address'      => 'sometimes|nullable|string|max:255',
                'city'         => 'sometimes|nullable|string|max:100',
                'state'        => 'sometimes|nullable|string|max:100',
                'zip_code'     => 'sometimes|nullable|string|max:20',
                'password'     => 'sometimes|nullable|string|min:8',
                'status'       => 'sometimes|nullable|in:0,1,2',
            ], [
                'email.email'         => 'The email must be a valid email address.',
                'email.unique'        => 'This email is already registered.',
                'password.min'        => 'The password must be at least 8 characters.',
                'password.confirmed'  => 'The password confirmation does not match.',
                'status.in'           => 'The status must be 0 (Inactive), 1 (Active), or 2 (Blocked).',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            if ($request->has('first_name')) {
                $user->first_name = $request->first_name;
            }
            if ($request->has('last_name')) {
                $user->last_name = $request->last_name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('phone_no')) {
                $user->phone_no = $request->phone_no;
            }
            if ($request->has('company_name')) {
                $user->company_name = $request->company_name;
            }
            if ($request->has('address')) {
                $user->address = $request->address;
            }
            if ($request->has('city')) {
                $user->city = $request->city;
            }
            if ($request->has('state')) {
                $user->state = $request->state;
            }
            if ($request->has('zip_code')) {
                $user->zip_code = $request->zip_code;
            }
            
            if ($request->has('password') && $request->password) {
                $user->password = Hash::make($request->password);
            }
            
            if ($request->has('status')) {
                $user->status = $request->status;
            }

            $user->save();

            $user->name = $user->first_name . ' ' . $user->last_name;

            return response()->json([
                'status'  => true,
                'message' => 'User updated successfully',
                'data'    => $user,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function delete(Request $request)
    {
        try {
            $id    = $request->id;
            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->license) {
                if ($user->license->file) {
                    $licenseFilePath = public_path($user->license->file);
                    if (file_exists($licenseFilePath)) {
                        unlink($licenseFilePath);
                    }
                }
                $user->license->delete();
            }

            $user->delete();

            return response()->json([
                'status'  => true,
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Change user status
     */
    public function changeStatus(Request $request)
    {
        try {
            $id     = $request->id;
            $status = $request->status;

            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User not found',
                ], 404);
            }

            if (!in_array($status, [0, 1, 2])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid status value. Must be 0 (Inactive), 1 (Active), or 2 (Blocked).',
                ], 422);
            }

            $user->status = $status;
            $user->save();

            $statusText = $status == 1 ? 'activated' : ($status == 2 ? 'blocked' : 'deactivated');

            return response()->json([
                'status'  => true,
                'message' => "User {$statusText} successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    /**
     * Manage user license (approve or decline)
     */
    public function manageLicense(Request $request)
    {
        try {
            $id = $request->id;
            $action = $request->action;

            if (!in_array($action, ['approve', 'decline'])) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid action. Must be approve or decline.',
                ], 422);
            }

            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User not found',
                ], 404);
            }

            if (!$user->license) {
                $message = $action === 'approve' ? 'User has no license to approve' : 'User has no license to decline';
                return response()->json([
                    'status'  => false,
                    'message' => $message,
                ], 404);
            }

            if ($action === 'approve') {
                $user->license->status = 1;
                $user->is_license = 1;
                $message = 'License approved successfully';
            } else {
                $user->license->status = 2;
                $user->is_license = 2;
                $message = 'License declined successfully';
            }

            $user->license->save();
            $user->save();

            return response()->json([
                'status'  => true,
                'message' => $message,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }
}