<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_blocked_after_five_attempts(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-123']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        // even the CORRECT password is refused now
        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret-pass-123',
        ])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_api_responses_include_rate_limit_headers(): void
    {
        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '60');
    }

    public function test_api_is_blocked_after_sixty_requests_per_minute(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/posts')->assertOk();
        }

        $this->getJson('/api/v1/posts')->assertStatus(429);
    }
}