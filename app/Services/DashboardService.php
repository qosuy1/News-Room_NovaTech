<?php

namespace App\Services;

use App\Enums\CacheKeys;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    private const LOCK_TIMEOUT = 5;

    // Public API

    public function getStats(): array
    {
        return $this->rememberWithLock(
            CacheKeys::DASHBOARD_STATS,
            ['dashboard', 'status'],
            fn () => [
                'article_count' => $this->fetchArticleCount(),
                'comment_count' => $this->fetchCommentCount(),
                'top_writers' => $this->fetchTopWriters(),
                'generated_at' => now()->toISOString(),
            ]
        );
    }

    public function getMostUsedTags(int $limit = 10): array
    {
        return $this->rememberWithLock(
            CacheKeys::TAGS_MOST_USED,
            ['tags', 'most_used'],
            fn () => $this->fetchMostUsedTags($limit)
        );
    }

    // Cache Invalidation

    public function invalidateDashboard(): void
    {
        Cache::tags('dashboard')->flush();
        Log::info('Cache invalidated: dashboard group');
    }

    public function invalidateTags(): void
    {
        Cache::tags('tags')->flush();
        Log::info('Cache invalidated: tags group');
    }

    public function invalidateAll(): void
    {
        $this->invalidateDashboard();
        $this->invalidateTags();
    }

    // Core caching helper

    private function rememberWithLock(CacheKeys $key, array $tags, callable $callback): mixed
    {
        try {
            return Cache::lock($key->lockKey(), self::LOCK_TIMEOUT)
                ->block(self::LOCK_TIMEOUT, function () use ($key, $tags, $callback) {

                    return Cache::tags($tags)->remember($key->value, $key->ttl(), $callback);

                });
        } catch (LockTimeoutException) {
            Log::warning("DashboardService: lock timeout on {$key->name}");

            return $callback();
        }

    }

    // DB Queries

    private function fetchArticleCount(): int
    {
        return Article::published()->count();
    }

    private function fetchCommentCount(): int
    {
        return Comment::count();
    }

    private function fetchTopWriters(int $limit = 5): array
    {
        return User::select('users.id', 'users.name', DB::raw('COUNT(articles.id) as articles_count'))
            ->join('articles', 'articles.user_id', '=', 'users.id')
            ->where('articles.status', 'published')
            ->where('users.role', 'writer')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('articles_count')
            ->limit($limit)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'articles_count' => $user->articles_count,
            ])
            ->toArray();
    }

    private function fetchMostUsedTags(int $limit): array
    {
        return Tag::select('tags.id', 'tags.name', DB::raw('COUNT(taggables.tag_id) as usage_count'))
            ->join('taggables', 'taggables.tag_id', '=', 'tags.id')
            ->groupBy('tags.id', 'tags.name')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get()
            ->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'usage_count' => $tag->usage_count,
            ])
            ->toArray();
    }
}
