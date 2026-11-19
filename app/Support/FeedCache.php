<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class FeedCache
{
    private const VERSION_KEY = 'feed:version';
    private const TTL = 300; // seconds

    public static function page(int $page, int $perPage = 10): LengthAwarePaginator
    {
        $version = Cache::get(self::VERSION_KEY, 1);

        return Cache::remember(
            "feed:v{$version}:page:{$page}:per:{$perPage}",
            self::TTL,
            fn () => Post::published()->with(['user', 'tags'])->latest()->paginate($perPage, ['*'], 'page', $page)
        );
    }

    public static function bump(): void
    {
        Cache::add(self::VERSION_KEY, 1);
        Cache::increment(self::VERSION_KEY);
    }
}