<?php

namespace App\Services;

use App\Mail\SendContractMail;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\User;
use Carbon\Carbon;

class AuctionCompletionService
{
    public function complete(Machinery $machinery, bool $sendWinnerEmail = true): array
    {
        $highestBid = Bid::where('machinery_id', $machinery->id)
            ->where('auction_id', $machinery->auction_id)
            ->whereHas('user')
            ->orderByDesc('amount')
            ->orderBy('id')
            ->first();

        $updateData = [
            'bid_status' => '2',
        ];

        if ($highestBid) {
            $updateData = array_merge($updateData, [
                'won_user' => $highestBid->user_id,
                'bid_won_date' => Carbon::now(),
                'contract_status' => 0,
                'bid_status' => 2,
                'status' => 2,
                'is_purchase' => 1,
            ]);
        } else {
            $updateData = array_merge($updateData, [
                'won_user' => null,
                'bid_won_date' => null,
                'status' => 2,
                'is_purchase' => 1,
            ]);
        }

        $machinery->update($updateData);

        $emailSent = false;
        $winner = null;

        if ($highestBid) {
            $winner = User::find($highestBid->user_id);

            if ($sendWinnerEmail && $winner) {
                $mail = new SendContractMail($winner, $machinery->fresh(), null);
                $smtp2goService = new SMTP2GOService();
                $htmlContent = $mail->renderHtmlContent();
                $emailSent = $smtp2goService->sendEmail($winner->email, $mail->getSubject(), $htmlContent);
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
