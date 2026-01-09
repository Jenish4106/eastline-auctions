<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleMapsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DistanceController extends Controller
{
    protected $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    /**
     * Calculate the driving distance between the company address and customer location
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateDistance(Request $request): JsonResponse
    {
        try {
            // Validate the input
            $validator = $request->validate([
                'zip_code' => 'required|string|max:20',
                'country' => 'required|string|max:100',
            ]);

            $zipCode = $request->input('zip_code');
            $country = $request->input('country');

            // Get the company address from settings
            $companyAddress = $this->googleMapsService->getCompanyAddress();

            if (!$companyAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company address not configured in settings'
                ], 400);
            }

            // Geocode the company address
            $companyLocation = $this->googleMapsService->geocodeAddress($companyAddress);

            if (!$companyLocation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to geocode company address: ' . $companyAddress
                ], 400);
            }

            // Geocode the customer location using ZIP code and country
            $customerLocation = $this->googleMapsService->getCoordinatesFromZipAndCountry($zipCode, $country);

            if (!$customerLocation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to geocode customer location: ' . $zipCode . ', ' . $country
                ], 400);
            }

            // Calculate the distance between the two locations
            $distanceResult = $this->googleMapsService->calculateDistance($companyLocation, $customerLocation);

            if (!$distanceResult) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to calculate distance between locations'
                ], 400);
            }

            // Return successful response
            return response()->json([
                'status' => 'success',
                'company_address' => $companyAddress,
                'customer_zip' => $zipCode,
                'country' => $country,
                'distance_miles' => $distanceResult['distance_miles'],
                'distance_text' => $distanceResult['distance_text'],
                'duration_seconds' => $distanceResult['duration_seconds'],
                'duration_text' => $distanceResult['duration_text']
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}