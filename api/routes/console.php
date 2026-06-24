<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$interval = max(1, (int) config('client_bot.scheduler_interval_minutes', 5));

Schedule::command('pikhlak:crm-sync')->cron("*/{$interval} * * * *");
Schedule::command('pikhlak:client-bot-notify')->cron("*/{$interval} * * * *");
