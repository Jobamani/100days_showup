<?php

namespace App\Support;

use Illuminate\Support\Str;

class TagParser
{
    /** @return list<string> */
    public static function parse(?string $input): array
    {
        return collect(explode(',', (string) $input))
            ->map(fn (string $name) => trim(preg_replace('/\s+/', ' ', $name)))
            ->filter()
            ->unique(fn (string $name) => Str::slug($name) ?: $name) // "Laravel" and "laravel" are one tag
            ->values()
            ->all();
    }
}