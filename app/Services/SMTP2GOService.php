<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Settings;

class SMTP2GOService
{
    protected string $apiKey;
    protected string $senderEmail;
    protected string $senderName;

    public function __construct()
    {
        $this->apiKey      = Settings::get('postmark_api_key');
        $this->senderEmail = Settings::get('mail_from_address');
        $this->senderName  = Settings::get('mail_from_name');
    }

    /**
     * Send email using Postmark API
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
                'From'          => "{$this->senderName} <{$this->senderEmail}>",
                'To'            => is_array($to) ? implode(', ', $to) : $to,
                'Subject'       => $subject,
                'HtmlBody'      => $htmlBody,
                'MessageStream' => 'outbound',
            ];

            if ($textBody) {
                $payload['TextBody'] = $textBody;
            }

            if (! empty($attachments)) {
                $payload['Attachments'] = [];

                foreach ($attachments as $attachment) {

                    if (
                        isset($attachment['path'], $attachment['name'], $attachment['type']) &&
                        file_exists($attachment['path'])
                    ) {
                        $payload['Attachments'][] = [
                            'Name'        => $attachment['name'],
                            'Content'     => base64_encode(file_get_contents($attachment['path'])),
                            'ContentType' => $attachment['type'],
                        ];
                    } else {
                        Log::warning('Postmark attachment missing or not found', $attachment);
                    }
                }
            }

            $response = Http::withHeaders([
                'Accept'                  => 'application/json',
                'Content-Type'            => 'application/json',
                'X-Postmark-Server-Token' => $this->apiKey,
            ])->post('https://api.postmarkapp.com/email', $payload);

            return $response->successful();

        } catch (\Throwable $e) {

            return false;
        }
    }
}
