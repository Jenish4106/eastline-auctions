<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class MailtrapService
{
    protected string $host;
    protected string $port;
    protected string $username;
    protected string $password;
    protected string $senderEmail;
    protected string $senderName;

    public function __construct()
    {
        $this->host        = \App\Models\Settings::get('mailtrap_host', 'live.smtp.mailtrap.io');
        $this->port        = \App\Models\Settings::get('mailtrap_port', '587');
        $this->username    = \App\Models\Settings::get('mailtrap_username', 'api');
        $this->password    = \App\Models\Settings::get('mailtrap_password', 'fe5b85891e0ed3f9ec515cf73d39747f');
        $this->senderEmail = \App\Models\Settings::get('mailtrap_sender_email', 'info@eastlineauctions.com');
        $this->senderName  = \App\Models\Settings::get('mailtrap_sender_name', 'Eastline Equipment Sales & Auctions');
    }

    /**
     * Send email via Mailtrap SMTP
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
            $scheme = ((int) $this->port === 465) ? 'smtps' : 'smtp';
            $dsn = sprintf(
                '%s://%s:%s@%s:%s',
                $scheme,
                rawurlencode($this->username),
                rawurlencode($this->password),
                $this->host,
                $this->port
            );

            $transport = Transport::fromDsn($dsn);
            $mailer    = new Mailer($transport);

            $email = (new Email())
                ->from(new \Symfony\Component\Mime\Address($this->senderEmail, $this->senderName))
                ->to(...(is_array($to) ? $to : [$to]))
                ->subject($subject)
                ->html($htmlBody);

            if ($textBody) {
                $email->text($textBody);
            }

            foreach ($attachments as $attachment) {
                if (isset($attachment['content'], $attachment['name'], $attachment['type'])) {
                    $email->attach($attachment['content'], $attachment['name'], $attachment['type']);
                } elseif (isset($attachment['path'], $attachment['name'], $attachment['type'])) {
                    if (file_exists($attachment['path'])) {
                        $email->attachFromPath($attachment['path'], $attachment['name'], $attachment['type']);
                    } else {
                        $content = \App\Services\S3StorageService::getFileContent($attachment['path']);
                        if ($content) {
                            $email->attach($content, $attachment['name'], $attachment['type']);
                        } else {
                            Log::warning('Mailtrap attachment missing or not found', $attachment);
                        }
                    }
                } else {
                    Log::warning('Mailtrap attachment format invalid', $attachment);
                }
            }

            $mailer->send($email);

            Log::info('Mailtrap Email Sent Successfully', [
                'to'      => $to,
                'subject' => $subject,
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('Mailtrap Email Sending Failed: ' . $e->getMessage(), [
                'to'      => $to,
                'subject' => $subject,
                'trace'   => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
