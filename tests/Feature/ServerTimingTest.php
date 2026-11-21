<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServerTimingTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_carry_a_server_timing_header(): void
    {
        config(['performance.expose_timing' => true]);

        $header = $this->getJson('/api/v1/feed')->assertOk()->headers->get('Server-Timing');

        $this->assertMatchesRegularExpression('/db;dur=[\d.]+;desc="\d+ queries"/', $header ?? '');
        $this->assertMatchesRegularExpression('/app;dur=[\d.]+/', $header ?? '');
    }

    public function test_reported_query_count_matches_the_real_query_count(): void
    {
        config(['performance.expose_timing' => true]);
        Post::factory()->count(3)->create(['published' => true]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->getJson('/api/v1/feed')->assertOk();
        $real = count(DB::getQueryLog());
        DB::disableQueryLog();

        preg_match('/desc="(\d+) queries"/', $response->headers->get('Server-Timing') ?? '', $m);

        $this->assertGreaterThan(0, $real);
        $this->assertSame($real, (int) ($m[1] ?? -1));
    }

    public function test_header_is_hidden_when_disabled(): void
    {
        config(['performance.expose_timing' => false]);

        $this->getJson('/api/v1/feed')->assertOk()->assertHeaderMissing('Server-Timing');
    }
}