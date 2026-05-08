<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsService
{
    private ?string $accountSid;

    private ?string $apiKeySid;

    private ?string $apiKeySecret;

    private ?string $from;

    private ?string $messagingServiceSid;

    private string $defaultCountryCode;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->apiKeySid = config('services.twilio.api_key_sid');
        $this->apiKeySecret = config('services.twilio.api_key_secret');
        $this->from = config('services.twilio.from');
        $this->messagingServiceSid = config('services.twilio.messaging_service_sid');
        $this->defaultCountryCode = config('services.twilio.default_country_code', '+1');
    }

    public function sendMessage(?string $to, string $message): bool
    {
        $to = $this->normalizePhoneNumber($to);
        if (! $this->isConfigured() || ! $to) {
            Log::warning('Twilio SMS skipped - missing config or phone', [
                'to' => $to,
            ]);
            return false;
        }

        $payload = [
            'To' => $to,
            'Body' => $message,
        ];

        if ($this->messagingServiceSid) {
            $payload['MessagingServiceSid'] = $this->messagingServiceSid;
        } else {
            $payload['From'] = $this->from;
        }

        try {

            $response = Http::asForm()
                ->withBasicAuth($this->apiKeySid, $this->apiKeySecret)
                ->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json",
                    $payload
                );

            $responseData = $response->json();
            if (! $response->successful()) {

                Log::error('Twilio SMS Failed', [
                    'status' => $response->status(),
                    'response' => $responseData,
                    'to' => $to,
                    'payload' => $payload,
                ]);

                return false;
            }
            Log::info('Twilio SMS Sent Successfully', [
                'sid' => $responseData['sid'] ?? null,
                'to' => $to,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Twilio SMS Exception', [
                'message' => $e->getMessage(),
                'to' => $to,
            ]);
            return false;
        }
    }

    private function isConfigured(): bool
    {
        return ! empty($this->accountSid)
            && ! empty($this->apiKeySid)
            && ! empty($this->apiKeySecret)
            && (! empty($this->from) || ! empty($this->messagingServiceSid));
    }

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
            return '+'.substr($phoneNumber, 2);
        }

        $digits = preg_replace('/\D/', '', $phoneNumber);
        if (strlen($digits) === 10) {
            return rtrim($this->defaultCountryCode, ' ').$digits;
        }

        if (strlen($digits) > 10) {
            return '+'.$digits;
        }

        return null;
    }
}
