<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Machinery;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use App\Services\GoogleMapsService;
use App\Models\MachineryFileManager;
use App\Models\User;
use Illuminate\Support\Facades\View;

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

            if ($machinery->is_purchase || $machinery->bid_status == '2' || $machinery->bid_status === 'sold') {
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

            $order = Order::create([
                'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                'machinery_id' => $machinery->id,
                'user_id' => $user->id,
                'price' => $machinery->buy_now_price > 0 ? $machinery->buy_now_price : ($machinery->bid_start_price ?? 0),
                'shipping_cost' => $shippingCost,
                'purchase_date' => now(),
                'delivery_status' => 0,

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

            $machinery->update([
                'is_purchase' => true,
                'bid_status' => '2',
                'won_user' => $user->id,
                'bid_won_date' => now(),
                'contract_status' => '3',
                'status' => 2,
            ]);

            $companyName = Settings::get('company_name', 'Stiopa Equipment');
            $companyAddress = Settings::get('address') ?? '';
            $companyPhone = Settings::get('phone_no') ?? '';
            $companyEmail = Settings::get('email') ?? '';

            $companyLogo = Settings::get('dark_logo');

            $machinery->load('images');
            $firstImage = $machinery->images->firstWhere('type', 'image');
            $machineryImage = null;
            if ($firstImage) {
                 $imagePath = 'uploads/machinery/images/' . ltrim($firstImage->image_path, '/');
                 if (File::exists(public_path($imagePath))) {
                     $machineryImage = public_path($imagePath);
                 }
            }

             $data = [
                'order' => $order,
                'machineryImage' => $machineryImage,
                'companyInfo' => [
                    'name' => $companyName,
                    'address' => $companyAddress,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'logo' => $companyLogo,
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

            $pseudoBid = (object)['amount' => $order->price];

            $sellerAddress = $companyAddress;

            $buyerAddress = trim(($order->billing_street ?? '') . ', ' .
                            ($order->billing_city ?? '') . ', ' .
                            ($order->billing_state ?? '') . ' ' .
                            ($order->billing_zip ?? '') . ', ' .
                            ($order->billing_country ?? ''));
            $buyerAddress = trim(str_replace(',  ', ', ', $buyerAddress), ', ');

            $shippingAddress = $buyerAddress;
            if (!$order->shipping_same_as_billing) {
                $shippingAddress = trim(($order->shipping_street ?? '') . ', ' .
                                       ($order->shipping_city ?? '') . ', ' .
                                       ($order->shipping_state ?? '') . ' ' .
                                       ($order->shipping_zip ?? '') . ', ' .
                                       ($order->shipping_country ?? ''));
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
                'absoluteSignaturePath' => public_path($signaturePath),
                'companyInfo' => [
                    'name' => $companyName,
                    'address' => $companyAddress,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'logo' => Settings::get('dark_logo') ?? '',
                ],
                'contractDate' => now()->format('Y-m-d'),
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

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful',
                'data' => [
                    'order_id' => $order->order_id,
                    'invoice_url' => asset($path),
                    'contract_url' => asset($finalContractPath),
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

            $companyName = Settings::get('company_name', 'Stiopa Equipment');
            $companyAddress = Settings::get('address') ?? '';
            $companyPhone = Settings::get('phone_no') ?? '';
            $companyEmail = Settings::get('email') ?? '';
            $companyLogo = Settings::get('dark_logo') ?? '';

            $price = $machinery->buy_now_price > 0 ? $machinery->buy_now_price : ($machinery->bid_start_price ?? 0);

            $highestBidModel = $machinery->bids()->where('auction_id', $machinery->auction_id)->orderBy('amount', 'desc')->first();
            if ($highestBidModel) {
                $price = $highestBidModel->amount;
            } else {
                $highestBidModel = (object)['amount' => $price];
            }

            $sellerAddress = $companyAddress;

            $billing = $request->input('billing_details');
            $shipping = $request->input('shipping_details');
            $isShippingDifferent = filter_var($shipping['is_different'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $buyerAddress = trim(($billing['street_and_number'] ?? '') . ', ' .
                            ($billing['city'] ?? '') . ', ' .
                            ($billing['state_province'] ?? '') . ' ' .
                            ($billing['zip_postal_code'] ?? '') . ', ' .
                            ($billing['country'] ?? ''));
            $buyerAddress = trim(str_replace(',  ', ', ', $buyerAddress), ', ');

            $shippingAddress = $buyerAddress;
            if ($isShippingDifferent) {
                $shippingAddress = trim(($shipping['shipping_street'] ?? '') . ', ' .
                                       ($shipping['shipping_city'] ?? '') . ', ' .
                                       ($shipping['shipping_state'] ?? '') . ' ' .
                                       ($shipping['shipping_zip'] ?? '') . ', ' .
                                       ($shipping['shipping_country'] ?? ''));
                $shippingAddress = trim(str_replace(',  ', ', ', $shippingAddress), ', ');
            }

            $contractDataView = [
                'machinery' => $machinery,
                'highestBid' => $highestBidModel,
                'user' => $user,
                'order' => $order,
                'sellerAddress' => $sellerAddress,
                'buyerAddress' => $buyerAddress,
                'shippingAddress' => $shippingAddress,
                'companyInfo' => [
                    'name' => $companyName,
                    'address' => $companyAddress,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'logo' => $companyLogo,
                ],
                'contractDate' => now()->format('Y-m-d'),
            ];

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
}
