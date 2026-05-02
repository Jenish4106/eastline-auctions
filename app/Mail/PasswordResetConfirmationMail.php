<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;
use App\Models\Settings;

class PasswordResetConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Password Reset Successful - ' . Settings::get('company_name', 'Mcfarland Equipment'))
                    ->view('emails.password-reset-confirmation')
                    ->with(['user' => $this->user]);
    }

    public function renderHtmlContent()
    {
        return View::make('emails.password-reset-confirmation', ['user' => $this->user])->render();
    }

    public function getSubject()
    {
        return 'Password Reset Successful - ' . Settings::get('company_name', 'Mcfarland Equipment');
    }
}
