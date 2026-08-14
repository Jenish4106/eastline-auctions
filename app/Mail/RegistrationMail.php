<?php

namespace App\Mail;

use App\Models\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

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
        return $this
            ->subject('Welcome to ' . Settings::get('company_name', 'Eastline Equipment Auctions'))
            ->view('emails.registration')
            ->with(['user' => $this->user]);
    }

    public function renderHtmlContent()
    {
        return View::make('emails.registration', ['user' => $this->user])->render();
    }

    public function getSubject()
    {
        return 'Welcome to ' . Settings::get('company_name', 'Eastline Equipment Auctions');
    }
}
