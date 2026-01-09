<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $contactData;

    public function __construct($firstName, $lastName, $email, $phone, $userMessage)
    {
        $this->contactData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'userMessage' => $userMessage,
        ];
    }

    public function build()
    {
        $fullName = $this->contactData['firstName'] . ' ' . $this->contactData['lastName'];
        
        return $this->subject('New Contact Form Submission - ' . config('app.name', 'RB Equipment Sales'))
                    ->view('emails.contact')
                    ->with(array_merge($this->contactData, ['fullName' => $fullName]));
    }

    /**
     * Render the HTML content of the email for SMTP2GO service
     */
    public function renderHtmlContent()
    {
        $fullName = $this->contactData['firstName'] . ' ' . $this->contactData['lastName'];
        $data = array_merge($this->contactData, ['fullName' => $fullName]);
        return View::make('emails.contact', $data)->render();
    }

    /**
     * Get the subject for SMTP2GO service
     */
    public function getSubject()
    {
        return 'New Contact Form Submission - ' . config('app.name', 'RB Equipment Sales');
    }
}