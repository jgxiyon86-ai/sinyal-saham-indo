<?php

use App\Support\GatewaySetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('wa:auto-birthday')
    ->dailyAt(GatewaySetting::birthdayAutoTime())
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('signals:publish-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('signals:cleanup-expired')
    ->dailyAt(env('SIGNAL_CLEANUP_TIME', '01:00'))
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('signals:process-wa-queue')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
