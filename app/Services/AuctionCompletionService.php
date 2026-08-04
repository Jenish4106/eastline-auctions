<?php

namespace App\Services;

use App\Mail\SendContractMail;
use App\Services\TwilioSmsService;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\User;
use Carbon\Carbon;

class AuctionCompletionService
{
    protected TwilioSmsService $smsService;
    public function __construct(TwilioSmsService $smsService)
    {
        $this->smsService = $smsService;
    }
    public function complete(Machinery $machinery, bool $sendWinnerEmail = true): array
    {
        $highestBid = Bid::where('machinery_id', $machinery->id)
            ->where('auction_id', $machinery->auction_id)
            ->whereHas('user')
            ->orderByDesc('amount')
            ->orderBy('id')
            ->first();

        if ($highestBid) {
            $updateData = [
                'won_user' => $highestBid->user_id,
                'bid_won_date' => Carbon::now(),
                'contract_status' => 0,
                'bid_status' => 2,
                'status' => 2,
            ];
        } else {
            $updateData = [
                'won_user' => null,
                'bid_won_date' => null,
                'status' => 1,
                'bid_status' => 0,
            ];
        }

        $machinery->update($updateData);

        $emailSent = false;
        $winner = null;

        if ($highestBid) {
            $winner = User::find($highestBid->user_id);

            if ($winner) {
                //Email
                if ($sendWinnerEmail) {
                    $mail = new SendContractMail($winner, $machinery->fresh(), null);
                    $mailtrapService = new \App\Services\MailtrapService();
                    $htmlContent = $mail->renderHtmlContent();
                    $emailSent = $mailtrapService->sendEmail($winner->email, $mail->getSubject(), $htmlContent);
                }

                //Sms
                $message = "Thank you for your purchase with McFarland Equipment Sales & Auctions! Your Won item is secured. Sign in to view your invoice and complete payment.";
                $smsSent = $this->smsService->sendMessage(
                    $winner->phone_no,
                    $message
                );
            }
        }

        return [
            'machinery' => $machinery->fresh(),
            'highest_bid' => $highestBid,
            'winner' => $winner,
            'email_sent' => $emailSent,
        ];
    }
}
