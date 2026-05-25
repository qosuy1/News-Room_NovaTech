<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendArticlePublishedNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Article $article)
    {
        $this->queue = 'notifications';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->article->fresh();

        if ($this->article->status !== 'published' || ! $this->article) {
            Log::warning("Article with ID {$this->article->id} is not published or does not exist. Notification will not be sent.");

            return;
        }

        User::readers()->select(['id', 'name', 'email'])->chunk(100, function ($readers) {
            foreach ($readers as $reader) {
                $reader->notify(new ArticlePublishedNotification($this->article));
            }
        });

    }
}
