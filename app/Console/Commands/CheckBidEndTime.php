<?php
namespace App\Console\Commands;

use App\Mail\SendContractMail;
use App\Models\Bid;
use App\Models\Machinery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\SMTP2GOService;

class CheckBidEndTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-bid-end-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check machinery bid_end_time and update status to sold if bidding has ended';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking bid end times...');

        $currentTime = Carbon::now();
        $machineries = Machinery::where('bid_end_time', '<', $currentTime)
            ->where('bid_status', '!=', '2')
            ->where('bid_status', '!=', 2)
            ->get();

        foreach ($machineries as $machinery) {
            $highestBid = Bid::where('machinery_id', $machinery->id)
                ->where('auction_id', $machinery->auction_id)
                ->orderBy('amount', 'desc')
                ->first();

            if ($highestBid) {
                $machinery->update([
                    'bid_status'      => '2',
                    'won_user'        => $highestBid->user_id,
                    'bid_won_date'    => Carbon::now(),
                    'contract_status' => '0'
                ]);

                $user = User::find($highestBid->user_id);

                if ($user) {
                    $mail = new SendContractMail($user, $machinery, null);
                    $smtp2goService = new SMTP2GOService();
                    $htmlContent = $mail->renderHtmlContent();
                    $result = $smtp2goService->sendEmail($user->email, $mail->getSubject(), $htmlContent);

                    if ($result) {
                        $this->info("Machinery ID {$machinery->id} updated to sold status. Notification sent to user {$user->email}");
                    } else {
                        $this->info("Machinery ID {$machinery->id} updated to sold status. Failed to send email to user {$user->email}");
                    }
                } else {
                    $this->info("Machinery ID {$machinery->id} updated to sold status. Won by user ID {$highestBid->user_id}, but user not found");
                }
            }
            else {
                $this->info("Machinery ID {$machinery->id} expired (No bids found)");
            }
        }

        $this->info('Bid end time check completed successfully.');
    }
}
