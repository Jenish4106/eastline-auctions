<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMTP2GOService
{
    private $apiKey;
    private $apiUrl;
    private $senderEmail;
    private $senderName;

    public function __construct()
    {
        $this->apiKey = env('SMTP2GO_API_KEY', 'api-FB7FBB0BD14A413DA64AFC6D86660F46');
        $this->apiUrl = env('SMTP2GO_API_URL', 'https://api.smtp2go.com/v3/email/send');
        $this->senderEmail = env('MAIL_FROM_ADDRESS', 'info@stiopa-equipment.com');
        $this->senderName = env('MAIL_FROM_NAME', 'RB EQUIPMENT SALES');
    }

    public function sendEmail($to, $subject, $htmlBody, $textBody = null)
    {
        try {
            $data = [
                'sender' => $this->senderEmail,
                'to' => is_array($to) ? $to : [$to],
                'subject' => $subject,
                'html_body' => $htmlBody,
            ];

            if ($textBody) {
                $data['text_body'] = $textBody;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Smtp2go-Api-Key' => $this->apiKey,
                'accept' => 'application/json',
            ])->post($this->apiUrl, $data);

            if ($response->successful()) {
                Log::info('Email sent successfully via SMTP2GO', [
                    'to' => $to,
                    'subject' => $subject,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Failed to send email via SMTP2GO', [
                    'to' => $to,
                    'subject' => $subject,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while sending email via SMTP2GO', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}