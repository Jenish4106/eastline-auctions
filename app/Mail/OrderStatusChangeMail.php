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
        $customMessage = $this->getStatusMessage($this->status);
        return $this
            ->subject('Order Status Update - ' . $this->order->order_id)
            ->view('emails.order-status-change')
            ->with([
                'user' => $this->user,
                'order' => $this->order,
                'machineryName' => $machineryName,
                'status' => $statusText,
                'customMessage' => $customMessage,
            ]);
    }

    public function renderHtmlContent()
    {
        $machineryName = $this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model;
        $statusText = $this->getStatusText($this->status);
        $customMessage = $this->getStatusMessage($this->status);
        return View::make('emails.order-status-change', [
            'user' => $this->user,
            'order' => $this->order,
            'machineryName' => $machineryName,
            'status' => $statusText,
            'customMessage' => $customMessage,
        ])->render();
    }

    public function getSubject()
    {
        return 'Order Status Update - ' . $this->order->order_id;
    }

    private function getStatusMessage($status)
    {
        switch ($status) {
            case 1:
                return 'Our team is currently reviewing your order details and verifying equipment availability to ensure a smooth and accurate transaction. Once the review process is completed and everything is confirmed, you will receive the official invoice with payment instructions as the next step.';
            case 3:
                return "Please find the attached invoice for your order. Kindly complete the payment according to the details provided in the invoice. Once the payment has been made, please upload the payment receipt through your account under 'Order Progress' so we can proceed with the shipping process.";
            case 4:
                return 'We are pleased to inform you that your payment has been successfully confirmed. Your order has now moved to Processing and is being prepared for shipment.';
        }
        return null;
    }

    private function getStatusText($status)
    {
        $statusMap = [
            0 => 'Order Submitted',
            1 => 'Sales Agreement',
            2 => 'Awaiting Invoice',
            3 => 'Settle Payment',
            4 => 'Payment Confirmed',
            5 => 'Processing',
            6 => 'Shipping Started',
            7 => 'In Transit',
            8 => 'Delivered',
            9 => 'Cancelled',
        ];
        return $statusMap[$status] ?? 'Unknown';
    }
}
