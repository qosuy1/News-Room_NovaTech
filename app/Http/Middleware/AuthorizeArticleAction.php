<?php

namespace App\Http\Middleware;

use App\Helper\V1\ApiResponse;
use App\Models\Article;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeArticleAction
{
    private const ACTIONS_REQUIRING_ARTICLE = ['view', 'update', 'delete', 'publish'];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = $request->user();
        $action = $this->normalizeAction($action);

        if ($action === null) {
            return ApiResponse::error('Invalid authorization action.', 500);
        }

        $article = null;

        if ($this->requiresArticle($action)) {
            $article = $this->resolveArticle($request);

            if ($article === null) {
                return ApiResponse::notFound();
            }
        }

        $denied = match ($action) {
            'view' => $this->authorizeView($user, $article),
            'update' => $this->authorizeOwner($user, $article),
            'delete' => $this->authorizeOwnerOrAdmin($user, $article, 'delete'),
            'publish' => $this->authorizePublish($user, $article),
            default => ApiResponse::error('Invalid authorization action.', 500),
        };

        if ($denied instanceof Response) {
            return $denied;
        }

        return $next($request);
    }

    private function normalizeAction(string $action): ?string
    {
        return match ($action) {
            'edit' => 'update',
            'view', 'update', 'delete', 'publish' => $action,
            default => null,
        };
    }

    private function requiresArticle(string $action): bool
    {
        return in_array($action, self::ACTIONS_REQUIRING_ARTICLE, true);
    }

    private function resolveArticle(Request $request): ?Article
    {
        $article = $request->route('article');

        if ($article instanceof Article) {
            $request->route()?->setParameter('article', $article);

            return $article;
        }

        $article = Article::find($article);

        if ($article instanceof Article) {
            $request->route()?->setParameter('article', $article);
        }

        return $article;
    }

    private function authorizeView(?User $user, Article $article): ?Response
    {
        if ($article->status === 'published') {
            return null;
        }

        if (! $user || ($user->role !== 'admin' && $user->id !== $article->user_id)) {
            return ApiResponse::unauthorized('You are not authorized to view this article.');
        }

        return null;
    }

    private function authorizeOwner(?User $user, Article $article): ?Response
    {
        if (! $user) {
            return ApiResponse::unauthorized('You need to register first.');
        }

        if ($user->role !== 'writer' && $user->id !== $article->user_id) {
            return ApiResponse::forbidden('You are not authorized to perform this action.');
        }

        return null;
    }

    private function authorizeOwnerOrAdmin(?User $user, Article $article, string $operation): ?Response
    {
        if (! $user) {
            return ApiResponse::unauthorized('You need to register first.');
        }

        if ($user->role === 'admin') {
            return null;
        }

        if ($user->role === 'writer' && $user->id === $article->user_id) {
            return null;
        }

        return ApiResponse::forbidden("You are not authorized to {$operation} this article.");
    }

    private function authorizePublish(?User $user, Article $article): ?Response
    {
        if (! $user) {
            return ApiResponse::unauthorized('You need to register first.');
        }

        if ($user->role === 'admin' || $user->id === $article->user_id) {
            return null;
        }

        return ApiResponse::forbidden('You are not authorized to publish this article.');
    }
}
