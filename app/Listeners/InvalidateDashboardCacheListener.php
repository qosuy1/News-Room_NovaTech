<?php

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Log;

class InvalidateDashboardCacheListener
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function handle(ArticlePublished $event): void
    {
        $this->dashboardService->invalidateDashboard();
        $this->dashboardService->invalidateTags();

        Log::info("InvalidateDashboardCacheListener: cache cleared after article [{$event->article->id}] published.");
    }
}
