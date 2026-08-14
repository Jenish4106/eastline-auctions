<?php

namespace App\Mail;

use App\Models\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class LicenseDeclinedMail extends Mailable
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
            ->subject('License Declined - ' . Settings::get('company_name', 'Eastline Equipment Auctions'))
            ->view('emails.license-declined')
            ->with(['user' => $this->user]);
    }

    public function renderHtmlContent()
    {
        return View::make('emails.license-declined', ['user' => $this->user])->render();
    }

    public function getSubject()
    {
        return 'License Declined - ' . Settings::get('company_name', 'Eastline Equipment Auctions');
    }
}
