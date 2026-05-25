<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('articles:report {--days=7 : Number of past days to include in the report}')]
#[Description('
    This command is responsible for generating a report of published articles in the last days.')]
class ArticlesReportCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('The days must be at least 1.');

            return self::FAILURE;
        }

        $from = now()->subDays($days);
        $to = now();

        $this->info("Generating a published articles report for the last {$days} days.");
        Log::channel('reports')->info('*************************************************');
        Log::channel('reports')->info('Generating weekly published articles report.');
        Log::channel('reports')->info('From: '.$from->toDateTimeString());
        Log::channel('reports')->info('To: '.$to->toDateTimeString());
        Log::channel('reports')->info('*************************************************');

        try {
            $writers = User::writers()->withCount([
                'articles' => function ($query) use ($from, $to) {
                    $query->published()->whereBetween('published_at', [$from, $to]);
                },
            ])->get();

        } catch (\Throwable $e) {
            $this->error('Unable to generate articles report: '.$e->getMessage());
            Log::channel('reports')->error('Unable to generate articles report', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }

        if ($writers->isEmpty()) {
            $this->warn('No writers found.');
            Log::channel('reports')->info('No writers found.');

            return self::SUCCESS;
        }

        $writers->each(function ($writer) {
            // print on the terminal
            $this->info("Writer_id: {$writer->id}, Writer: {$writer->name}, Published Articles: {$writer->articles_count}");

            // store in log file
            Log::channel('reports')
                ->info("\nWriter_id: {$writer->id} \n
                            Writer: {$writer->name} \n
                            Published Articles: {$writer->articles_count}\n\n");
            Log::channel('reports')->info('________________________________________________');
        });

        return self::SUCCESS;
    }
}
