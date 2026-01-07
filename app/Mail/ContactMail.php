<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
}