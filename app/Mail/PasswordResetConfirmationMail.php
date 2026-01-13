<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

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
        return $this->subject('Password Reset Successful - RB Equipment Sales')
                    ->view('emails.password-reset-confirmation')
                    ->with(['user' => $this->user]);
    }

    public function renderHtmlContent()
    {
        return View::make('emails.password-reset-confirmation', ['user' => $this->user])->render();
    }

    public function getSubject()
    {
        return 'Password Reset Successful - RB Equipment Sales';
    }
}
