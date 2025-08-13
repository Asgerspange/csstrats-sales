<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\{
    Artisan,
    Schedule
};

Schedule::command('app:fetch-stripe-data')->hourly()->appendOutputTo(storage_path('logs/stripe_data.log'));