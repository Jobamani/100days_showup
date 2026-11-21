<?php

namespace App\Http\Middleware;

use App\Support\QueryStats;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServerTiming
{
    public function __construct(private QueryStats $stats) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->stats->reset();
        $start = microtime(true);

        $response = $next($request);

        if (config('performance.expose_timing')) {
            $response->headers->set('Server-Timing', sprintf(
                'db;dur=%.1f;desc="%d queries", app;dur=%.1f',
                $this->stats->ms,
                $this->stats->count,
                (microtime(true) - $start) * 1000,
            ));
        }

        return $response;
    }
}