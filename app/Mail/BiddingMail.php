<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class BiddingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $machinery;
    public $bidAmount;

    public function __construct($user, $machinery, $bidAmount)
    {
        $this->user = $user;
        $this->machinery = $machinery;
        $this->bidAmount = $bidAmount;
    }

    public function build()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return $this->subject('Bid Placed Successfully - ' . $machineryName)
                    ->view('emails.bidding')
                    ->with([
                        'user' => $this->user,
                        'machineryName' => $machineryName,
                        'bidAmount' => $this->bidAmount,
                    ]);
    }

    public function renderHtmlContent()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return View::make('emails.bidding', [
            'user' => $this->user,
            'machineryName' => $machineryName,
            'bidAmount' => $this->bidAmount,
        ])->render();
    }

    public function getSubject()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return 'Bid Placed Successfully - ' . $machineryName;
    }
}
