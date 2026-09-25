<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mirror future-dated salary structures whose effective_from has arrived.
Schedule::command('payroll:activate-structures')->dailyAt('01:00');
