<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:extend-expired-auctions')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/ExpireTime.log'));

Schedule::command('app:check-bid-end-time')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/checkBidEndTime.log'));
