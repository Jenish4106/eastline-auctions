<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use App\Models\Order;
use App\Mail\BiddingMail;
use App\Mail\OutbidMail;
use App\Mail\BuyNowOrderMail;
use App\Services\SMTP2GOService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Settings;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class BiddingController extends Controller
{
    public function placeBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $user = auth('api')->user();

            if ($user->is_license == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your license is not verified.',
                ], 403);
            }

            $machinery = Machinery::find($request->machinery_id);

            $previousHighestBid = Bid::where('machinery_id', $request->machinery_id)
                            ->orderBy('amount', 'desc')
                            ->first();

            $highestBidAmount = $previousHighestBid ? $previousHighestBid->amount : $machinery->bid_start_price;
            $minAmount = $highestBidAmount;

            if ($request->amount <= $minAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bid amount must be greater than the current highest bid of ' . $minAmount,
                ], 400);
            }

            $bid = Bid::create([
                'user_id' => $user->id,
                'machinery_id' => $request->machinery_id,
                'amount' => $request->amount,
            ]);

            if($machinery->bid_status == 0){
                $machinery->update([
                    'bid_status' => 1
                ]);
            }

            try {
                $mail = new BiddingMail($user, $machinery, $request->amount);
                $smtp2goService = new SMTP2GOService();
                $htmlContent = $mail->renderHtmlContent();
                $smtp2goService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
            } catch (\Exception $e) {
                \Log::error('Failed to send bidding email: ' . $e->getMessage());
            }

            if ($previousHighestBid && $previousHighestBid->user_id != $user->id) {
                try {
                    $previousBidder = User::find($previousHighestBid->user_id);
                    if ($previousBidder) {
                        $outbidMail = new OutbidMail($previousBidder, $machinery, $request->amount);
                        $smtp2goService = new SMTP2GOService();
                        $htmlContent = $outbidMail->renderHtmlContent();
                        $smtp2goService->sendEmail($previousBidder->email, $outbidMail->getSubject(), $htmlContent);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send outbid email: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bid placed successfully.',
                'bid' => $bid,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getMachineryWithBids(Request $request)
    {
        try {
            $user = auth('api')->user();

            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['id', 'year', 'make', 'model', 'bid_start_price', 'bid_end_time', 'created_at'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Machinery::whereHas('bids', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with(['images', 'bids.user']);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('year', 'LIKE', "%{$search}%")
                      ->orWhere('make', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%")
                      ->orWhere('bid_start_price', 'LIKE', "%{$search}%")
                      ->orWhere('bid_end_time', 'LIKE', "%{$search}%");
                });
            }

            $machineries = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $machineriesWithFormattedData = $machineries->getCollection()->map(function ($machinery) use ($user) {
                $bids = $machinery->bids;
                $highestBid = $bids->max('amount');
                $lastBid = $highestBid ?: $machinery->bid_start_price;

                $currentUserBids = $bids->where('user_id', $user->id);
                $currentUserHighestBid = $currentUserBids->max('amount');

                $status = 'Active';

                if ($machinery->bid_status === 'sold' || $machinery->won_user) {
                    if ($machinery->won_user == $user->id) {
                        $status = 'won';
                    } else {
                        $status = 'sold';
                    }
                } else {
                    if ($currentUserBids->count() > 0) {
                        if ($currentUserHighestBid >= $lastBid) {
                            $status = 'Active';
                        } else {
                            $status = 'Outbid';
                        }
                    } else {
                        $status = 'Active';
                    }
                }

                $firstImage = $machinery->images->firstWhere('type', 'image');

                return [
                    'id' => $machinery->id,
                    'name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                    'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
                    'bid_start_price' => $machinery->bid_start_price,
                    'last_bid' => $lastBid,
                    'bid_end_time' => $machinery->bid_end_time,
                    'status' => $status,
                ];
            });

            if ($machineriesWithFormattedData->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No machinery with bids found',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $machineriesWithFormattedData,
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
        $machineryId = $request->machineryId;

        $sortBy = $request->input('sort_by', 'amount');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['amount', 'created_at', 'user_full_name', 'my_bid'];
        $allowedSortOrders = ['asc', 'desc'];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'amount';
        }

        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'desc';
        }

        try {
            $user = auth('api')->user();

            $machinery = Machinery::with(['images', 'bids.user'])->find($machineryId);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $bids = $machinery->bids;

            $highestBid = $bids->max('amount');
            $lastBid = $highestBid ?: $machinery->bid_start_price;

            $currentUserBids = $bids->where('user_id', $user->id);
            $currentUserHighestBid = $currentUserBids->max('amount');

            $status = 'Active';

            if ($machinery->bid_status === 'sold' || $machinery->won_user) {
                if ($machinery->won_user == $user->id) {
                    $status = 'won';
                } else {
                    $status = 'sold';
                }
            } else {
                if ($currentUserBids->count() > 0) {
                    if ($currentUserHighestBid >= $lastBid) {
                        $status = 'Active';
                    } else {
                        $status = 'Outbid';
                    }
                } else {
                    $status = 'Active';
                }
            }

            $firstImage = $machinery->images->firstWhere('type', 'image');

            $machineryDetails = [
                'machinery_name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                'bid_end_time' => $machinery->bid_end_time,
                'start_bid_price' => $machinery->bid_start_price,
                'highest_bid' => $highestBid,
                'my_bid' => $currentUserHighestBid,
                'user_full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'status' => $status,
                'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
            ];

            $biddingDetails = $bids->map(function ($bid) use ($user) {
                return [
                    'user_full_name' => trim(($bid->user->first_name ?? '')),
                    'amount' => $bid->amount,
                    'bid_date_time' => $bid->created_at,
                    'my_bid' => $bid->user_id == $user->id,
                ];
            });

            switch ($sortBy) {
                case 'amount':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('amount') : $biddingDetails->sortBy('amount');
                    break;
                case 'created_at':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('bid_date_time') : $biddingDetails->sortBy('bid_date_time');
                    break;
                case 'user_full_name':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('user_full_name') : $biddingDetails->sortBy('user_full_name');
                    break;
                case 'my_bid':
                    $biddingDetails = $sortOrder === 'desc' ? $biddingDetails->sortByDesc('my_bid') : $biddingDetails->sortBy('my_bid');
                    break;
                default:
                    $biddingDetails = $biddingDetails->sortByDesc('amount');
                    break;
            }

            $biddingDetails = $biddingDetails->values();

            return response()->json([
                'success' => true,
                'machinery_details' => $machineryDetails,
                'bidding_details' => $biddingDetails,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getUserWonBids(Request $request)
    {
        try {
            $user = auth('api')->user();

            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'bid_won_date');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['id', 'year', 'make', 'model', 'won_bid_amount', 'bid_won_date', 'contract_status'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'bid_won_date';
            }

            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Machinery::where('won_user', $user->id)
                ->with(['images', 'category', 'bids']);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('year', 'LIKE', "%{$search}%")
                      ->orWhere('make', 'LIKE', "%{$search}%")
                      ->orWhere('model', 'LIKE', "%{$search}%")
                      ->orWhere('bid_won_date', 'LIKE', "%{$search}%")
                      ->orWhereHas('category', function($q2) use ($search) {
                          $q2->where('category_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $wonMachinery = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $wonMachineryWithFormattedData = $wonMachinery->getCollection()->map(function ($machinery) {
                $userWonBid = $machinery->bids->where('user_id', auth('api')->id())->max('amount');

                $firstImage = $machinery->images->firstWhere('type', 'image');

                $contractStatusMap = [
                    0 => 'Pending',
                    1 => 'Approved',
                    3 => 'Signed',
                    4 => 'Rejected',
                ];

                return [
                    'id' => $machinery->id,
                    'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
                    'machinery_name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                    'category' => $machinery->category ? $machinery->category->category_name : 'Uncategorized',
                    'won_bid_amount' => $userWonBid,
                    'won_date' => $machinery->bid_won_date,
                    'contract_status' => isset($contractStatusMap[$machinery->contract_status]) ? $contractStatusMap[$machinery->contract_status] : 'Unknown',
                ];
            });

            if ($wonMachineryWithFormattedData->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No won bids found',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $wonMachineryWithFormattedData,
                'pagination' => [
                    'current_page' => $wonMachinery->currentPage(),
                    'last_page'    => $wonMachinery->lastPage(),
                    'per_page'     => $wonMachinery->perPage(),
                    'total'        => $wonMachinery->total(),
                    'from'         => $wonMachinery->firstItem(),
                    'to'           => $wonMachinery->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getSingleWonBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machineryId' => 'required|exists:machinery,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $user = auth('api')->user();

            $machineryId = $request->machineryId;

            $machinery = Machinery::with(['category', 'bids'])->find($machineryId);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            if ($machinery->won_user != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this contract',
                ], 403);
            }

            $highestBid = $machinery->bids->max('amount');

            $contractStatusMap = [
                0 => 'Pending',
                1 => 'Approved',
                3 => 'Signed',
                4 => 'Rejected',
            ];

            $winningUser = User::find($machinery->won_user);

            $highestBidModel = $machinery->bids->sortByDesc('amount')->first();

            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $highestBidModel,
                'user' => $winningUser,
                'companyInfo' => [
                    'name' => Settings::get('company_name',),
                    'address' => Settings::get('address'),
                    'phone' => Settings::get('phone_no'),
                    'email' => Settings::get('email'),
                ],
                'contractDate' => now()->format('Y-m-d'),
            ];

            $contractHtml = View::make('pdf.contract', $contractDataView)->render();

            $contractData = [
                'id' => $machinery->id,
                'name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                'category' => $machinery->category ? $machinery->category->category_name : 'Uncategorized',
                'start_bid_price' => $machinery->bid_start_price,
                'won_bid_amount' => $highestBid,
                'status' => isset($contractStatusMap[$machinery->contract_status]) ? $contractStatusMap[$machinery->contract_status] : 'Unknown',
                'contract_html' => $contractHtml,
            ];

            return response()->json([
                'success' => true,
                'data' => $contractData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function addSignatureToContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
            'sign_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $user = auth('api')->user();

            $machineryId = $request->machinery_id;

            $machinery = Machinery::with(['category', 'bids'])->find($machineryId);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            if ($machinery->won_user != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to sign this contract',
                ], 403);
            }

            $signatureImage = $request->file('sign_photo');

            $signatureDirectory = public_path('uploads/signatures');
            if (!File::exists($signatureDirectory)) {
                File::makeDirectory($signatureDirectory, 0755, true);
            }

            $signatureFileName = time() . '_' . $signatureImage->getClientOriginalName();
            $signaturePath = 'uploads/signatures/' . $signatureFileName;
            $signatureImage->move(public_path('uploads/signatures'), $signatureFileName);

            $highestBid = $machinery->bids()->max('amount');

            $contractStatusMap = [
                0 => 'Pending',
                1 => 'Approved',
                3 => 'Signed',
                4 => 'Rejected',
            ];

            $winningUser = User::find($machinery->won_user);

            $highestBidModel = $machinery->bids()->orderBy('amount', 'desc')->first();

            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $highestBidModel,
                'user' => $winningUser,
                'signaturePath' => $signaturePath,
                'absoluteSignaturePath' => public_path($signaturePath),
                'companyInfo' => [
                    'name' => Settings::get('company_name',),
                    'address' => Settings::get('address'),
                    'phone' => Settings::get('phone_no'),
                    'email' => Settings::get('email'),
                ],
                'contractDate' => now()->format('Y-m-d'),
            ];

            $pdf = Pdf::loadView('pdf.contract', $contractDataView);
            $pdfContent = $pdf->output();

            $pdfFileName = 'contract_' . $machineryId . '_' . time() . '.pdf';
            $pdfPath = 'machinery_files/' . $pdfFileName;

            $publicDirectory = public_path('uploads/machinery_files');
            if (!File::exists($publicDirectory)) {
                File::makeDirectory($publicDirectory, 0755, true);
            }

            $fullPath = public_path('uploads/machinery_files/' . $pdfFileName);
            file_put_contents($fullPath, $pdfContent);

            $pdfPath = 'uploads/machinery_files/' . $pdfFileName;

            $fileManager = MachineryFileManager::create([
                'machinery_id' => $machineryId,
                'image_path' => $pdfPath,
                'type' => 'contract_pdf',
            ]);

            $machinery->update([
                'contract_status' => 3,
                'contract_path' => $pdfPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract signed and PDF generated successfully',
                'data' => [
                    'contract_file_id' => $fileManager->id,
                    'contract_file_path' => asset($pdfPath),
                    'signature_path' => asset($signaturePath),
                    'machinery_id' => $machineryId,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getUserOrders(Request $request)
    {
        try {
            $user = auth('api')->user();

            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $sortBy = $request->input('sort_by', 'purchase_date');
            $sortOrder = $request->input('sort_order', 'desc');

            $allowedSortFields = ['order_id', 'price', 'purchase_date', 'delivery_status'];
            $allowedSortOrders = ['asc', 'desc'];

            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'purchase_date';
            }

            if (!in_array($sortOrder, $allowedSortOrders)) {
                $sortOrder = 'desc';
            }

            $query = Order::where('user_id', $user->id)
                ->with(['machinery' => function($query) {
                    $query->select('id', 'make', 'model', 'year', 'description');
                }, 'machinery.images']);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('order_id', 'LIKE', "%{$search}%")
                      ->orWhere('price', 'LIKE', "%{$search}%")
                      ->orWhere('delivery_status', 'LIKE', "%{$search}%")
                      ->orWhereHas('machinery', function($q2) use ($search) {
                          $q2->where('make', 'LIKE', "%{$search}%")
                             ->orWhere('model', 'LIKE', "%{$search}%")
                             ->orWhere('year', 'LIKE', "%{$search}%");
                      });
                });
            }

            $orders = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $ordersWithFormattedData = $orders->getCollection()->map(function ($order) {
                if ($order->machinery) {
                    $firstImage = $order->machinery->images->firstWhere('type', 'image');

                    $deliveryStatusMap = [
                        0 => 'Process',
                        1 => 'Shipped',
                        2 => 'In Transit',
                        3 => 'Delivered',
                        4 => 'Cancelled',
                    ];

                    return [
                        'id' => $order->id,
                        'order_id' => $order->order_id,
                        'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
                        'name' => $order->machinery->year . ' ' . $order->machinery->make . ' ' . $order->machinery->model,
                        'price' => $order->price,
                        'purchase_date' => $order->purchase_date,
                        'delivery_status' => $order->delivery_status,
                        'delivery_status_text' => isset($deliveryStatusMap[$order->delivery_status]) ? $deliveryStatusMap[$order->delivery_status] : 'Unknown',
                    ];
                }
            })->filter(); // Remove null values if machinery doesn't exist

            if ($ordersWithFormattedData->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No orders found',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $ordersWithFormattedData,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                    'from'         => $orders->firstItem(),
                    'to'           => $orders->lastItem(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function getOrderDetails(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'order_id' => 'required|string|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $user = auth('api')->user();

            $order = Order::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->with(['machinery' => function($query) {
                    $query->select('id', 'make', 'model', 'year', 'working_hours', 'weight', 'serial_number');
                }, 'machinery.images'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $firstImage = $order->machinery->images->firstWhere('type', 'image');

            $settings = Settings::first();
            $deliveryContact = $settings ? $settings->phone_no : null;

            $deliveryStatusMap = [
                0 => 'Process',
                1 => 'Shipped',
                2 => 'In Transit',
                3 => 'Delivered',
                4 => 'Cancelled',
            ];

            $deliveryTimeline = [];

            if ($order->process_date) {
                $deliveryTimeline[] = [
                    'status' => 'Process',
                    'date' => $order->process_date,
                    'status_code' => 0
                ];
            }

            if ($order->shipped_date) {
                $deliveryTimeline[] = [
                    'status' => 'Shipped',
                    'date' => $order->shipped_date,
                    'status_code' => 1
                ];
            }

            if ($order->in_transit_date) {
                $deliveryTimeline[] = [
                    'status' => 'In Transit',
                    'date' => $order->in_transit_date,
                    'status_code' => 2
                ];
            }

            if ($order->delivered_date) {
                $deliveryTimeline[] = [
                    'status' => 'Delivered',
                    'date' => $order->delivered_date,
                    'status_code' => 3
                ];
            }

            if ($order->cancelled_date) {
                $deliveryTimeline[] = [
                    'status' => 'Cancelled',
                    'date' => $order->cancelled_date,
                    'status_code' => 4
                ];
            }

            $orderDetails = [
                'first_image' => $firstImage ? asset('uploads/machinery/images/' . ltrim($firstImage->image_path, '/')) : null,
                'name' => $order->machinery->year . ' ' . $order->machinery->make . ' ' . $order->machinery->model,
                'working_hours' => $order->machinery->working_hours,
                'weight' => $order->machinery->weight,
                'year' => $order->machinery->year,
                'price' => $order->price,
                'serial_no' => $order->machinery->serial_number,
                'order_id' => $order->order_id,
                'delivery_contact' => $deliveryContact,
                'current_status' => isset($deliveryStatusMap[$order->delivery_status]) ? $deliveryStatusMap[$order->delivery_status] : 'Unknown',
                'delivery_timeline' => $deliveryTimeline,
            ];

            return response()->json([
                'success' => true,
                'data' => $orderDetails,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.',
            ], 500);
        }
    }

    public function purchaseMachinery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machineryId' => 'required|exists:machinery,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 401);
            }

            $machinery = Machinery::find($request->machineryId);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            // if ($machinery->bid_status !== '0' || $machinery->buy_now_price <= 0) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'This machinery is not available for direct purchase',
            //     ], 400);
            // }

            if ($machinery->is_purchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'This machinery has already been purchased',
                ], 400);
            }

            $order = Order::create([
                'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                'machinery_id' => $machinery->id,
                'user_id' => $user->id,
                'price' => $machinery->buy_now_price,
                'purchase_date' => now(),
                'delivery_status' => 0,
                'process_date' => now(),
            ]);

            $machinery->update([
                'is_purchase' => true,
                'bid_status' => '2',
                'won_user' => $user->id,
                'bid_won_date' => now(),
            ]);

            // Send buy now order email
            try {
                $mail = new BuyNowOrderMail($user, $order, $machinery);
                $smtp2goService = new SMTP2GOService();
                $htmlContent = $mail->renderHtmlContent();
                $smtp2goService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
            } catch (\Exception $e) {
                \Log::error('Failed to send buy now order email: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Machinery purchased successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'machinery_id' => $machinery->id,
                    'price' => $machinery->buy_now_price,
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
