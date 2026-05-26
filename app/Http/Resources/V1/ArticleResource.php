<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'articles' => [
                'id' => $this->id,
                'title' => $this->title,
                'content' => $this->content,
                'status' => $this->status,
                'published_at' => optional($this->published_at)?->toDateTimeString(),
                'comments_count' => $this->whenCounted('comments'),
            ],
            'writer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'comments' => $this->whenLoaded('comments', fn () => $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => $comment->relationLoaded('user') ? [
                    'id' => $comment->user?->id,
                    'name' => $comment->user?->name,
                ] : null,
                'created_at' => optional($comment->created_at)?->toDateTimeString(),
            ])->values()),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'file_path' => $attachment->file_path,
                'file_type' => $attachment->file_type,
            ])->values()),
        ];
    }
}
