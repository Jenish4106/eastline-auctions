<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BiddingMail;
use App\Mail\BuyNowOrderMail;
use App\Mail\OutbidMail;
use App\Mail\SendContractMail;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use App\Models\Order;
use App\Models\Settings;
use App\Models\User;
use App\Services\GoogleMapsService;
use App\Services\MailtrapService;
use App\Services\S3StorageService;
use App\Services\TwilioSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class BiddingController extends Controller
{
    protected $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    public function placeBid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
            'auction_id' => 'required',
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

            // if ($user->is_license == 0) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Your license is not verified.',
            //     ], 403);
            // }

            $machinery = Machinery::find($request->machinery_id);

            if ($machinery->bid_status == '2' || $machinery->bid_status == '3') {
                return response()->json([
                    'success' => false,
                    'message' => $machinery->bid_status == '3'
                        ? 'This auction has been cancelled because the machinery was purchased.'
                        : 'This auction has already been completed.',
                ], 400);
            }

            if ($machinery->bid_end_time && Carbon::now()->greaterThan($machinery->bid_end_time)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The bidding for this machinery has ended.',
                ], 400);
            }

            $previousHighestBid = Bid::where('machinery_id', $request->machinery_id)
                ->where('auction_id', $request->auction_id)
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

            $isWon = false;
            if ($machinery->buy_now_price > 0 && $request->amount >= $machinery->buy_now_price) {
                $isWon = true;
            }

            $bid = Bid::create([
                'user_id' => $user->id,
                'machinery_id' => $request->machinery_id,
                'auction_id' => $request->auction_id,
                'amount' => $request->amount,
            ]);

            $machinery->increment('offer');

            if ($isWon) {
                $machinery->update([
                    'bid_status' => '2',
                    'won_user' => $user->id,
                    'bid_won_date' => Carbon::now(),
                    'contract_status' => '0',
                    'status' => 2,
                ]);
            } elseif ((string) $machinery->bid_status === '0') {
                $machinery->update([
                    'bid_status' => '1',
                ]);
            }

            if ($isWon) {
                try {
                    $mail = new SendContractMail($user, $machinery, null);
                    $mailtrapService = new MailtrapService();
                    $htmlContent = $mail->renderHtmlContent();
                    $mailtrapService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Congratulations! You have won this auction, but failed to send contract email.',
                    ], 200);
                }
            } else {
                try {
                    $mail = new BiddingMail($user, $machinery, $request->amount);
                    $mailtrapService = new MailtrapService();
                    $htmlContent = $mail->renderHtmlContent();
                    $mailtrapService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Bid placed successfully but failed to send bid confirmation email.',
                    ], 200);
                }
            }

            if ($previousHighestBid && $previousHighestBid->user_id != $user->id) {
                try {
                    $previousBidder = User::find($previousHighestBid->user_id);
                    if ($previousBidder) {
                        $outbidMail = new OutbidMail($previousBidder, $machinery, $request->amount);
                        $mailtrapService = new MailtrapService();
                        $mailContent = $outbidMail->renderHtmlContent();
                        $mailtrapService->sendEmail($previousBidder->email, $outbidMail->getSubject(), $mailContent);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Bid placed successfully but failed to send outbid notification to previous highest bidder.',
                    ], 200);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $isWon ? 'Congratulations! You have won this auction.' : 'Bid placed successfully.',
                'bid' => $bid,
                'won' => $isWon
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

            $query = Machinery::whereHas('bids', function ($q) use ($user) {
                $q
                    ->where('user_id', $user->id)
                    ->whereColumn('bids.auction_id', 'machinery.auction_id');
            })
                ->with(['images', 'bids.user']);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('auction_id', 'LIKE', "%{$search}%")
                        ->orWhere('year', 'LIKE', "%{$search}%")
                        ->orWhere('make', 'LIKE', "%{$search}%")
                        ->orWhere('model', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT_WS(' ', year, make, model) LIKE ?", ["%{$search}%"])
                        ->orWhere('bid_start_price', 'LIKE', "%{$search}%")
                        ->orWhere('bid_end_time', 'LIKE', "%{$search}%");

                    if (stripos('completed', $search) !== false || stripos('sold', $search) !== false) {
                        $q
                            ->orWhere('bid_status', 'sold')
                            ->orWhere('bid_status', '3')
                            ->orWhereNotNull('won_user');
                    }
                });
            }

            $machineries = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $machineriesWithFormattedData = $machineries->getCollection()->map(function ($machinery) use ($user) {
                $bids = $machinery->bids->where('auction_id', $machinery->auction_id);
                $highestBid = $bids->max('amount');
                $lastBid = $highestBid ?: $machinery->bid_start_price;

                $currentUserBids = $bids->where('user_id', $user->id);
                $currentUserHighestBid = $currentUserBids->max('amount');

                $status = 'Active';

                if ((string) $machinery->bid_status === '3') {
                    $status = 'cancelled';
                } elseif ($machinery->bid_status === 'sold' || $machinery->won_user) {
                    if ($machinery->won_user == $user->id) {
                        $status = 'won';
                    } else {
                        $status = 'completed';
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

                $firstImageObj = $machinery->images ? $machinery->images->firstWhere('type', 'image') : null;
                $firstImageUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();
                if ($firstImageObj) {
                    $path1 = public_path('uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/'));
                    if (file_exists($path1)) {
                        $firstImageUrl = asset('public/uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/')) . '?time=' . time();
                    }
                }

                return [
                    'id' => $machinery->id,
                    'auction_id' => $machinery->auction_id,
                    'name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                    'first_image' => $firstImageUrl,
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

            $machinery = Machinery::with(['images', 'bids.user', 'category'])->find($machineryId);

            if (!$machinery) {
                return response()->json([
                    'success' => false,
                    'message' => 'Machinery not found',
                ], 404);
            }

            $bids = $machinery->bids->where('auction_id', $machinery->auction_id);

            $highestBid = $bids->max('amount');
            $lastBid = $highestBid ?: $machinery->bid_start_price;

            $currentUserBids = $bids->where('user_id', $user->id);
            $currentUserHighestBid = $currentUserBids->max('amount');

            $status = 'Active';

            if ((string) $machinery->bid_status === '3') {
                $status = 'cancelled';
            } elseif ($machinery->bid_status === 'sold' || $machinery->won_user) {
                if ($machinery->won_user == $user->id) {
                    $status = 'won';
                } else {
                    $status = 'completed';
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

            $firstImageObj = $machinery->images->firstWhere('type', 'image');
            $firstImageUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();
            if ($firstImageObj) {
                $path1 = public_path('uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/'));
                if (file_exists($path1)) {
                    $firstImageUrl = asset('public/uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/')) . '?time=' . time();
                }
            }

            $machineryDetails = [
                'auction_id' => $machinery->auction_id,
                'machinery_name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                'bid_end_time' => $machinery->bid_end_time,
                'start_bid_price' => $machinery->bid_start_price,
                'highest_bid' => $highestBid,
                'my_bid' => $currentUserHighestBid,
                'user_full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'status' => $status,
                'first_image' => $firstImageUrl,
            ];

            $biddingDetails = $bids->map(function ($bid) use ($user) {
                return [
                    'user_full_name' => trim(($bid->user->first_name ?? '')),
                    'amount' => $bid->amount,
                    'auction_id' => $bid->auction_id,
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
                ->whereHas('bids', function ($q) use ($user) {
                    $q
                        ->where('user_id', $user->id)
                        ->whereColumn('bids.auction_id', 'machinery.auction_id');
                })
                ->with(['images', 'category', 'bids']);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('auction_id', 'LIKE', "%{$search}%")
                        ->orWhere('year', 'LIKE', "%{$search}%")
                        ->orWhere('make', 'LIKE', "%{$search}%")
                        ->orWhere('model', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT_WS(' ', year, make, model) LIKE ?", ["%{$search}%"])
                        ->orWhere('bid_won_date', 'LIKE', "%{$search}%")
                        ->orWhereHas('category', function ($q2) use ($search) {
                            $q2->where('category_name', 'LIKE', "%{$search}%");
                        });

                    $contractStatusMap = ['pending' => 0, 'approved' => 1, 'signed' => 3, 'rejected' => 4];
                    foreach ($contractStatusMap as $label => $value) {
                        if (stripos($label, $search) !== false) {
                            $q->orWhere('contract_status', $value);
                        }
                    }
                });
            }

            $wonMachinery = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $wonMachineryWithFormattedData = $wonMachinery->getCollection()->map(function ($machinery) {
                $userWonBid = $machinery
                    ->bids
                    ->where('user_id', auth('api')->id())
                    ->where('auction_id', $machinery->auction_id)
                    ->max('amount');

                $firstImageObj = $machinery->images->firstWhere('type', 'image');
                $firstImageUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();
                if ($firstImageObj) {
                    $path1 = public_path('uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/'));
                    if (file_exists($path1)) {
                        $firstImageUrl = asset('public/uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/')) . '?time=' . time();
                    }
                }

                $contractStatusMap = [
                    0 => 'Pending',
                    1 => 'Approved',
                    3 => 'Signed',
                    4 => 'Rejected',
                ];

                return [
                    'id' => $machinery->id,
                    'auction_id' => $machinery->auction_id,
                    'first_image' => $firstImageUrl,
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
                    'last_page' => $wonMachinery->lastPage(),
                    'per_page' => $wonMachinery->perPage(),
                    'total' => $wonMachinery->total(),
                    'from' => $wonMachinery->firstItem(),
                    'to' => $wonMachinery->lastItem(),
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

            $highestBid = $machinery->bids->where('auction_id', $machinery->auction_id)->max('amount');

            $contractStatusMap = [
                0 => 'Pending',
                1 => 'Approved',
                3 => 'Signed',
                4 => 'Rejected',
            ];

            $contractData = [
                'id' => $machinery->id,
                'auction_id' => $machinery->auction_id,
                'name' => $machinery->year . ' ' . $machinery->make . ' ' . $machinery->model,
                'category' => $machinery->category ? $machinery->category->category_name : 'Uncategorized',
                'start_bid_price' => $machinery->bid_start_price,
                'won_bid_amount' => $highestBid,
                'status' => isset($contractStatusMap[$machinery->contract_status]) ? $contractStatusMap[$machinery->contract_status] : 'Unknown',
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
            'sign_photo' => 'required|string',
            'billing_details.legal_company_name' => 'nullable|string|max:255',
            'billing_details.street_and_number' => 'required|string|max:255',
            'billing_details.city' => 'required|string|max:255',
            'billing_details.state_province' => 'nullable|string|max:255',
            'billing_details.zip_postal_code' => 'required|string|max:20',
            'billing_details.country' => 'required|string|max:255',
            'shipping_details.is_different' => 'required|boolean',
            'shipping_details.shipping_street' => 'required_if:shipping_details.is_different,true|nullable|string|max:255',
            'shipping_details.shipping_city' => 'required_if:shipping_details.is_different,true|nullable|string|max:255',
            'shipping_details.shipping_state' => 'nullable|string|max:255',
            'shipping_details.shipping_zip' => 'required_if:shipping_details.is_different,true|nullable|string|max:20',
            'shipping_details.shipping_country' => 'required_if:shipping_details.is_different,true|nullable|string|max:255',
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

            $billing = $request->input('billing_details');
            $shipping = $request->input('shipping_details');
            $isShippingDifferent = filter_var($shipping['is_different'], FILTER_VALIDATE_BOOLEAN);

            $shippingCost = 0;
            try {
                $shippingZip = $isShippingDifferent ? $shipping['shipping_zip'] : $billing['zip_postal_code'];
                $shippingCountry = $isShippingDifferent ? $shipping['shipping_country'] : $billing['country'];

                $companyAddress = $this->googleMapsService->getCompanyAddress();
                if ($companyAddress) {
                    $companyLocation = $this->googleMapsService->geocodeAddress($companyAddress);
                    $customerLocation = $this->googleMapsService->getCoordinatesFromZipAndCountry($shippingZip, $shippingCountry);

                    if ($companyLocation && $customerLocation) {
                        $distanceResult = $this->googleMapsService->calculateDistance($companyLocation, $customerLocation);
                        if ($distanceResult) {
                            $perMileDeliveryCost = Settings::get('per_mile_delivery_cost', 0);
                            $shippingCost = $distanceResult['distance_miles'] * $perMileDeliveryCost;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Keep shipping cost as 0 if calculation fails
            }

            $signatureData = $request->input('sign_photo');
            $signaturePath = null;

            if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $type)) {
                $signatureData = substr($signatureData, strpos($signatureData, ',') + 1);
                $type = strtolower($type[1]);

                if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                    throw new \Exception('invalid image type');
                }
                $signatureData = str_replace(' ', '+', $signatureData);
                $signatureData = base64_decode($signatureData);

                if ($signatureData === false) {
                    throw new \Exception('base64_decode failed');
                }

                $signatureFileName = time() . '_signature.' . $type;
                $signatureUpload = S3StorageService::upload($signatureData, 'signatures', $signatureFileName);
                $signaturePath = $signatureUpload['relative_path'];
            } else {
                throw new \Exception('Invalid signature format');
            }

            $highestBid = $machinery->bids()->where('auction_id', $machinery->auction_id)->max('amount');
            $highestBidModel = $machinery->bids()->where('auction_id', $machinery->auction_id)->orderBy('amount', 'desc')->first();

            $order = Order::where('machinery_id', $machineryId)->where('user_id', $user->id)->latest()->first();

            if ($order) {
                $order->update([
                    'price' => $highestBid,
                    'shipping_cost' => $shippingCost,
                    'billing_company' => $billing['legal_company_name'] ?? null,
                    'billing_street' => $billing['street_and_number'],
                    'billing_city' => $billing['city'],
                    'billing_state' => $billing['state_province'] ?? null,
                    'billing_zip' => $billing['zip_postal_code'],
                    'billing_country' => $billing['country'],
                    'shipping_same_as_billing' => !$isShippingDifferent,
                    'shipping_street' => $isShippingDifferent ? ($shipping['shipping_street'] ?? null) : null,
                    'shipping_city' => $isShippingDifferent ? ($shipping['shipping_city'] ?? null) : null,
                    'shipping_state' => $isShippingDifferent ? ($shipping['shipping_state'] ?? null) : null,
                    'shipping_zip' => $isShippingDifferent ? ($shipping['shipping_zip'] ?? null) : null,
                    'shipping_country' => $isShippingDifferent ? ($shipping['shipping_country'] ?? null) : null,
                ]);
            } else {
                do {
                    $orderId = 'ORD-' . strtoupper(Str::random(10));
                } while (Order::where('order_id', $orderId)->exists());

                $order = Order::create([
                    'order_id' => $orderId,
                    'machinery_id' => $machineryId,
                    'user_id' => $user->id,
                    'type' => 2,
                    'price' => $highestBid,
                    'shipping_cost' => $shippingCost,
                    'purchase_date' => now(),
                    'awaiting_invoice_date' => now(),
                    'delivery_status' => 2,
                    'billing_company' => $billing['legal_company_name'] ?? null,
                    'billing_street' => $billing['street_and_number'],
                    'billing_city' => $billing['city'],
                    'billing_state' => $billing['state_province'] ?? null,
                    'billing_zip' => $billing['zip_postal_code'],
                    'billing_country' => $billing['country'],
                    'shipping_same_as_billing' => !$isShippingDifferent,
                    'shipping_street' => $isShippingDifferent ? ($shipping['shipping_street'] ?? null) : null,
                    'shipping_city' => $isShippingDifferent ? ($shipping['shipping_city'] ?? null) : null,
                    'shipping_state' => $isShippingDifferent ? ($shipping['shipping_state'] ?? null) : null,
                    'shipping_zip' => $isShippingDifferent ? ($shipping['shipping_zip'] ?? null) : null,
                    'shipping_country' => $isShippingDifferent ? ($shipping['shipping_country'] ?? null) : null,
                ]);
            }

            $winningUser = User::find($machinery->won_user);

            $companyName = Settings::get('company_name', 'Mcfarland Equipment');
            $companyAddress = Settings::get('address') ?? '';
            $companyPhone = Settings::get('phone_no') ?? '';
            $companyEmail = Settings::get('email') ?? '';
            $companyLogo = Settings::get('dark_logo');

            $sellerAddress = $companyAddress;

            $buyerAddress = trim(($billing['street_and_number'] ?? '') . ', '
                . ($billing['city'] ?? '') . ', '
                . ($billing['state_province'] ?? '') . ' '
                . ($billing['zip_postal_code'] ?? '') . ', '
                . ($billing['country'] ?? ''));
            $buyerAddress = trim(str_replace(',  ', ', ', $buyerAddress), ', ');

            $shippingAddress = $buyerAddress;
            if ($isShippingDifferent) {
                $shippingAddress = trim(($shipping['shipping_street'] ?? '') . ', '
                    . ($shipping['shipping_city'] ?? '') . ', '
                    . ($shipping['shipping_state'] ?? '') . ' '
                    . ($shipping['shipping_zip'] ?? '') . ', '
                    . ($shipping['shipping_country'] ?? ''));
                $shippingAddress = trim(str_replace(',  ', ', ', $shippingAddress), ', ');
            }

            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $highestBidModel,
                'user' => $winningUser,
                'order' => $order,
                'sellerAddress' => $sellerAddress,
                'buyerAddress' => $buyerAddress,
                'shippingAddress' => $shippingAddress,
                'signaturePath' => $signaturePath,
                'shipping_cost' => $shippingCost,
                'absoluteSignaturePath' => File::exists(public_path($signaturePath)) ? $this->imageToBase64(public_path($signaturePath)) : null,
                'companyInfo' => [
                    'name' => $companyName,
                    'address' => $companyAddress,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'logo' => $companyLogo && File::exists(public_path($companyLogo)) ? $this->imageToBase64(public_path($companyLogo)) : null,
                    'logoUrl' => $companyLogo ? asset('public/' . ltrim($companyLogo, '/')) : null,
                    'signature_path' => File::exists(public_path('uploads/signatures/seller_signature.png')) ? $this->imageToBase64(public_path('uploads/signatures/seller_signature.png')) : null,  // For PDF
                ],
                'contractDate' => now()->format('Y-m-d'),
            ];

            $pdf = Pdf::loadView('pdf.contract', $contractDataView);
            $pdfContent = $pdf->output();

            $pdfFileName = 'contract_' . $machineryId . '_' . time() . '.pdf';
            $contractUpload = S3StorageService::upload($pdfContent, 'machinery_files', $pdfFileName);
            $pdfPath = $contractUpload['relative_path'];

            $fileManager = MachineryFileManager::create([
                'machinery_id' => $machineryId,
                'order_id' => $order ? $order->id : null,
                'image_path' => $pdfPath,
                'type' => 'contract_pdf',
            ]);

            $companyLogo = Settings::get('dark_logo');
            $companyLogoPath = null;
            if ($companyLogo && File::exists(public_path($companyLogo))) {
                $companyLogoPath = public_path($companyLogo);
            }

            $machinery->load('images');
            $firstImage = $machinery->images->firstWhere('type', 'image');
            $machineryImage = null;
            $machineryImageUrl = null;

            if ($firstImage) {
                $imagePathRel = 'uploads/machinery/images/' . ltrim($firstImage->image_path, '/');
                if (File::exists(public_path($imagePathRel))) {
                    $machineryImage = $this->imageToBase64(public_path($imagePathRel));
                    $machineryImageUrl = asset('public/' . ltrim($imagePathRel, '/'));
                }
            }

            $machinery->update([
                'contract_status' => 3,
            ]);

            if ($order) {
                $order->update([
                    'delivery_status' => 2,
                    'awaiting_invoice_date' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contract signed and PDF generated successfully',
                'data' => [
                    'contract_file_id' => $fileManager->id,
                    'contract_file_path' => asset('public/' . ltrim($pdfPath, '/')),
                    'signature_path' => asset('public/' . ltrim($signaturePath, '/')),
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
            $settings = Settings::first();
            $deliveryContact = $settings ? $settings->phone_no : null;

            $transformOrder = function ($order) use ($deliveryContact) {
                if (!$order->machinery) {
                    return null;
                }

                $firstImageObj = $order->machinery->images->firstWhere('type', 'image');
                $firstImageUrl = asset('public/uploads/defaults/default.png') . '?time=' . time();
                if ($firstImageObj) {
                    $path1 = public_path('uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/'));
                    if (file_exists($path1)) {
                        $firstImageUrl = asset('public/uploads/machinery/images/' . ltrim($firstImageObj->image_path, '/')) . '?time=' . time();
                    }
                }

                $deliveryStatusMap = [
                    0 => 'Order Submitted',
                    1 => 'Sales Agreement',
                    2 => 'Awaiting Invoice',
                    3 => 'Settle Payment',
                    4 => 'Payment Confirmed',
                    5 => 'Processing',
                    6 => 'Shipping Started',
                    7 => 'In Transit',
                    8 => 'Delivered',
                    9 => 'Cancelled',
                ];

                $deliveryTimeline = [];
                if ($order->purchase_date) {
                    $deliveryTimeline[] = ['status' => 'Order Submitted', 'date' => $order->purchase_date, 'status_code' => 0];
                }
                if ($order->sales_agreement_date) {
                    $deliveryTimeline[] = ['status' => 'Sales Agreement', 'date' => $order->sales_agreement_date, 'status_code' => 1];
                }
                if ($order->awaiting_invoice_date) {
                    $deliveryTimeline[] = ['status' => 'Awaiting Invoice', 'date' => $order->awaiting_invoice_date, 'status_code' => 2];
                }
                if ($order->settle_payment_date) {
                    $deliveryTimeline[] = ['status' => 'Settle Payment', 'date' => $order->settle_payment_date, 'status_code' => 3];
                }
                if ($order->confirmation_date) {
                    $deliveryTimeline[] = ['status' => 'Payment Confirmed', 'date' => $order->confirmation_date, 'status_code' => 4];
                }
                if ($order->process_date) {
                    $deliveryTimeline[] = ['status' => 'Processing', 'date' => $order->process_date, 'status_code' => 5];
                }
                if ($order->shipped_date) {
                    $deliveryTimeline[] = ['status' => 'Shipping Started', 'date' => $order->shipped_date, 'status_code' => 6];
                }
                if ($order->in_transit_date) {
                    $deliveryTimeline[] = ['status' => 'In Transit', 'date' => $order->in_transit_date, 'status_code' => 7];
                }
                if ($order->delivered_date) {
                    $deliveryTimeline[] = ['status' => 'Delivered', 'date' => $order->delivered_date, 'status_code' => 8];
                }
                if ($order->cancelled_date) {
                    $deliveryTimeline[] = ['status' => 'Cancelled', 'date' => $order->cancelled_date, 'status_code' => 9];
                }

                $trackingData = [];
                if ($order->trackingEntries) {
                    foreach ($order->trackingEntries as $entry) {
                        $trackingData[] = [
                            'id' => $entry->id,
                            'tracking_date' => $entry->tracking_date ? $entry->tracking_date->format('Y-m-d H:i:s') : null,
                            'city' => $entry->city,
                            'lat' => $entry->lat,
                            'lng' => $entry->lng,
                        ];
                    }
                }

                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'first_image' => $firstImageUrl,
                    'name' => $order->machinery->year . ' ' . $order->machinery->make . ' ' . $order->machinery->model,
                    'auction_id' => $order->machinery->auction_id,
                    'price' => $order->price,
                    'type' => $order->type,
                    'type_text' => $order->type == 1 ? 'Checkout' : 'Bidding',
                    'purchase_date' => $order->purchase_date,
                    'delivery_status' => $order->delivery_status,
                    'delivery_status_text' => isset($deliveryStatusMap[$order->delivery_status]) ? $deliveryStatusMap[$order->delivery_status] : 'Unknown',
                    'current_status' => isset($deliveryStatusMap[$order->delivery_status]) ? $deliveryStatusMap[$order->delivery_status] : 'Unknown',
                    'invoice_url' => $order->invoice_url,
                    'contract_url' => $order->contract_url,
                    'payment_slip_url' => $order->payment_slip_url,
                    'payment_slip_status' => $order->payment_slip_status,
                    'payment_slip_status_text' => $order->payment_slip_status_text,
                    'working_hours' => $order->machinery->working_hours,
                    'weight' => $order->machinery->weight,
                    'year' => $order->machinery->year,
                    'serial_no' => $order->machinery->serial_number,
                    'delivery_contact' => $deliveryContact,
                    'delivery_timeline' => $deliveryTimeline,
                    'order_tracking' => $trackingData,
                ];
            };

            $query = Order::where('user_id', $user->id)
                ->with(['machinery' => function ($query) {
                    $query->select('id', 'make', 'model', 'year', 'working_hours', 'weight', 'serial_number', 'description', 'auction_id');
                }, 'machinery.images', 'trackingEntries']);

            if ($request->has('order_id')) {
                $validator = Validator::make($request->all(), [
                    'order_id' => 'required|string|exists:orders,id',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validator->errors(),
                    ], 400);
                }

                $order = $query->where('id', $request->order_id)->first();

                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found',
                    ], 404);
                }

                $orderDetails = $transformOrder($order);

                return response()->json([
                    'success' => true,
                    'data' => $orderDetails,
                ], 200);
            }

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

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('order_id', 'LIKE', "%{$search}%")
                        ->orWhere('price', 'LIKE', "%{$search}%")
                        ->orWhere('delivery_status', 'LIKE', "%{$search}%")
                        ->orWhereHas('machinery', function ($q2) use ($search) {
                            $q2
                                ->where('make', 'LIKE', "%{$search}%")
                                ->orWhere('model', 'LIKE', "%{$search}%")
                                ->orWhere('year', 'LIKE', "%{$search}%");
                        });
                });
            }

            $orders = $query->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

            $ordersWithFormattedData = $orders->getCollection()->map($transformOrder)->filter();

            if ($ordersWithFormattedData->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No orders found',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $ordersWithFormattedData->values(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
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

            if ((int) $machinery->status === 2 || in_array((string) $machinery->bid_status, ['2', '3', 'sold'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This machinery has already been purchased',
                ], 400);
            }

            do {
                $orderId = 'ORD-' . strtoupper(Str::random(10));
            } while (Order::where('order_id', $orderId)->exists());

            $order = Order::create([
                'order_id' => $orderId,
                'machinery_id' => $machinery->id,
                'user_id' => $user->id,
                'type' => 1,
                'price' => $machinery->buy_now_price,
                'purchase_date' => now(),
                'delivery_status' => 0,
            ]);

            $machinery->update([
                'bid_status' => '2',
                'won_user' => $user->id,
                'bid_won_date' => now(),
                'status' => 2,
            ]);

            try {
                $mail = new BuyNowOrderMail($user, $order, $machinery);
                $mailtrapService = new MailtrapService();
                $htmlContent = $mail->renderHtmlContent();
                $mailtrapService->sendEmail($user->email, $mail->getSubject(), $htmlContent);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order placed successfully, but failed to send confirmation email.',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Machinery purchased successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'machinery_id' => $machinery->id,
                    'auction_id' => $machinery->auction_id,
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

    public function uploadPaymentSlip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'payment_slip' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ], 400);
        }

        try {
            $user = auth('api')->user();
            $order = Order::where('id', $request->order_id)->where('user_id', $user->id)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or you are not authorized.',
                ], 404);
            }

            $paymentSlipData = $request->input('payment_slip');
            $extension = 'jpg';

            if (preg_match('/^data:image\/(\w+);base64,/', $paymentSlipData, $type)) {
                $paymentSlipData = substr($paymentSlipData, strpos($paymentSlipData, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid image type. Only jpg, jpeg, png, gif are allowed.',
                    ], 400);
                }
                $extension = $type;
            } elseif (preg_match('/^data:application\/pdf;base64,/', $paymentSlipData, $type)) {
                $paymentSlipData = substr($paymentSlipData, strpos($paymentSlipData, ',') + 1);
                $extension = 'pdf';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment slip format.',
                ], 400);
            }

            if (strpos($paymentSlipData, 'data:image') === 0 || strpos($paymentSlipData, 'data:application') === 0) {
                $paymentSlipData = explode(',', $paymentSlipData)[1];
            }

            $decodedData = base64_decode($paymentSlipData);

            if ($decodedData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Base64 decode failed.',
                ], 400);
            }

            $fileName = 'payment_slip_' . $order->order_id . '_' . time() . '.' . $extension;
            $uploadResult = S3StorageService::upload($decodedData, 'payment_slips', $fileName);

            $order->payment_slip_path = $uploadResult['relative_path'];
            $order->payment_slip_status = 0;
            $order->delivery_status = 3;
            $order->settle_payment_date = now();
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment slip uploaded successfully.',
                'data' => [
                    'payment_slip_url' => $uploadResult['url'],
                    'status' => 'Pending',
                    'status_code' => 0
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.'
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
