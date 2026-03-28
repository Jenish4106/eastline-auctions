<?php
namespace App\Console\Commands;

use App\Models\Machinery;
use App\Services\AuctionCompletionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckBidEndTime extends Command
{
    protected $auctionCompletionService;

    public function __construct(AuctionCompletionService $auctionCompletionService)
    {
        parent::__construct();
        $this->auctionCompletionService = $auctionCompletionService;
    }

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
            ->where('bid_status', '!=', '3')
            ->where('bid_status', '!=', 3)
            ->get();

        foreach ($machineries as $machinery) {
            $result = $this->auctionCompletionService->complete($machinery);

            if ($result['highest_bid']) {
                if ($result['winner']) {
                    if ($result['email_sent']) {
                        $this->info("Machinery ID {$machinery->id} updated to completed status. Notification sent to user {$result['winner']->email}");
                    } else {
                        $this->info("Machinery ID {$machinery->id} updated to completed status. Failed to send email to user {$result['winner']->email}");
                    }
                } else {
                    $this->info("Machinery ID {$machinery->id} updated to completed status. Won by user ID {$result['highest_bid']->user_id}, but user not found");
                }
            } else {
                $this->info("Machinery ID {$machinery->id} updated to completed status (No bids found)");
            }
        }

        $this->info('Bid end time check completed successfully.');
    }
}
