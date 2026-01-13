<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class BuyNowOrderMail extends Mailable
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
        return $this->subject('Order Confirmation - ' . $this->order->order_id)
                    ->view('emails.buy-now-order')
                    ->with([
                        'user' => $this->user,
                        'order' => $this->order,
                        'machineryName' => $machineryName,
                    ]);
    }

    public function renderHtmlContent()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        return View::make('emails.buy-now-order', [
            'user' => $this->user,
            'order' => $this->order,
            'machineryName' => $machineryName,
        ])->render();
    }

    public function getSubject()
    {
        return 'Order Confirmation - ' . $this->order->order_id;
    }
}
