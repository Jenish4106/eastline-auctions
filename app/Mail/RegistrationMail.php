<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;
use App\Models\Settings;

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Welcome to ' . Settings::get('company_name', 'Mcfarland Equipment'))
                    ->view('emails.registration')
                    ->with(['user' => $this->user]);
    }

    public function renderHtmlContent()
    {
        return View::make('emails.registration', ['user' => $this->user])->render();
    }

    public function getSubject()
    {
        return 'Welcome to ' . Settings::get('company_name', 'Mcfarland Equipment');
    }
}
