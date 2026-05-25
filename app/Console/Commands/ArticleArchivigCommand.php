<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('articles:archive {--days=30 : The number of days after which articles should be archived}')]
#[Description('
    This command is responsible for archiving articles that are older than a certain date.
    It will be scheduled to run every first day of the month at midnight.')]
class ArticleArchivigCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $thresholdDate = now()->subDays($days);

        try {
            $archivedCount = Article::where('created_at', '<', $thresholdDate)
                ->where('status', '!=', 'published')
                ->update(['status' => 'archived']);

        } catch (\Exception $e) {
            $this->error('Error occurred while archiving articles: '.$e->getMessage());

            return 1;
        }

        $this->info("Archived {$archivedCount} articles that were draft more than {$days} days ago.");
    }
}
