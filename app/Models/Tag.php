<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    public function articles()
    {
        return $this->morphedByMany(Article::class, 'taggable', 'taggables');
    }

    public function slug(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => str()->slug($value)
        );
    }
}
