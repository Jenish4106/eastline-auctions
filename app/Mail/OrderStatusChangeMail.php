<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class OrderStatusChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $order;
    public $machinery;
    public $status;

    public function __construct($user, $order, $machinery, $status)
    {
        $this->user = $user;
        $this->order = $order;
        $this->machinery = $machinery;
        $this->status = $status;
    }

    public function build()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        $statusText = $this->getStatusText($this->status);
        return $this->subject('Order Status Update - ' . $this->order->order_id)
                    ->view('emails.order-status-change')
                    ->with([
                        'user' => $this->user,
                        'order' => $this->order,
                        'machineryName' => $machineryName,
                        'status' => $statusText,
                    ]);
    }

    public function renderHtmlContent()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        $statusText = $this->getStatusText($this->status);
        return View::make('emails.order-status-change', [
            'user' => $this->user,
            'order' => $this->order,
            'machineryName' => $machineryName,
            'status' => $statusText,
        ])->render();
    }

    public function getSubject()
    {
        return 'Order Status Update - ' . $this->order->order_id;
    }

    private function getStatusText($status)
    {
        $statusMap = [
            0 => 'Process',
            1 => 'Shipped',
            2 => 'In Transit',
            3 => 'Delivered',
            4 => 'Cancelled',
        ];
        return $statusMap[$status] ?? 'Unknown';
    }
}
