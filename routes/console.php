<?php

use App\Jobs\SendWeeklyReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SendWeeklyReport, 'reports')
    ->weeklyOn(5, '08:00') // every friday at 8 am
    ->name('weekly_published_articles_report_job')
    ->withoutOverlapping()
    ->onFailure(function () {
        Log::error('Scheduler: Failed to send weekly published articles report.');
    });

Schedule::command('articles:archive')->monthlyOn(1, '00:00');
Schedule::command('articles:report')->weeklyOn(5, '08:00');
