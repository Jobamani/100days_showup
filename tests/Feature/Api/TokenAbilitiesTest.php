<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenAbilitiesTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user, bool $readOnly = false): string
    {
        return $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret-pass-123',
            'read_only' => $readOnly,
        ])->assertOk()->json('token');
    }

    public function test_normal_login_token_can_write(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-123']);

        $this->withToken($this->tokenFor($user))
            ->postJson('/api/v1/posts', ['title' => 'full access post', 'body' => 'Long enough body text.'])
            ->assertCreated();
    }

    public function test_read_only_token_cannot_write(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-123']);

        $this->withToken($this->tokenFor($user, readOnly: true))
            ->postJson('/api/v1/posts', ['title' => 'blocked post', 'body' => 'Long enough body text.'])
            ->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_read_only_token_can_still_read_the_profile(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-123']);

        $this->withToken($this->tokenFor($user, readOnly: true))
            ->getJson('/api/v1/user')
            ->assertOk();
    }
}