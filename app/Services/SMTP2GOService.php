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
        $this->apiKey      = env('SMTP2GO_API_KEY');
        $this->apiUrl      = env('SMTP2GO_API_URL', 'https://api.smtp2go.com/v3/email/send');
        $this->senderEmail = env('MAIL_FROM_ADDRESS');
        $this->senderName  = env('MAIL_FROM_NAME');
    }

    public function sendEmail($to, $subject, $htmlBody, $attachments = [], $textBody = null)
    {
        try {
            $data = [
                'sender'     => $this->senderEmail,
                'to'         => is_array($to) ? $to : [$to],
                'subject'    => $subject,
                'html_body'  => $htmlBody,
            ];

            if ($textBody) {
                $data['text_body'] = $textBody;
            }

            if (!empty($attachments)) {
                $data['attachments'] = [];

                foreach ($attachments as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $data['attachments'][] = [
                            'filename'      => $attachment['name'],
                            'fileblob' => base64_encode(file_get_contents($attachment['path'])),
                            'content_type'  => $attachment['type'],
                        ];
                    }
                }
            }

            $response = Http::withHeaders([
                'X-Smtp2go-Api-Key' => $this->apiKey,
                'Content-Type'     => 'application/json',
                'accept'           => 'application/json',
            ])->post($this->apiUrl, $data);

            Log::info('SMTP2GO response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('SMTP2GO send failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
