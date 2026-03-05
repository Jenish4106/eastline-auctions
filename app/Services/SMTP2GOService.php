<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Settings;

class SMTP2GOService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $senderEmail;
    protected string $senderName;

    public function __construct()
    {
        $this->apiKey      = Settings::get('smtp2go_api_key');
        $this->apiUrl      = Settings::get('smtp2go_api_url');
        $this->senderEmail = Settings::get('mail_from_address');
        $this->senderName  = Settings::get('mail_from_name');
    }

    /**
     * Send email using SMTP2GO API
     *
     * @param string|array $to
     * @param string $subject
     * @param string $htmlBody
     * @param array $attachments
     * @param string|null $textBody
     * @return bool
     */
    public function sendEmail($to, string $subject, string $htmlBody, array $attachments = [], ?string $textBody = null): bool
    {
        try {

            $payload = [
                'api_key'   => $this->apiKey,
                'sender'    => "{$this->senderName} <{$this->senderEmail}>",
                'to'        => is_array($to) ? $to : [$to],
                'subject'   => $subject,
                'html_body' => $htmlBody,
            ];

            if ($textBody) {
                $payload['text_body'] = $textBody;
            }

            if (! empty($attachments)) {
                $payload['attachments'] = [];

                foreach ($attachments as $attachment) {

                    if (
                        isset($attachment['path'], $attachment['name'], $attachment['type']) &&
                        file_exists($attachment['path'])
                    ) {
                        $payload['attachments'][] = [
                            'filename'      => $attachment['name'],
                            'fileblob'      => base64_encode(file_get_contents($attachment['path'])),
                            'mimetype'  => $attachment['type'],
                        ];

                        Log::info('SMTP2GO attachment added', [
                            'file' => $attachment['path'],
                        ]);
                    } else {
                        Log::warning('SMTP2GO attachment missing or not found', $attachment);
                    }
                }
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post($this->apiUrl, $payload);

            Log::info('SMTP2GO response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return $response->successful();

        } catch (\Throwable $e) {
            Log::error('SMTP2GO send failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
