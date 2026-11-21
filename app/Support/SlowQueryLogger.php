<?php

namespace App\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class SlowQueryLogger
{
    public static function handle(QueryExecuted $query): void
    {
        if ($query->time < (float) config('performance.slow_query_ms')) {
            return;
        }

        Log::warning('slow_query', [
            'ms'         => round($query->time, 2),
            'sql'        => $query->sql,   // placeholders only. Never log bindings.
            'connection' => $query->connectionName,
            'path'       => app()->runningInConsole() ? 'console' : request()->path(),
        ]);
    }
}