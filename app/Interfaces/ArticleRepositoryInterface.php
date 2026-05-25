<?php

namespace App\Interfaces;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    public function getAllPublishedWithRelations(int $perPage = 15): LengthAwarePaginator;

    public function getAllFiltered(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getAllForDashboard(string $role, int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Article;

    public function find(string|int $id): Article;

    public function loadRelations(Article $article): Article;

    public function update(Article $article, array $data): Article;

    public function updateStatus(int $id, string $status): Article;

    public function delete(Article $article): bool;
}
