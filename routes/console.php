<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/* Deletes prunable models, e.g. guest bags untouched for 30 days (App\Models\Cart::prunable()). */
Schedule::command('model:prune')->daily();
