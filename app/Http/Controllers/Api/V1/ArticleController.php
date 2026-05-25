<?php

namespace App\Http\Controllers\Api\V1;

use App\Helper\V1\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Articles\StoreArticleRequest;
use App\Http\Requests\V1\Articles\UpdateArticleRequest;
use App\Http\Resources\V1\ArticleResource;
use App\Interfaces\ArticleRepositoryInterface;
use App\Models\Article;
use App\Services\ArticleService;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticleService $articleService,
        protected ArticleRepositoryInterface $articleRepository,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = (int) request()->query('per_page', 15);
        $articles = $this->articleRepository->getAllPublishedWithRelations($perPage);

        return ApiResponse::paginated(ArticleResource::collection($articles), 'Articles retrieved successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleRequest $request)
    {
        $article = $this->articleService->createNewArticle($request->validated());

        return ApiResponse::created(new ArticleResource($article), 'Article created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        $article = $this->articleService->showArticle($article);

        return ApiResponse::success($article);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        $article = $this->articleService->updateArticle($article, $request->validated());

        return ApiResponse::success($article, 'Article updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        $this->articleService->deleteArticle($article);

        return ApiResponse::success(null, 'Article deleted successfully.');
    }

    public function publish(Article $article)
    {
        $article = $this->articleService->publishArticle($article);

        return ApiResponse::success($article, 'Article published successfully.');
    }
}
