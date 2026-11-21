<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SlowQueryLogTest extends TestCase
{
    public function test_slow_queries_are_logged_without_their_bindings(): void
    {
        config(['performance.slow_query_ms' => 0]); // everything counts as slow
        Log::spy();

        DB::select('select ? as secret', ['hunter2']);

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return $message === 'slow_query'
                && str_contains($context['sql'], 'select ? as secret')
                && ! str_contains(json_encode($context), 'hunter2');
        })->atLeast()->once();
    }

    public function test_fast_queries_are_not_logged(): void
    {
        config(['performance.slow_query_ms' => 100000]);
        Log::spy();

        DB::select('select 1');

        Log::shouldNotHaveReceived('warning');
    }
}