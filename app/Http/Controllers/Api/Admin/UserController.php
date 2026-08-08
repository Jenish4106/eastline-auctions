<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\License;
use App\Mail\LicenseApprovedMail;
use App\Mail\LicenseDeclinedMail;
use App\Services\MailtrapService;
use App\Services\TwilioSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            ]);


            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", ["%{$search}%"])
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone_no', 'LIKE', "%{$search}%")
                      ->orWhere('company_name', 'LIKE', "%{$search}%")
                      ->orWhere('address', 'LIKE', "%{$search}%")
                      ->orWhere('city', 'LIKE', "%{$search}%")
                      ->orWhere('state', 'LIKE', "%{$search}%")
                      ->orWhere('zip_code', 'LIKE', "%{$search}%");

                    $statusMap = ['inactive' => 0, 'active' => 1, 'blocked' => 2];
                    foreach ($statusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('status', $value);
                        }
                    }

                    $licenseStatusMap = ['pending' => 0, 'approved' => 1, 'rejected' => 2];
                    foreach ($licenseStatusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('is_license', $value);
                        }
                    }
                });
            }

            $users = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            foreach ($users->items() as $user) {
                $user->latest_license = $user->license()->latest()->first();
            }

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
                
                $latestLicense = $user->latest_license;
                $user->license_status = $latestLicense ? $latestLicense->status : null;
                $user->license_status_text = $latestLicense ? (isset($licenseStatusMap[$latestLicense->status]) ? $licenseStatusMap[$latestLicense->status] : 'Unknown') : 'No License';
                
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
                'data'       => $usersWithFormattedData->makeHidden(['updated_at']),
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
            $user = User::find($id);
            $latestLicense = $user->license()->latest()->first();

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
            
            if ($latestLicense) {
                if ($latestLicense->front_side) {
                    $latestLicense->front_side_url = url($latestLicense->front_side);
                }
                if ($latestLicense->back_side) {
                    $latestLicense->back_side_url = url($latestLicense->back_side);
                }
                
                $user->license_status_text = isset($licenseStatusMap[$latestLicense->status]) ? $licenseStatusMap[$latestLicense->status] : 'Unknown';
                $user->license = $latestLicense;
            } else {
                $user->license_status_text = 'No License';
                $user->license = null;
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

            $wonMachines = \App\Models\Machinery::where('won_user', $user->id)->get();
            foreach ($wonMachines as $machine) {
                $orderCreated = \App\Models\Order::where('machinery_id', $machine->id)->exists();
                
                if ($machine->contract_status != 3 && !$orderCreated) {
                    $topBid = \App\Models\Bid::where('machinery_id', $machine->id)
                                             ->orderBy('amount', 'desc')
                                             ->first();
                                             
                    if ($topBid && $topBid->user_id == $user->id) {
                        \App\Models\Bid::where('machinery_id', $machine->id)
                                       ->where('user_id', $user->id)
                                       ->delete();
                        
                        $nextTopBid = \App\Models\Bid::where('machinery_id', $machine->id)
                                                     ->orderBy('amount', 'desc')
                                                     ->first();
                        
                        $files = \App\Models\MachineryFileManager::where('machinery_id', $machine->id)
                            ->whereIn('type', ['contract_pdf', 'invoice'])
                            ->get();
                        $disk = config('filesystems.default', 's3');
                        foreach ($files as $file) {
                            if ($disk === 's3') {
                                if (Storage::disk('s3')->exists($file->image_path)) {
                                    Storage::disk('s3')->delete($file->image_path);
                                }
                            } else {
                                $filePath = public_path($file->image_path);
                                if (file_exists($filePath)) {
                                    @unlink($filePath);
                                }
                            }
                            $file->delete();
                        }
                                                     
                        if ($nextTopBid) {
                            $machine->won_user = $nextTopBid->user_id;
                            $machine->bid_won_date = \Carbon\Carbon::now();
                            $machine->contract_status = 0;
                            $machine->bid_status = '2';
                            $machine->status = 2;
                        } else {
                            $machine->won_user = null;
                            $machine->bid_won_date = null;
                            $machine->contract_status = 0;
                            $machine->bid_status = '0';
                            $machine->status = 1;
                        }
                        $machine->save();
                    }
                }
            }

            if ($user->license) {
                $disk = config('filesystems.default', 's3');
                if ($user->license->front_side) {
                    if ($disk === 's3') {
                        if (Storage::disk('s3')->exists($user->license->front_side)) {
                            Storage::disk('s3')->delete($user->license->front_side);
                        }
                    } else {
                        $licenseFilePath = public_path($user->license->front_side);
                        if (file_exists($licenseFilePath)) {
                            unlink($licenseFilePath);
                        }
                    }
                }
                if ($user->license->back_side) {
                    if ($disk === 's3') {
                        if (Storage::disk('s3')->exists($user->license->back_side)) {
                            Storage::disk('s3')->delete($user->license->back_side);
                        }
                    } else {
                        $licenseFilePath = public_path($user->license->back_side);
                        if (file_exists($licenseFilePath)) {
                            unlink($licenseFilePath);
                        }
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

            $latestLicense = $user->license()->latest()->first();
            
            if (!$latestLicense) {
                $message = $action === 'approve' ? 'User has no license to approve' : 'User has no license to decline';
                return response()->json([
                    'status'  => false,
                    'message' => $message,
                ], 404);
            }

            if ($action === 'approve') {
                $latestLicense->status = 1;
                $latestLicense->is_sumsub = 0;
                $user->is_license = 1;
                $message = 'License approved successfully';

                (new TwilioSmsService())->sendMessage(
                    $user->phone_no,
                    'Welcome to Mcfarland Equipment Sales & Auctions! Your registration is complete. Start browsing, bidding, or use Buy It Now.'
                );
                
                try {
                    $mail = new LicenseApprovedMail($user);
                    $mailtrapService = new MailtrapService();
                    $htmlContent = $mail->renderHtmlContent();
                    $mailtrapService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
                } catch (\Exception $e) {
                    return response()->json([
                        'status'  => true,
                        'message' => 'License approved but failed to send email notification.',
                    ], 200);
                }
            } else {
                $latestLicense->status = 2;
                $latestLicense->is_sumsub = 0;
                $user->is_license = 2;
                $message = 'License declined successfully';
                
                try {
                    $mail = new LicenseDeclinedMail($user);
                    $mailtrapService = new MailtrapService();
                    $htmlContent = $mail->renderHtmlContent();
                    $mailtrapService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
                } catch (\Exception $e) {
                    return response()->json([
                        'status'  => true,
                        'message' => 'License declined but failed to send email notification.',
                    ], 200);
                }
            }

            $latestLicense->save();
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
