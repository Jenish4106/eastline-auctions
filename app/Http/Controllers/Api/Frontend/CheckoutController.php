<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\AuctionCancelledMail;
use App\Mail\BuyNowOrderMail;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\MachineryFileManager;
use App\Models\Order;
use App\Models\Settings;
use App\Models\User;
use App\Services\GoogleMapsService;
use App\Services\PostmarkService;
use App\Services\TwilioSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    public function checkout(Request $request)
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
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $machinery = Machinery::find($request->machinery_id);
            if (!$machinery) {
                return response()->json(['success' => false, 'message' => 'Machinery not found'], 404);
            }

            if ((int) $machinery->status === 2 || in_array((string) $machinery->bid_status, ['2', '3', 'sold'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This machinery has already been purchased.'
                ], 400);
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
            }

            do {
                $orderId = 'ORD-' . strtoupper(Str::random(10));
            } while (Order::where('order_id', $orderId)->exists());

            $order = Order::create([
                'order_id' => $orderId,
                'machinery_id' => $machinery->id,
                'user_id' => $user->id,
                'type' => 1,
                'price' => $machinery->buy_now_price > 0 ? $machinery->buy_now_price : ($machinery->bid_start_price ?? 0),
                'shipping_cost' => $shippingCost,
                'purchase_date' => now(),
                'sales_agreement_date' => now(),
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

            $activeAuctionBids = Bid::where('machinery_id', $machinery->id)
                ->where('auction_id', $machinery->auction_id)
                ->get();

            $hasActiveAuctionBids = $activeAuctionBids->isNotEmpty();

            $machinery->update([
                'bid_status' => $hasActiveAuctionBids ? '3' : '2',
                'won_user' => $user->id,
                'bid_won_date' => now(),
                'contract_status' => '3',
                'status' => 2,
            ]);

            if ($hasActiveAuctionBids) {
                $this->sendAuctionCancelledEmails($machinery, $user, $activeAuctionBids);
            }

            $companyName = Settings::get('company_name', 'Mcfarland Equipment');
            $companyAddress = Settings::get('address') ?? '';
            $companyPhone = Settings::get('phone_no') ?? '';
            $companyEmail = Settings::get('email') ?? '';
            $companyBankName = Settings::get('bank_name');
            $companyBeneficiaryName = Settings::get('beneficiary_name');
            $companyBeneficiaryAddress = Settings::get('beneficiary_address');
            $companyAccountNumber = Settings::get('account_number');
            $companyRoutingNumber = Settings::get('routing_number');
            $companyBranchAddress = Settings::get('branch_address');

            $companyLogo = Settings::get('dark_logo');

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

            $data = [
                'order' => $order,
                'machineryImage' => $machineryImage,
                'machineryImageUrl' => $machineryImageUrl ?? null,
                'companyInfo' => [
                    'name' => $companyName,
                    'address' => $companyAddress,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'bank_name' => $companyBankName,
                    'beneficiary_name' => $companyBeneficiaryName,
                    'beneficiary_address' => $companyBeneficiaryAddress,
                    'account_number' => $companyAccountNumber,
                    'routing_number' => $companyRoutingNumber,
                    'branch_address' => $companyBranchAddress,
                    'logo' => $companyLogo && File::exists(public_path($companyLogo)) ? $this->imageToBase64(public_path($companyLogo)) : null,  // For PDF
                    'logoUrl' => $companyLogo ? asset('public/' . ltrim($companyLogo, '/')) : null,  // For Frontend
                ]
            ];

            $pdf = Pdf::loadView('pdf.invoice', $data);
            $fileName = 'invoice_' . $order->order_id . '.pdf';
            $path = 'uploads/invoices/' . $fileName;

            $publicDir = public_path('uploads/invoices');
            if (!File::exists($publicDir)) {
                File::makeDirectory($publicDir, 0755, true);
            }

            $pdf->save(public_path($path));

            MachineryFileManager::create([
                'machinery_id' => $machinery->id,
                'order_id' => $order->id,
                'image_path' => $path,
                'type' => 'invoice',
            ]);

            $signatureData = $request->input('sign_photo');
            $signatureDirectory = public_path('uploads/signatures');
            if (!File::exists($signatureDirectory)) {
                File::makeDirectory($signatureDirectory, 0755, true);
            }

            $signatureFileName = time() . '_signature.png';
            $signaturePath = 'uploads/signatures/' . $signatureFileName;

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
                $signaturePath = 'uploads/signatures/' . $signatureFileName;
                File::put(public_path($signaturePath), $signatureData);
            } else {
                throw new \Exception('Invalid signature format');
            }

            $winningUser = $user;

            $pseudoBid = (object) ['amount' => $order->price];

            $sellerAddress = $companyAddress;

            $buyerAddress = trim(($order->billing_street ?? '') . ', '
                . ($order->billing_city ?? '') . ', '
                . ($order->billing_state ?? '') . ' '
                . ($order->billing_zip ?? '') . ', '
                . ($order->billing_country ?? ''));
            $buyerAddress = trim(str_replace(',  ', ', ', $buyerAddress), ', ');

            $shippingAddress = $buyerAddress;
            if (!$order->shipping_same_as_billing) {
                $shippingAddress = trim(($order->shipping_street ?? '') . ', '
                    . ($order->shipping_city ?? '') . ', '
                    . ($order->shipping_state ?? '') . ' '
                    . ($order->shipping_zip ?? '') . ', '
                    . ($order->shipping_country ?? ''));
                $shippingAddress = trim(str_replace(',  ', ', ', $shippingAddress), ', ');
            }

            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $pseudoBid,
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
                    'logo' => $companyLogo && File::exists(public_path($companyLogo)) ? $this->imageToBase64(public_path($companyLogo)) : null,  // For PDF
                    'logoUrl' => $companyLogo ? asset('public/' . ltrim($companyLogo, '/')) : null,
                    'signature_path' => File::exists(public_path('uploads/signatures/seller_signature.png')) ? $this->imageToBase64(public_path('uploads/signatures/seller_signature.png')) : null,  // For PDF
                ],
                'contractDate' => now()->format('Y-m-d'),
                'is_checkout' => true,
                'buy_now_price' => $machinery->buy_now_price,
            ];

            $contractPdf = Pdf::loadView('pdf.contract', $contractDataView);
            $contractPdfContent = $contractPdf->output();

            $contractFileName = 'contract_' . $order->order_id . '_' . time() . '.pdf';
            $contractPublicDir = public_path('uploads/machinery_files');
            if (!File::exists($contractPublicDir)) {
                File::makeDirectory($contractPublicDir, 0755, true);
            }

            $contractFullPath = $contractPublicDir . '/' . $contractFileName;
            file_put_contents($contractFullPath, $contractPdfContent);
            $finalContractPath = 'uploads/machinery_files/' . $contractFileName;

            MachineryFileManager::create([
                'machinery_id' => $machinery->id,
                'order_id' => $order->id,
                'image_path' => $finalContractPath,
                'type' => 'contract_pdf',
            ]);

            $machinery->update([
                'contract_status' => 3,
            ]);

            try {
                $mail = new BuyNowOrderMail($winningUser, $order, $machinery);
                $postmarkService = new PostmarkService();
                $htmlContent = $mail->renderHtmlContent();

                $attachments = [];
                if (file_exists(public_path($finalContractPath))) {
                    $attachments[] = [
                        'path' => public_path($finalContractPath),
                        'name' => 'Contract-' . $order->order_id . '.pdf',
                        'type' => 'application/pdf',
                    ];
                }

                $emailSent = $postmarkService->sendEmail($winningUser->email, $mail->getSubject(), $htmlContent, $attachments);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email',
                ], 500);
            }

            (new TwilioSmsService())->sendMessage(
                $winningUser->phone_no,
                'Thank you for your purchase with McFarland Equipment Sales & Auctions! Your Buy It Now item is secured. You will get the invoice shortly to complete payment.'
            );

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful',
                'data' => [
                    'order_id' => $order->order_id,
                    'invoice_url' => asset('public/' . ltrim($path, '/')),
                    'contract_url' => asset('public/' . ltrim($finalContractPath, '/')),
                    'user_email' => $user->email,
                    'shipping_address' => [
                        'street' => $order->shipping_same_as_billing ? $order->billing_street : $order->shipping_street,
                        'city' => $order->shipping_same_as_billing ? $order->billing_city : $order->shipping_city,
                        'state' => $order->shipping_same_as_billing ? $order->billing_state : $order->shipping_state,
                        'zip' => $order->shipping_same_as_billing ? $order->billing_zip : $order->shipping_zip,
                        'country' => $order->shipping_same_as_billing ? $order->billing_country : $order->shipping_country,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }

    public function getContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'machinery_id' => 'required|exists:machinery,id',
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
            'is_bid' => 'required|boolean',
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
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $machinery = Machinery::find($request->machinery_id);
            if (!$machinery) {
                return response()->json(['success' => false, 'message' => 'Machinery not found'], 404);
            }

            $order = null;

            $companyName = Settings::get('company_name', 'Mcfarland Equipment');
            $companyAddress = Settings::get('address') ?? '';
            $companyPhone = Settings::get('phone_no') ?? '';
            $companyEmail = Settings::get('email') ?? '';
            $companyLogo = Settings::get('dark_logo') ?? '';

            $price = $machinery->buy_now_price > 0 ? $machinery->buy_now_price : ($machinery->bid_start_price ?? 0);

            $highestBidModel = $machinery->bids()->where('auction_id', $machinery->auction_id)->orderBy('amount', 'desc')->first();
            if ($highestBidModel) {
                $price = $highestBidModel->amount;
            } else {
                $highestBidModel = (object) ['amount' => $price];
            }

            $sellerAddress = $companyAddress;

            $billing = $request->input('billing_details');
            $shipping = $request->input('shipping_details');
            $isShippingDifferent = filter_var($shipping['is_different'] ?? false, FILTER_VALIDATE_BOOLEAN);

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

            $shippingCost = 0;
            try {
                $shippingZip = $isShippingDifferent ? ($shipping['shipping_zip'] ?? null) : ($billing['zip_postal_code'] ?? null);
                $shippingCountry = $isShippingDifferent ? ($shipping['shipping_country'] ?? null) : ($billing['country'] ?? null);

                $companyAddress = $this->googleMapsService->getCompanyAddress();
                if ($companyAddress && $shippingZip && $shippingCountry) {
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
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong, please try again.'
                ], 500);
            }

            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $highestBidModel,
                'user' => $user,
                'order' => $order,
                'shipping_cost' => $shippingCost,
                'sellerAddress' => $sellerAddress,
                'buyerAddress' => $buyerAddress,
                'shippingAddress' => $shippingAddress,
                'companyInfo' => [
                    'name' => $companyName,
                    'address' => $companyAddress,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'logo' => null,
                    'logoUrl' => $companyLogo ? asset('public/' . ltrim($companyLogo, '/')) : null,
                ],
                'contractDate' => now()->format('Y-m-d'),
            ];

            $is_bid = $request->input('is_bid', false);

            if (empty($is_bid) || $is_bid == false) {
                $contractDataView['is_checkout'] = true;
                $contractDataView['buy_now_price'] = $machinery->buy_now_price;
            }

            $contractHtml = View::make('pdf.contract', $contractDataView)->render();

            return response()->json([
                'success' => true,
                'data' => $contractHtml
            ]);
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

    private function sendAuctionCancelledEmails(Machinery $machinery, User $purchaser, $bids): void
    {
        $bidderIds = $bids
            ->pluck('user_id')
            ->filter(function ($userId) use ($purchaser) {
                return (int) $userId !== (int) $purchaser->id;
            })
            ->unique()
            ->values();

        if ($bidderIds->isEmpty()) {
            return;
        }

        $bidders = User::whereIn('id', $bidderIds)->get();
        $postmarkService = new PostmarkService();

        foreach ($bidders as $bidder) {
            if (empty($bidder->email)) {
                continue;
            }

            try {
                $mail = new AuctionCancelledMail($bidder, $machinery, $purchaser);
                $postmarkService->sendEmail(
                    $bidder->email,
                    $mail->getSubject(),
                    $mail->renderHtmlContent()
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send auction cancellation email to ' . $bidder->email . ': ' . $e->getMessage());
            }
        }
    }
}
