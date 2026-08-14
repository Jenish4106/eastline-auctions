<?php

namespace App\Services;

use Pingram\Client;
use Pingram\Model\SendSmsRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PingramSmsService
{
    private string $apiKey;
    private string $baseUrl;
    private string $notificationType;
    private string $defaultCountryCode;

    public function __construct()
    {
        $this->apiKey = config('services.pingram.api_key', 'pingram_sk_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJrZXlfOWFlYWViN2QzYjZlMDE1MWZjOTAwMWQ3OTIyMjViMTUiLCJ2ZXJzaW9uIjoxLCJhY2NvdW50SWQiOiI1eDJmZm90eXM5aHBxYmxzaGgxZWk2bnVlNyIsImtleVR5cGUiOiJzZWNyZXQiLCJlbnZpcm9ubWVudElkIjoiNXgyZmZvdHlzOWhwcWJsc2hoMWVpNm51ZTcifQ.emdCQr3WYYXODlaf3knTpmcRxirHPCgYtFEMtajmhq4');
        $this->baseUrl = config('services.pingram.base_url', 'https://api.eu.pingram.io');
        $this->notificationType = config('services.pingram.notification_type', 'sms_compose_preview');
        $this->defaultCountryCode = config('services.pingram.default_country_code', '+1');
    }

    /**
     * Send SMS message using official Pingram SDK / EU REST API
     *
     * @param string|null $to
     * @param string $message
     * @return bool
     */
    public function sendMessage(?string $to, string $message): bool
    {
        $to = $this->normalizePhoneNumber($to);
        if (!$to) {
            // Log::warning('Pingram SMS skipped - invalid or missing phone number', [
            //     'to' => $to,
            // ]);
            return false;
        }

        $payload = [
            'type' => $this->notificationType,
            'to' => $to,
            'message' => $message,
        ];

        try {
            $client = new Client($this->apiKey, $this->baseUrl);
            $body = new SendSmsRequest($payload);

            $client->getSms()->smsSend($body);

            // Log::info('Pingram SMS Sent Successfully via Pingram SDK', ['to' => $to]);
            return true;
        } catch (\Throwable $sdkException) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post(rtrim($this->baseUrl, '/') . '/sms', $payload);

                if ($response->successful()) {
                    // Log::info('Pingram SMS Sent Successfully via HTTP API', [
                    //     'to' => $to,
                    //     'endpoint' => rtrim($this->baseUrl, '/') . '/sms',
                    // ]);
                    return true;
                }

                // Log::error('Pingram SMS Failed via HTTP API', [
                //     'status' => $response->status(),
                //     'response' => $response->json() ?? $response->body(),
                //     'to' => $to,
                // ]);
            } catch (\Throwable $httpException) {
                // Log::error('Pingram SMS HTTP Exception', [
                //     'message' => $httpException->getMessage(),
                //     'to' => $to,
                // ]);
            }

            return false;
        }
    }

    /**
     * Format phone number to E.164
     */
    private function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        if (empty($phoneNumber)) {
            return null;
        }

        $phoneNumber = trim($phoneNumber);
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);

        if (str_starts_with($phoneNumber, '+')) {
            return $phoneNumber;
        }

        if (str_starts_with($phoneNumber, '00')) {
            return '+' . substr($phoneNumber, 2);
        }

        $digits = preg_replace('/\D/', '', $phoneNumber);
        if (strlen($digits) === 10) {
            return rtrim($this->defaultCountryCode, ' ') . $digits;
        }

        if (strlen($digits) > 10) {
            return '+' . $digits;
        }

        return null;
    }
}
