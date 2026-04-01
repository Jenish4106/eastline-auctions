<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use App\Models\Order;
use App\Models\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\SMTP2GOService;
use Illuminate\Support\Facades\View;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Services\AuctionCompletionService;

class BiddingController extends Controller
{
    protected $auctionCompletionService;

    public function __construct(AuctionCompletionService $auctionCompletionService)
    {
        $this->auctionCompletionService = $auctionCompletionService;
    }

    public function getMachineryBiddingInfo(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'machinery.created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['machinery.id', 'machinery.year', 'machinery.make', 'machinery.model', 'machinery.bid_end_time', 'machinery.bid_start_price', 'machinery.bid_status', 'bids_count'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'machinery.created_at';
            }

            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Machinery::select([
                'machinery.id',
                'machinery.auction_id',
                'machinery.year',
                'machinery.make',
                'machinery.model',
                'machinery.bid_end_time',
                'machinery.bid_start_price',
                'machinery.bid_status'
            ]);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('machinery.auction_id', 'LIKE', "%{$search}%")
                        ->orWhere('machinery.year', 'LIKE', "%{$search}%")
                        ->orWhere('machinery.make', 'LIKE', "%{$search}%")
                        ->orWhere('machinery.model', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT_WS(' ', machinery.year, machinery.make, machinery.model) LIKE ?", ["%{$search}%"]);

                    $statusMap = ['pending' => '0', 'active' => '1', 'completed' => '2', 'cancelled' => '3'];
                    foreach ($statusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('machinery.bid_status', $value);
                        }
                    }
                });
            }

            $machineries = $query
                ->withCount(['bids' => function ($q) {
                    $q->whereColumn('bids.auction_id', 'machinery.auction_id')
                        ->whereHas('user');
                }])
                ->leftJoin(\Illuminate\Support\Facades\DB::raw('(SELECT bids.machinery_id, bids.auction_id, MAX(bids.amount) as highest_bid FROM bids INNER JOIN users ON bids.user_id = users.id GROUP BY bids.machinery_id, bids.auction_id) as bid_max'), function ($join) {
                    $join
                        ->on('machinery.id', '=', 'bid_max.machinery_id')
                        ->on('machinery.auction_id', '=', 'bid_max.auction_id');
                })
                ->selectRaw('machinery.*, bid_max.highest_bid')
                ->orderBy($sortBy === 'bids_count' ? 'bids_count' : $sortBy, $sortOrder)
                ->paginate($perPage, ['*'], 'page', $page);

            $result = $machineries->getCollection()->map(function ($machinery) {
                $name = trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model);

                switch ($machinery->bid_status) {
                    case '1':
                    case 1:
                        $status = 'active';
                        break;
                    case '2':
                    case 2:
                        $status = 'completed';
                        break;
                    case '3':
                    case 3:
                        $status = 'cancelled';
                        break;
                    case '0':
                    case 0:
                        $status = 'pending';
                        break;
                    default:
                        $status = $machinery->bid_status;
                        break;
                }

                return [
                    'id' => $machinery->id,
                    'auction_id' => $machinery->auction_id,
                    'year' => $machinery->year,
                    'make' => $machinery->make,
                    'model' => $machinery->model,
                    'bid_end_time' => $machinery->bid_end_time,
                    'bid_start_price' => $machinery->bid_start_price,
                    'highest_bid' => $machinery->highest_bid,
                    'bid_status' => $status,
                    'bids_count' => $machinery->bids_count,
                    'name' => $name,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'pagination' => [
                    'current_page' => $machineries->currentPage(),
                    'last_page' => $machineries->lastPage(),
                    'per_page' => $machineries->perPage(),
                    'total' => $machineries->total(),
                    'from' => $machineries->firstItem(),
                    'to' => $machineries->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getMachineryBiddingDetails(Request $request)
    {
        try {
            $machineryId = $request->input('machineryId');

            $sortBy = $request->input('sort_by', 'amount');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['amount', 'created_at', 'user_full_name', 'user_email', 'user_phone'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'amount';
            }

            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $machinery = Machinery::with([
                'bids' => function ($q) {
                    $q->whereHas('user')
                        ->with('user:id,first_name,last_name,email,phone_no');
                }
            ])->where('id', $machineryId)->first();

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');

            $bidStatusText = '';
            switch ($machinery->bid_status) {
                case '0':
                case 0:
                    $bidStatusText = 'pending';
                    break;
                case '1':
                case 1:
                    $bidStatusText = 'active';
                    break;
                case '2':
                case 2:
                    $bidStatusText = 'completed';
                    break;
                case '3':
                case 3:
                    $bidStatusText = 'cancelled';
                    break;
                default:
                    $bidStatusText = $machinery->bid_status;
                    break;
            }

            $machineryInfo = [
                'name' => trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model),
                'bid_start_price' => $machinery->bid_start_price,
                'highest_bid' => $highestBid,
                'bid_end_time' => $machinery->bid_end_time,
                'bid_status' => $bidStatusText,
                'status' => $machinery->status,
            ];

            $biddingDetails = $machinery->bids->filter(function ($bid) use ($machinery) {
                return $bid->auction_id === $machinery->auction_id;
            })->map(function ($bid) use ($highestBid, $machinery) {
                $bidData = [
                    'id' => $bid->id,
                    'user_full_name' => trim(($bid->user->first_name ?? '') . ' ' . ($bid->user->last_name ?? '')),
                    'user_email' => $bid->user->email ?? '',
                    'user_phone' => $bid->user->phone_no ?? '',
                    'bid_amount' => $bid->amount,
                    'auction_id' => $bid->auction_id,
                    'bid_created_at' => $bid->created_at,
                    'is_highest' => $bid->amount == $highestBid,
                ];

                if ($machinery->bid_status == '2' || $machinery->bid_status == 2) {
                    $bidData['is_won'] = $bid->amount == $highestBid;
                }

                return $bidData;
            });

            switch ($sortBy) {
                case 'amount':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('bid_amount') : $biddingDetails->sortBy('bid_amount');
                    break;
                case 'created_at':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('bid_created_at') : $biddingDetails->sortBy('bid_created_at');
                    break;
                case 'user_full_name':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('user_full_name') : $biddingDetails->sortBy('user_full_name');
                    break;
                case 'user_email':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('user_email') : $biddingDetails->sortBy('user_email');
                    break;
                case 'user_phone':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('user_phone') : $biddingDetails->sortBy('user_phone');
                    break;
                default:
                    $biddingDetails = $biddingDetails->sortByDesc('bid_amount');
                    break;
            }

            $biddingDetails = $biddingDetails->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'machinery_info' => $machineryInfo,
                    'bidding_details' => $biddingDetails,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getBiddingWonUsers(Request $request)
    {
        try {
            $search = $request->input('search', '');

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'machinery.created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['machinery.id', 'machinery.year', 'machinery.make', 'machinery.model', 'machinery.contract_status', 'users.first_name', 'users.last_name', 'users.phone_no', 'categories.category_name', 'machinery.created_at', 'machinery.won_bid_amount', 'machinery_name', 'user_full_name'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'machinery.created_at';
            }

            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Machinery::select([
                'machinery.id as machinery_id',
                'machinery.auction_id',
                'machinery.year',
                'machinery.make',
                'machinery.model',
                'machinery.contract_status',
                'machinery.won_user',
                'machinery.created_at',
                'users.first_name',
                'users.last_name',
                'users.phone_no',
                'categories.category_name',
            ])
                ->join('users', 'machinery.won_user', '=', 'users.id')
                ->leftJoin('categories', 'machinery.category_id', '=', 'categories.id')
                ->whereHas('wonUser')
                ->whereHas('bids', function ($q) {
                    $q->whereHas('user')
                        ->whereColumn('bids.auction_id', 'machinery.auction_id');
                })
                ->whereNotNull('machinery.won_user')
                ->where('machinery.won_user', '!=', 0);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('machinery.auction_id', 'LIKE', "%{$search}%")
                        ->orWhere('machinery.year', 'LIKE', "%{$search}%")
                        ->orWhere('machinery.make', 'LIKE', "%{$search}%")
                        ->orWhere('machinery.model', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT_WS(' ', machinery.year, machinery.make, machinery.model) LIKE ?", ["%{$search}%"])
                        ->orWhere('users.first_name', 'LIKE', "%{$search}%")
                        ->orWhere('users.last_name', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT_WS(' ', users.first_name, users.last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('users.phone_no', 'LIKE', "%{$search}%")
                        ->orWhere('categories.category_name', 'LIKE', "%{$search}%");

                    // Contract Status search
                    $contractStatusMap = ['pending' => 0, 'approved' => 1, 'signed' => 3, 'rejected' => 4];
                    foreach ($contractStatusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('machinery.contract_status', $value);
                        }
                    }
                });
            }

            $query->selectRaw('(SELECT MAX(bids.amount) FROM bids INNER JOIN users as bid_users ON bids.user_id = bid_users.id WHERE bids.machinery_id = machinery.id AND bids.auction_id = machinery.auction_id) as won_bid_amount');

            if ($sortBy === 'machinery_name') {
                $wonMachineries = $query->orderByRaw('CONCAT(machinery.year, " ", machinery.make, " ", machinery.model) ' . $sortOrder)->paginate($perPage, ['*'], 'page', $page);
            } elseif ($sortBy === 'user_full_name') {
                $wonMachineries = $query->orderByRaw('CONCAT(users.first_name, " ", users.last_name) ' . $sortOrder)->paginate($perPage, ['*'], 'page', $page);
            } else {
                $wonMachineries = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);
            }

            $result = $wonMachineries->getCollection()->map(function ($machinery) {
                $contractStatusMap = [
                    0 => 'Pending',
                    1 => 'Approved',
                    3 => 'Signed',
                    4 => 'Rejected',
                ];

                return [
                    'machinery_id' => $machinery->machinery_id,
                    'auction_id' => $machinery->auction_id,
                    'machinery_name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                    'contract_status' => isset($contractStatusMap[$machinery->contract_status]) ? $contractStatusMap[$machinery->contract_status] : 'Unknown',
                    'user_full_name' => trim(($machinery->first_name ?? '') . ' ' . ($machinery->last_name ?? '')),
                    'phone_no' => $machinery->phone_no ?? '',
                    'category' => $machinery->category_name ?? 'Uncategorized',
                    'won_bid_amount' => $machinery->won_bid_amount,
                    'contract_file_url' => $machinery->contract_url,
                ];
            });

            if ($result->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No bidding won users found',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'pagination' => [
                    'current_page' => $wonMachineries->currentPage(),
                    'last_page' => $wonMachineries->lastPage(),
                    'per_page' => $wonMachineries->perPage(),
                    'total' => $wonMachineries->total(),
                    'from' => $wonMachineries->firstItem(),
                    'to' => $wonMachineries->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function deleteBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bid_id' => 'required|exists:bids,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $bid = Bid::find($request->bid_id);

            if (!$bid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid not found',
                ], 404);
            }

            $machinery = Machinery::with(['bids' => function ($q) {
                $q->whereHas('user');
            }])->where('id', $bid->machinery_id)->first();

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $bids = $machinery->bids->where('auction_id', $machinery->auction_id);

            $highestBid = $bids->sortByDesc('amount')->first();

            $isHighest = $highestBid && $highestBid->id == $bid->id;

            $bid->delete();

            if ($isHighest) {
                $remainingBids = Bid::where('machinery_id', $machinery->id)
                    ->where('auction_id', $machinery->auction_id)
                    ->whereHas('user')
                    ->orderByDesc('amount')
                    ->get();

                $nextHighest = $remainingBids->first();

                if ($nextHighest) {
                    $machinery->won_user = $nextHighest->user_id;
                    $machinery->bid_won_date = \Carbon\Carbon::now();
                    $machinery->contract_status = 0;
                    $machinery->bid_status = 2;
                    $machinery->status = 2;
                } else {
                    $machinery->bid_status = 1;
                    $machinery->won_user = null;
                    $machinery->bid_won_date = null;
                    $machinery->contract_status = 0;
                    $machinery->status = 1;
                }

                $machinery->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Bid deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function updateBidStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
            'bid_status' => 'required|in:0,1,2,3',
        ], [
            'machinery_id.required' => 'The machinery id field is required.',
            'machinery_id.exists' => 'The selected machinery does not exist.',
            'bid_status.required' => 'The bid status field is required.',
            'bid_status.in' => 'The bid status must be 0 (Pending), 1 (Active), 2 (Completed), or 3 (Cancelled).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $machinery = Machinery::find($request->machinery_id);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $bidStatus = (string) $request->bid_status;

            if ($bidStatus === '2') {
                $result = $this->auctionCompletionService->complete($machinery);
                $machinery = $result['machinery'];
            } else {
                $machinery->bid_status = $bidStatus;
                if ($bidStatus === '3') {
                    $machinery->status = 2;
                }
                $machinery->save();
            }

            $statusMap = [
                '0' => 'pending',
                '1' => 'active',
                '2' => 'completed',
                '3' => 'cancelled',
            ];

            return response()->json([
                'success' => true,
                'message' => 'Bid status updated successfully',
                'data' => [
                    'machinery_id' => $machinery->id,
                    'auction_id' => $machinery->auction_id,
                    'bid_status' => $machinery->bid_status,
                    'bid_status_text' => $statusMap[(string) $machinery->bid_status] ?? 'unknown',
                    'won_user' => $machinery->won_user,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getMachineryWiseWonDetails(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $machineryId = $request->machinery_id;

            $machinery = Machinery::with([
                'wonUser:id,first_name,last_name,phone_no',
                'category:id,category_name',
                'bids' => function ($q) {
                    $q->whereHas('user');
                }
            ])
                ->whereHas('bids', function ($q) {
                    $q->whereColumn('bids.auction_id', 'machinery.auction_id')
                        ->whereHas('user');
                })
                ->where('id', $machineryId)
                ->whereHas('wonUser')
                ->whereNotNull('won_user')
                ->where('won_user', '!=', 0)
                ->first();

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found or has no winning bidder',
                ], 404);
            }

            $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');

            $contractStatusMap = [
                0 => 'Pending',
                1 => 'Approved',
                3 => 'Signed',
                4 => 'Rejected',
            ];

            $result = [
                'machinery_id' => $machinery->id,
                'auction_id' => $machinery->auction_id,
                'machinery_name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                'contract_status' => isset($contractStatusMap[$machinery->contract_status]) ? $contractStatusMap[$machinery->contract_status] : 'Unknown',
                'user_full_name' => trim(($machinery->wonUser->first_name ?? '') . ' ' . ($machinery->wonUser->last_name ?? '')),
                'phone_no' => $machinery->wonUser->phone_no ?? '',
                'category' => $machinery->category ? $machinery->category->category_name : 'Uncategorized',
                'won_bid_amount' => $highestBid,
                'contract_file_url' => $machinery->contract_url,
            ];

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function updateContractStatus(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'machinery_id'   => 'required|exists:machinery,id',
            'action'         => 'required|in:approve,reject',
            'bank_name'              => 'nullable|string|max:255',
            'beneficiary_name'       => 'nullable|string|max:255',
            'beneficiary_address'    => 'nullable|string',
            'account_number'         => 'nullable|string|max:50',
            'routing_number'         => 'nullable|string|max:50',
            'branch_address'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $machineryId = $request->machinery_id;
            $action = $request->action;

            $machinery = Machinery::with([
                'bids' => function ($q) {
                    $q->whereHas('user');
                },
                'wonUser'
            ])->find($machineryId);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            if ($action === 'approve' && $machinery->won_user && !$machinery->wonUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Winning user not found',
                ], 404);
            }

            if ($action === 'approve') {
                $machinery->contract_status = 1;

                $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');
                $bidModel = $machinery->bids->where('auction_id', $machinery->auction_id)->sortByDesc('amount')->first();

                if ($bidModel && $machinery->wonUser) {
                    $existingOrder = Order::where('machinery_id', $machinery->id)
                        ->where('user_id', $machinery->wonUser->id)
                        ->first();

                    if (!$existingOrder) {
                        $existingOrder = Order::create([
                            'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                            'machinery_id' => $machinery->id,
                            'user_id' => $machinery->wonUser->id,
                            'type' => 2,
                            'price' => $bidModel->amount,
                            'purchase_date' => now(),
                            'delivery_status' => 2,
                            'awaiting_invoice_date' => now(),
                        ]);
                    } else {
                        $existingOrder->update([
                            'delivery_status' => 2,
                            'awaiting_invoice_date' => now()
                        ]);
                    }

                    // Generate Invoice
                    $companyName = Settings::get('company_name', 'Stiopa Equipment');
                    $companyAddress = Settings::get('address') ?? '';
                    $companyPhone = Settings::get('phone_no') ?? '';
                    $companyEmail = Settings::get('email') ?? '';
                    $companyLogo = Settings::get('dark_logo');

                    $machinery->load('images');
                    $firstImage = $machinery->images->firstWhere('type', 'image');
                    $machineryImage = null;
                    $machineryImageUrl = null;

                    if ($firstImage) {
                        $imagePathRel = 'uploads/machinery/images/' . ltrim($firstImage->image_path, '/');
                        if (File::exists(public_path($imagePathRel))) {
                            $machineryImage = $this->imageToBase64(public_path($imagePathRel));  // For PDF generation
                            $machineryImageUrl = asset($imagePathRel);  // For Frontend display
                        }
                    }

                    $invoiceData = [
                        'order' => $existingOrder,
                        'machineryImage' => $machineryImage,
                        'machineryImageUrl' => $machineryImageUrl,
                        'companyInfo' => [
                            'name' => $companyName,
                            'address' => $companyAddress,
                            'phone' => $companyPhone,
                            'email' => $companyEmail,
                            'logo' => $companyLogo && File::exists(public_path($companyLogo)) ? $this->imageToBase64(public_path($companyLogo)) : null,
                            'logoUrl' => $companyLogo ? asset($companyLogo) : null,
                            'bank_name' => $request->input('bank_name', null),
                            'beneficiary_name' => $request->input('beneficiary_name', null),
                            'beneficiary_address' => $request->input('beneficiary_address', null),
                            'account_number' => $request->input('account_number', null),
                            'routing_number' => $request->input('routing_number', null),
                            'branch_address' => $request->input('branch_address', null),
                        ]
                    ];

                    $invoicePdf = Pdf::loadView('pdf.invoice', $invoiceData);
                    $invoiceFileName = 'invoice_' . $existingOrder->order_id . '.pdf';
                    $invoicePath = 'uploads/invoices/' . $invoiceFileName;

                    $invoicePublicDir = public_path('uploads/invoices');
                    if (!File::exists($invoicePublicDir)) {
                        File::makeDirectory($invoicePublicDir, 0755, true);
                    }

                    $invoicePdf->save(public_path($invoicePath));

                    MachineryFileManager::create([
                        'machinery_id' => $machinery->id,
                        'order_id' => $existingOrder->id,
                        'image_path' => $invoicePath,
                        'type' => 'invoice',
                    ]);

                    $user = $machinery->wonUser;
                    if ($user) {
                        $machineryName = trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model);
                        $htmlContent = View::make('emails.contract-approved', [
                            'user' => $user,
                            'machineryName' => $machineryName
                        ])->render();
                        
                        $attachments = [];
                        
                        if (File::exists(public_path($invoicePath))) {
                            $attachments[] = [
                                'path' => public_path($invoicePath),
                                'name' => $invoiceFileName,
                                'type' => 'application/pdf',
                            ];
                        }
                        
                        $contractFile = MachineryFileManager::where('machinery_id', $machinery->id)
                            ->where('type', 'contract_pdf')
                            ->latest()
                            ->first();
                            
                        if ($contractFile && File::exists(public_path($contractFile->image_path))) {
                            $contractFilePath = public_path($contractFile->image_path);
                            $attachments[] = [
                                'path' => $contractFilePath,
                                'name' => 'contract_' . basename($contractFilePath),
                                'type' => 'application/pdf',
                            ];
                        }
                        
                        $smtp2goService = new SMTP2GOService();
                        $smtp2goService->sendEmail(
                            $user->email, 
                            'Contract Approved - ' . $machineryName, 
                            $htmlContent, 
                            $attachments
                        );
                    }
                }
            } elseif ($action === 'reject') {
                $machinery->contract_status = 4;

                if ($machinery->won_user) {
                    $existingOrder = Order::where('machinery_id', $machinery->id)
                        ->where('user_id', $machinery->won_user)
                        ->first();
                    if ($existingOrder) {
                        $existingOrder->update([
                            'delivery_status' => 9,
                            'cancelled_date' => now()
                        ]);
                    }

                    $user = $machinery->wonUser;
                    if ($user) {
                        $machineryName = trim($machinery->year . ' ' . $machinery->make . ' ' . $machinery->model);
                        $htmlContent = View::make('emails.contract-rejected', [
                            'user' => $user,
                            'machineryName' => $machineryName
                        ])->render();
                        
                        $smtp2goService = new SMTP2GOService();
                        $smtp2goService->sendEmail(
                            $user->email, 
                            'Contract Rejected - ' . $machineryName, 
                            $htmlContent,
                            []
                        );
                    }
                }
            }

            $machinery->save();

            $actionText = $action === 'approve' ? 'approved' : 'rejected';

            return response()->json([
                'success' => true,
                'message' => "Contract has been {$actionText} successfully",
                'data' => [
                    'machinery_id' => $machinery->id,
                    'contract_status' => $machinery->contract_status,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    private function imageToBase64($path)
    {
        if (File::exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        return null;
    }
}
