<?php

namespace App\Services;

use App\Events\ArticlePublished;
use App\Interfaces\ArticleRepositoryInterface;
use App\Interfaces\AttachmentServiceInterface;
use App\Jobs\SendArticlePublishedNotification;
use App\Models\Article;
use App\Models\Attachment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArticleService
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepo,
        protected AttachmentServiceInterface $attachmentService,
    ) {}

    public function createNewArticle(array $data): Article
    {
        $trackedPhysicalFiles = [];
        $files = $data['attachments'] ?? null;
        unset($data['attachments']);

        return DB::transaction(function () use ($data, $trackedPhysicalFiles) {
            $data['user_id'] = request()->user()->id;
            $article = $this->articleRepo->create($data);

            if (! empty($files)) {
                $attachments = is_array($files)
                    ? $this->attachmentService->uploadMultipleAndAttach($files, $article, 'articles')
                    : collect([$this->attachmentService->uploadAndAttach($files, $article, 'articles')]);

                $trackedPhysicalFiles = $this->mapAttachmentsToTrackedFiles($attachments);
            }

            if (isset($data['tags'])) {
                $article->tags()->sync($data['tags']);
            }
            if ($article->status === 'published') {
                DB::afterCommit(function () use ($article) {
                    // dispatch the notification job to sen for all readers about the new published article
                    SendArticlePublishedNotification::dispatch($article);
                    // dispatch this event after the artcile published
                    ArticlePublished::dispatch($article);
                });
            }
            DB::afterRollBack(function () use ($trackedPhysicalFiles) {
                foreach ($trackedPhysicalFiles as $file) {
                    $this->attachmentService->deletePhysicalFile($file['path'], $file['disk']);
                }
            });

            return $this->articleRepo->loadRelations($article);
        });
    }

    public function showArticle(Article $article): Article
    {
        return $this->articleRepo->loadRelations($article);
    }

    public function updateArticle(Article $article, array $data): Article
    {
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        $payload = collect($data)->only(['title', 'content', 'status'])->all();

        return DB::transaction(function () use ($article, $payload, $tags) {
            $updatedArticle = ! empty($payload)
                ? $this->articleRepo->update($article, $payload)
                : $article;

            if ($tags !== null) {
                $updatedArticle->tags()->sync($tags);
            }

            if ($updatedArticle->wasChanged('status') && $updatedArticle->status === 'published') {
                DB::afterCommit(function () use ($article, $updatedArticle) {
                    // dispatch the notification job to sen for all readers about the new published article
                    SendArticlePublishedNotification::dispatch($updatedArticle);

                    // dispatch this event after the artcile published
                    ArticlePublished::dispatch($article);
                });
            }

            return $this->articleRepo->loadRelations($updatedArticle);
        });
    }

    public function deleteArticle(Article $article): void
    {
        DB::beginTransaction();

        try {
            $article->load('attachments');

            foreach ($article->attachments as $attachment) {
                $this->attachmentService->delete($attachment);
            }

            $this->articleRepo->delete($article);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function publishArticle(Article $article): Article
    {
        if ($article->status === 'published') {
            return $article;
        }

        $article = $this->articleRepo->updateStatus($article->id, 'published');

        // dispatch the notification job to sen for all readers about the new published article
        SendArticlePublishedNotification::dispatch($article);
        // dispatch this event after the artcile published
        ArticlePublished::dispatch($article);

        return $this->articleRepo->loadRelations($article);
    }

    private function mapAttachmentsToTrackedFiles(Collection $attachments): array
    {
        return $attachments
            ->map(fn (Attachment $attachment) => [
                'path' => $attachment->path,
                'disk' => $attachment->disk,
            ])
            ->values()
            ->all();
    }
}
