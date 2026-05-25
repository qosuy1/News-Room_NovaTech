<?php

namespace App\Jobs;

use App\Mail\WeeklyReportMail;
use App\Models\Article;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [30, 60, 120, 300, 600];

    public int $timeout = 30;

    private CarbonInterface $from;

    private CarbonInterface $to;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('reports');

        $this->from = now()->subWeek()->startOfDay();
        $this->to = now()->endOfDay();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $articles = $this->getLastWeekPublishedArticles();

        if ($articles->isEmpty()) {
            Log::info('No articles published in the last week. Weekly report will not be sent.');

            return;
        }
        $reportData = $this->fetchReportData($articles);

        $this->sendReportToAdmins($reportData);

    }

    private function sendReportToAdmins(array $reportData): void
    {
        $admins = $this->getAdmins();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new WeeklyReportMail($reportData, $this->from, $this->to));

            Log::info("SendWeeklyReport: sent to [{$admin->email}] — ".count($reportData).' articles covered.', [
                'period_from' => $this->from->toDateString(),
                'period_to' => $this->to->toDateString(),
            ]);
        }
    }

    private function fetchReportData(Collection $articles): array
    {

        return $articles->map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'published_at' => $article->published_at->toDateTimeString(),
                'author_name' => $article->user->name,
                'author_email' => $article->user->email,
            ];
        })->toArray();
    }

    private function getLastWeekPublishedArticles()
    {
        return Article::with(['user:id,name,email'])
            ->published()->whereBetween('published_at', [$this->from, $this->to])
            ->select(['id', 'title', 'published_at', 'user_id'])
            ->latest()->get();
    }

    private function getAdmins()
    {
        return User::admins()->select(['name', 'email'])->get();
    }
}
