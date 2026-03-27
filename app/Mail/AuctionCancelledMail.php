<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class AuctionCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $machinery;
    public $purchaser;

    public function __construct($user, $machinery, $purchaser)
    {
        $this->user = $user;
        $this->machinery = $machinery;
        $this->purchaser = $purchaser;
    }

    public function build()
    {
        $machineryName = $this->getMachineryName();

        return $this->subject('Auction Cancelled - ' . $machineryName)
            ->view('emails.auction-cancelled')
            ->with([
                'user' => $this->user,
                'machineryName' => $machineryName,
                'purchaserName' => trim(($this->purchaser->first_name ?? '') . ' ' . ($this->purchaser->last_name ?? '')),
            ]);
    }

    public function renderHtmlContent()
    {
        return View::make('emails.auction-cancelled', [
            'user' => $this->user,
            'machineryName' => $this->getMachineryName(),
            'purchaserName' => trim(($this->purchaser->first_name ?? '') . ' ' . ($this->purchaser->last_name ?? '')),
        ])->render();
    }

    public function getSubject()
    {
        return 'Auction Cancelled - ' . $this->getMachineryName();
    }

    private function getMachineryName()
    {
        return trim($this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model);
    }
}
