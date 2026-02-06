<?php

namespace App\Console\Commands;

use App\Models\Machinery;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExtendExpiredAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extend-expired-auctions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extend expired auctions by recalculating bid start and end times';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired auctions extension process...');

        $currentTime = Carbon::now();

        $expiredMachinery = Machinery::where('bid_end_time', '<=', $currentTime)
                                    ->whereIn('status', [0, 1])
                                    ->get();

        $updatedCount = 0;

        foreach ($expiredMachinery as $machinery) {
            try {
                if ($machinery->bids()->exists()) {
                    $this->info("Skipping machinery ID {$machinery->id}: Already has bids.");
                    continue;
                }

                $bidEndDays = $machinery->bid_end_days ?? 7;

                $newStartTime = $currentTime;

                $newEndTime = $newStartTime->copy()->addDays($bidEndDays);

                $machinery->bid_start_time = $newStartTime;
                $machinery->bid_end_time = $newEndTime;
                $machinery->save();

                $updatedCount++;

                $this->info("Updated machinery ID {$machinery->id}: Start time set to {$newStartTime->format('Y-m-d H:i:s')}, End time set to {$newEndTime->format('Y-m-d H:i:s')}");

            } catch (\Exception $e) {
                $this->error("Failed to update machinery ID {$machinery->id}: ");
            }
        }

        $this->info("Process completed. Updated {$updatedCount} expired auctions.");

        return Command::SUCCESS;
    }
}
