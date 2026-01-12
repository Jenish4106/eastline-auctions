<?php

namespace App\Services;

use App\Models\Settings;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GoogleMapsService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('GOOGLE_MAPS_API_KEY');
    }

    /**
     * Get the company address from settings
     *
     * @return string|null
     */
    public function getCompanyAddress()
    {
        $address = Settings::get('address');
        return $address ?: null;
    }

    /**
     * Geocode an address to get latitude and longitude
     *
     * @param string $address
     * @return array|null
     */
    public function geocodeAddress(string $address)
    {
        if (!$this->apiKey) {
            throw new \Exception('Google Maps API key is not configured');
        }

        try {
            $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'address' => $address,
                    'key' => $this->apiKey,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng']
                ];
            }

            return null;
        } catch (RequestException $e) {
            return null;
        }
    }

    /**
     * Get coordinates from ZIP code and country
     *
     * @param string $zipCode
     * @param string $country
     * @return array|null
     */
    public function getCoordinatesFromZipAndCountry(string $zipCode, string $country)
    {
        if (!$this->apiKey) {
            throw new \Exception('Google Maps API key is not configured');
        }

        $address = $zipCode . ', ' . $country;

        try {
            $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'address' => $address,
                    'key' => $this->apiKey,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'lat' => $location['lat'],
                    'lng' => $location['lng']
                ];
            }

            return null;
        } catch (RequestException $e) {
            return null;
        }
    }

    /**
     * Calculate the driving distance between two locations
     *
     * @param array $origin Location array with 'lat' and 'lng'
     * @param array $destination Location array with 'lat' and 'lng'
     * @return array|null
     */
    public function calculateDistance(array $origin, array $destination)
    {
        if (!$this->apiKey) {
            throw new \Exception('Google Maps API key is not configured');
        }

        try {
            $response = $this->client->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'query' => [
                    'origins' => $origin['lat'] . ',' . $origin['lng'],
                    'destinations' => $destination['lat'] . ',' . $destination['lng'],
                    'mode' => 'driving',
                    'units' => 'imperial',
                    'key' => $this->apiKey,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK' && !empty($data['rows'][0]['elements'][0])) {
                $element = $data['rows'][0]['elements'][0];
                
                if ($element['status'] === 'OK') {
                    return [
                        'distance_miles' => round($element['distance']['value'] / 1609.344, 2),
                        'distance_text' => $element['distance']['text'],
                        'duration_seconds' => $element['duration']['value'],
                        'duration_text' => $element['duration']['text']
                    ];
                }
            }

            return null;
        } catch (RequestException $e) {
            return null;
        }
    }
}