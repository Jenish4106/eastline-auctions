<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class SendContractMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $machinery;
    public $contractPath;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $machinery, $contractPath)
    {
        $this->user = $user;
        $this->machinery = $machinery;
        $this->contractPath = $contractPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Equipment Sales Contract - ' . $this->machinery->make . ' ' . $this->machinery->model,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contract',
            with: [
                'machineryName' => trim($this->machinery->year . ' ' . $this->machinery->make . ' ' . $this->machinery->model),
                'finalBidAmount' => $this->getHighestBidAmount(),
                'winningDate' => now()->format('Y-m-d'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function getHighestBidAmount()
    {
        $highestBid = \App\Models\Bid::where('machinery_id', $this->machinery->id)
            ->orderBy('amount', 'desc')
            ->first();
        
        return $highestBid ? $highestBid->amount : 'N/A';
    }
}
