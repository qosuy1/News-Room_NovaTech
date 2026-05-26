<?php

namespace App\Repository;

use App\Interfaces\ArticleRepositoryInterface;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentArticleRepository implements ArticleRepositoryInterface
{
    private const RELATIONS = ['user', 'tags', 'attachments', 'comments.user'];

    public function getAllPublishedWithRelations(int $perPage = 15, array $relations = self::RELATIONS): LengthAwarePaginator
    {
        return Article::published()
            ->with($relations)
            ->withCount('comments')
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['user', 'tags', 'attachments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', '%' . $search . '%')
                    ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function getAllForDashboard(string $role, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['user:id,name', 'tags:id,name,slug'])
            ->withCount('comments')
            ->latest();

        if ($role === 'writer') {
            $query->where('user_id', $userId);
        }

        if ($role === 'user') {
            $query->whereHas('comments', fn($q) => $q->where('user_id', $userId));
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Article
    {
        return Article::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'] ?? 'draft',
            'user_id' => $data['user_id'] ?? auth()->id(),
            'pubished_at' => $data['status'] == 'published' ? now() : null,
        ]);
    }

    public function find(string|int $id, array $relations = self::RELATIONS): Article
    {
        return Article::with($relations)->findOrFail($id);
    }

    public function loadRelations(Article $article, array $relations = self::RELATIONS): Article
    {
        return $article->load($relations)->loadCount('comments');
    }

    public function update(Article $article, array $data): Article
    {
        $article->update(
            collect($data)->only(['title', 'content', 'status', 'published_at'])->all()
        );

        return $article->fresh();
    }

    public function updateStatus(int $id, string $status): Article
    {
        $article = Article::findOrFail($id);

        $updateData = ['status' => $status];
        if ($status === 'published') {
            $updateData['published_at'] = now();
        }

        $article->update($updateData);

        return $article;
    }

    public function delete(Article $article): bool
    {
        return (bool) $article->delete();
    }
}
