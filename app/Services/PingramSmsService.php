<?php

namespace App\Services;

use Pingram\Client;
use Pingram\Model\SendSmsRequest;
use Illuminate\Support\Facades\Log;

class PingramSmsService
{
    private string $apiKey;
    private string $notificationType;
    private string $defaultCountryCode;

    public function __construct()
    {
        $this->apiKey = config('services.pingram.api_key', 'pingram_sk_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJrZXlfOWFlYWViN2QzYjZlMDE1MWZjOTAwMWQ3OTIyMjViMTUiLCJ2ZXJzaW9uIjoxLCJhY2NvdW50SWQiOiI1eDJmZm90eXM5aHBxYmxzaGgxZWk2bnVlNyIsImtleVR5cGUiOiJzZWNyZXQiLCJlbnZpcm9ubWVudElkIjoiNXgyZmZvdHlzOWhwcWJsc2hoMWVpNm51ZTcifQ.emdCQr3WYYXODlaf3knTpmcRxirHPCgYtFEMtajmhq4');
        $this->notificationType = config('services.pingram.notification_type', 'welcome_sms');
        $this->defaultCountryCode = config('services.pingram.default_country_code', '+1');
    }

    /**
     * Send SMS message using official Pingram SDK (Client & SendSmsRequest)
     *
     * @param string|null $to
     * @param string $message
     * @return bool
     */
    public function sendMessage(?string $to, string $message): bool
    {
        $to = $this->normalizePhoneNumber($to);
        if (!$to) {
            Log::warning('Pingram SMS skipped - invalid or missing phone number', [
                'to' => $to,
            ]);
            return false;
        }

        Log::info('Api key:- ' . $this->apiKey);
        Log::info('Notification type:- ' . $this->notificationType);
        Log::info('Default country code:- ' . $this->defaultCountryCode);
        Log::info('To:- ' . $to);
        Log::info('Message:- ' . $message);

        try {
            $client = new Client($this->apiKey);

            $body = new SendSmsRequest([
                'type' => $this->notificationType,
                'to' => $to,
                'message' => $message,
            ]);

            $client->getSms()->smsSend($body);

            Log::info('Pingram SMS Sent Successfully via Pingram SDK', ['to' => $to]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Pingram SMS Exception via Pingram SDK', [
                'message' => $e->getMessage(),
                'to' => $to,
            ]);
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
