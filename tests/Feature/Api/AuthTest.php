<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receives_a_token(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-123']);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret-pass-123',
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name']])
            ->assertJsonMissingPath('user.email');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_wrong_password_is_rejected_and_no_token_is_created(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-123']);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unknown_email_gets_the_same_error_as_a_wrong_password(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->banned()->create(['password' => 'secret-pass-123']);

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret-pass-123',
        ])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_endpoint_requires_a_token(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_user_endpoint_works_with_a_real_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_acting_as_skips_the_token_for_fast_tests(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/user')->assertOk();
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards(); // clear the guard's cached user
        $this->withToken($token)->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_banned_users_existing_token_is_rejected(): void
    {
        $user = User::factory()->banned()->create();
        $token = $user->createToken('old')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/user')->assertForbidden();
    }
}