<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BiddingController extends Controller
{
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
                $query->where(function($q) use ($search) {
                    $q->where('year', 'LIKE', "%{$search}%")
                      ->orWhere('make', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%");
                });
            }

            $machineries = $query->withCount(['bids' => function ($q) {
                        $q->whereColumn('bids.auction_id', 'machinery.auction_id');
                    }])
                    ->leftJoin(\Illuminate\Support\Facades\DB::raw('(SELECT machinery_id, auction_id, MAX(amount) as highest_bid FROM bids GROUP BY machinery_id, auction_id) as bid_max'), function ($join) {
                        $join->on('machinery.id', '=', 'bid_max.machinery_id')
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
                        $status = 'sold';
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
                    'last_page'    => $machineries->lastPage(),
                    'per_page'     => $machineries->perPage(),
                    'total'        => $machineries->total(),
                    'from'         => $machineries->firstItem(),
                    'to'           => $machineries->lastItem(),
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

            $machinery = Machinery::with('bids.user:id,first_name,last_name,email,phone_no')->where('id', $machineryId)->first();

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
                    $bidStatusText = 'sold';
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
            ];

            $biddingDetails = $machinery->bids->filter(function($bid) use ($machinery) {
                return $bid->auction_id === $machinery->auction_id;
            })->map(function ($bid) use ($highestBid, $machinery) {
                $bidData = [
                    'user_full_name' => $bid->user->first_name . ' ' . $bid->user->last_name,
                    'user_email' => $bid->user->email,
                    'user_phone' => $bid->user->phone_no,
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
            ->leftJoin('users', 'machinery.won_user', '=', 'users.id')
            ->leftJoin('categories', 'machinery.category_id', '=', 'categories.id')
            ->whereHas('bids')
            ->whereNotNull('machinery.won_user')
            ->where('machinery.won_user', '!=', 0);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('machinery.year', 'LIKE', "%{$search}%")
                      ->orWhere('machinery.make', 'LIKE', "%{$search}%")
                      ->orWhere('machinery.model', 'LIKE', "%{$search}%")
                      ->orWhere('users.first_name', 'LIKE', "%{$search}%")
                      ->orWhere('users.last_name', 'LIKE', "%{$search}%")
                      ->orWhere('users.phone_no', 'LIKE', "%{$search}%")
                      ->orWhere('categories.category_name', 'LIKE', "%{$search}%");
                });
            }

            $query->selectRaw('(SELECT MAX(amount) FROM bids WHERE bids.machinery_id = machinery.id AND bids.auction_id = machinery.auction_id) as won_bid_amount');

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
                    'last_page'    => $wonMachineries->lastPage(),
                    'per_page'     => $wonMachineries->perPage(),
                    'total'        => $wonMachineries->total(),
                    'from'         => $wonMachineries->firstItem(),
                    'to'           => $wonMachineries->lastItem(),
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

            $machinery = Machinery::with(['wonUser:id,first_name,last_name,phone_no', 'category:id,category_name', 'bids'])
                ->whereHas('bids', function($q) {
                    $q->whereColumn('bids.auction_id', 'machinery.auction_id');
                })
                ->where('id', $machineryId)
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
            'machinery_id' => 'required|exists:machinery,id',
            'action' => 'required|in:approve,reject',
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

            $machinery = Machinery::with('bids')->find($machineryId);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            if ($action === 'approve') {
                $machinery->contract_status = 1;

                $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');
                $bidModel = $machinery->bids->where('auction_id', $machinery->auction_id)->sortByDesc('amount')->first();

                if ($bidModel && $machinery->won_user) {
                    $existingOrder = Order::where('machinery_id', $machinery->id)
                                         ->where('user_id', $machinery->won_user)
                                         ->first();

                    if (!$existingOrder) {
                        Order::create([
                            'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                            'machinery_id' => $machinery->id,
                            'user_id' => $machinery->won_user,
                            'price' => $bidModel->amount,
                            'purchase_date' => now(),
                            'delivery_status' => 0,
                            'process_date' => now(),
                        ]);
                    }
                }
            } elseif ($action === 'reject') {
                $machinery->contract_status = 4;
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
}
