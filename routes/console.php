<?php

use App\Actions\Partners\NotifyPartnersOfUpcomingCargoDeposit;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('partners:notify-deposit-reminders', function (NotifyPartnersOfUpcomingCargoDeposit $action) {
    $count = $action->handle();

    $this->info("Partners notified: {$count}");
})->purpose('Notify partners to deposit stock N days before cargo ETA');

Schedule::command('partners:notify-deposit-reminders')->dailyAt('08:00');
