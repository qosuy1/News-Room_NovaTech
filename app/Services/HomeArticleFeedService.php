<?php

namespace App\Services;

use App\Enums\CacheKeys;
use App\Interfaces\ArticleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class HomeArticleFeedService
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {}

    public function getV1Articles(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = $this->pageCacheKey(CacheKeys::HOME_V1_ARTICLES, $page, $perPage);

        return Cache::remember($cacheKey, CacheKeys::HOME_V1_ARTICLES->ttl(), function () use ($perPage) {
            return $this->articleRepository->getAllPublishedWithRelations($perPage, ['user']);
        });
    }

    public function getV2Articles(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $cacheKey = $this->pageCacheKey(CacheKeys::HOME_V2_ARTICLES, $page, $perPage);

        return Cache::remember($cacheKey, CacheKeys::HOME_V2_ARTICLES->ttl(), function () use ($perPage) {
            return $this->articleRepository->getAllPublishedWithRelations($perPage, ['user', 'tags']);
        });
    }

    public function invalidateAll(): void
    {
        Cache::forget($this->pageCacheKey(CacheKeys::HOME_V1_ARTICLES));
        Cache::forget($this->pageCacheKey(CacheKeys::HOME_V2_ARTICLES));
    }

    private function pageCacheKey(CacheKeys $baseKey, int $page = 1, int $perPage = 15): string
    {
        return "{$baseKey->value}:page:{$page}:per_page:{$perPage}";
    }
}
