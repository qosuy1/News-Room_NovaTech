<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\DashboardService;
use App\Services\HomeArticleFeedService;

class ArticleObserver
{
    public function __construct(
        private DashboardService $dashboardService,
        private HomeArticleFeedService $homeArticleFeedService,
    ) {}

    /**
     * Handle the article "created" event.
     */
    public function created(Article $article): void
    {
        $this->dashboardService->invalidateAll();

        if ($article->status === 'published') {
            $this->homeArticleFeedService->invalidateAll();
        }
    }

    /**
     * Handle the article "updated" event.
     */
    public function updated(Article $article): void
    {
        $statusChanged = $article->wasChanged('status');
        $isNowPublished = $article->status === 'published';
        $wasPublished = $article->getOriginal('status') === 'published';

        if ($wasPublished || $isNowPublished || $statusChanged) {
            $this->dashboardService->invalidateAll();
            $this->homeArticleFeedService->invalidateAll();
        }
    }

    /**
     * Handle the article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        $this->dashboardService->invalidateAll();

        if ($article->status === 'published') {
            $this->homeArticleFeedService->invalidateAll();
        }
    }
}
