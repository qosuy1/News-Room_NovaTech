<?php

namespace App\Enums;

enum CacheKeys: string
{
    case DASHBOARD_ARTICLES = 'dashboard:articles';
    case DASHBOARD_STATS = 'dashboard:stats';
    case HOME_V1_ARTICLES = 'home:v1:articles';
    case HOME_V2_ARTICLES = 'home:v2:articles';

    // Tags group — stored separately from dashboard (requirement #16)
    case TAGS_MOST_USED = 'tags:most_used';
    case TAGS_ALL = 'tags:all';

    //  * How long dashboard stats stay fresh.
    public function ttl(): int
    {
        return match ($this) {
            self::DASHBOARD_STATS => 600,   // 10 minutes
            self::DASHBOARD_ARTICLES => 600, // 10 minutes
            self::HOME_V1_ARTICLES => 600, // 10 minutes
            self::HOME_V2_ARTICLES => 600, // 10 minutes

            self::TAGS_MOST_USED => 3600,  // 1 hour
            self::TAGS_ALL => 1800,  // 30 minutes
        };
    }

    public function lockKey(): string
    {
        return 'lock:'.$this->value;
    }
}
