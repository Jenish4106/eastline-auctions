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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'vat_number' => 'nullable|string|max:50',

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

            // Calculate Shipping Cost
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

            $order = Order::create([
                'order_id' => 'ORD-' . strtoupper(Str::random(10)),
                'machinery_id' => $machinery->id,
                'user_id' => $user->id,
                'price' => $machinery->buy_now_price > 0 ? $machinery->buy_now_price : ($machinery->bid_start_price ?? 0),
                'shipping_cost' => $shippingCost,
                'purchase_date' => now(),
                'delivery_status' => 0,
                // 'process_date' => now(), // Removed as it's now set on status 1 (Process)

                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'vat_number' => $request->vat_number ?? null,

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
            ]);

            $companyName = Settings::get('company_name') ?? 'RB Equipment Sales';
            $companyAddress = Settings::get('address') ?? '';
            $companyPhone = Settings::get('phone_no') ?? '';
            $companyEmail = Settings::get('email') ?? '';

            $companyLogo = Settings::get('dark_logo');
            $companyLogoPath = null;
            if ($companyLogo && File::exists(public_path($companyLogo))) {
                $companyLogoPath = public_path($companyLogo);
            }

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
                    'logo' => $companyLogoPath,
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

            $order->update([
                'invoice_path' => $path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checkout successful',
                'data' => [
                    'order_id' => $order->order_id,
                    'invoice_url' => asset($path)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, please try again.'
            ], 500);
        }
    }
}
