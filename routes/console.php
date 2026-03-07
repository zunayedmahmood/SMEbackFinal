<?php

use App\Jobs\ExpirePendingOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    ExpirePendingOrders::dispatch();
})->everyFifteenMinutes();
