<?php

namespace App\Http\Resources\V2;

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
        $reading_time = ceil(str_word_count($this->content) / 200); // Assuming average reading speed of 200 wpm
        $comment_count = $this->whenLoaded('comments', fn () => $this->comments_count ?? 0);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'writer_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'published_at' => optional($this->published_at)?->toDateTimeString(),

            'reading_time' => $reading_time,
            'comment_count' => $comment_count,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck(['id', 'name'])),
        ];
    }
}
