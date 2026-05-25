<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource['stats'];
        $top_tags = $this->resource['top_tags'];

        return [
            'statistics' => [
                'articles_count' => $data['stats']['articles_count'],
                'comments_count' => $data['stats']['comments_count'],
                'top_writers' => $data['stats']['top_writers'],
            ],

            'top_tags' => $top_tags,
        ];
    }
}
