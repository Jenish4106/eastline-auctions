<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class ResendInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $order;
    public $machinery;

    public function __construct($user, $order, $machinery)
    {
        $this->user = $user;
        $this->order = $order;
        $this->machinery = $machinery;
    }

    public function build()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return $this->subject('Invoice for Order - ' . $this->order->order_id)
                    ->view('emails.resend-invoice')
                    ->with([
                        'user' => $this->user,
                        'order' => $this->order,
                        'machineryName' => $machineryName,
                    ]);
    }

    public function renderHtmlContent()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return View::make('emails.resend-invoice', [
            'user' => $this->user,
            'order' => $this->order,
            'machineryName' => $machineryName,
        ])->render();
    }

    public function getSubject()
    {
        return 'Invoice for Order - ' . $this->order->order_id;
    }
}
