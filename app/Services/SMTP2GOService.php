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

    public function sendEmail($to, $subject, $htmlBody, $attachments = [], $textBody = null)
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

            if (!empty($attachments)) {
                $data['attachments'] = [];
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $data['attachments'][] = [
                            'filename' => $attachment['name'] ?? basename($attachment['path']),
                            'file_contents' => base64_encode(file_get_contents($attachment['path'])),
                            'content_type' => $attachment['type'] ?? mime_content_type($attachment['path'])
                        ];
                    }
                }
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Smtp2go-Api-Key' => $this->apiKey,
                'accept' => 'application/json',
            ])->post($this->apiUrl, $data);

            if ($response->successful()) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
