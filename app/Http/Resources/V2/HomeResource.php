<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reading_time = ceil(str_word_count($this->content) / 200); // Assuming average reading speed of 200 wpm
        $comment_count = $this->whenCounted('comments', fn() => $this->comments_count ?? 0);

        return [
            new \App\Http\Resources\V1\HomeResource($this->resource)->additional([
                'tags' => $this->whenLoaded('tags', fn() => $this->tags->pluck(['id', 'name'])),
                
                'meta' => [
                    'reading_time' => $reading_time,
                    'comment_count' => $comment_count,
                ],
            ]),
        ];
    }
}
