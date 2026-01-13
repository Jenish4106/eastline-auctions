<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class OutbidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $machinery;
    public $currentBid;

    public function __construct($user, $machinery, $currentBid)
    {
        $this->user = $user;
        $this->machinery = $machinery;
        $this->currentBid = $currentBid;
    }

    public function build()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return $this->subject('You Have Been Outbid - ' . $machineryName)
                    ->view('emails.outbid')
                    ->with([
                        'user' => $this->user,
                        'machineryName' => $machineryName,
                        'currentBid' => $this->currentBid,
                    ]);
    }

    public function renderHtmlContent()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return View::make('emails.outbid', [
            'user' => $this->user,
            'machineryName' => $machineryName,
            'currentBid' => $this->currentBid,
        ])->render();
    }

    public function getSubject()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return 'You Have Been Outbid - ' . $machineryName;
    }
}
