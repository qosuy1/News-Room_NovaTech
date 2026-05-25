<?php

namespace App\Providers;

use App\Interfaces\ArticleRepositoryInterface;
use App\Interfaces\AttachmentServiceInterface;
use App\Repository\EloquentArticleRepository;
use App\Services\AttachmentService;
use App\Services\Notifications\NotificationDispatcherInterface;
use App\Services\Notifications\RoleBasedNotificationDispatcher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, EloquentArticleRepository::class);
        $this->app->bind(AttachmentServiceInterface::class, AttachmentService::class);

        $this->app->bind(NotificationDispatcherInterface::class, RoleBasedNotificationDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // enable monitoring in local enviroment only to see queries
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                Log::info("SQL Query: {$query->sql} | Bindings: ".json_encode($query->bindings)." | Time: {$query->time}ms");
            });
        }

        // for all routes
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // strict for actions like update, delete , store
        RateLimiter::for('strict', function (Request $request) {
            // Tighter limit for write operations (create/update/delete)
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // ArticleObserver handles raw model lifecycle (any save/delete)
        // I Register the observer in the Article Model usig #[ObserverBy()]
        // Article::observe(ArticleObserver::class);
    }
}
