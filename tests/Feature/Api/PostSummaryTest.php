<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.llm.key' => 'test-key',
            'services.llm.base_url' => 'https://llm.test/v1',
        ]);
        Http::preventStrayRequests();
    }

    private function fakeLlm(string $text = 'A short summary.'): void
    {
        Http::fake([
            'llm.test/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => $text]]],
            ], 200),
        ]);
    }

    public function test_owner_can_generate_a_summary(): void
    {
        $this->fakeLlm();
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'body' => 'Distinctive body text for the summary test.',
        ]);
        Sanctum::actingAs($user, ['posts:write']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.summary', 'A short summary.');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'summary' => 'A short summary.']);

        Http::assertSent(fn (Request $request) =>
            $request->hasHeader('Authorization', 'Bearer test-key')
            && str_contains($request['messages'][1]['content'], 'Distinctive body text')
        );
    }

    public function test_saved_summary_is_reused_without_calling_the_api_again(): void
    {
        $this->fakeLlm();
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['posts:write']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertOk();
        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertOk();

        Http::assertSentCount(1);
    }

    public function test_guest_cannot_generate_a_summary(): void
    {
        Http::fake();
        $post = Post::factory()->create();

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_other_user_cannot_generate_a_summary(): void
    {
        Http::fake();
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['posts:write']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_read_only_token_cannot_generate_a_summary(): void
    {
        Http::fake();
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['posts:read']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_llm_failure_returns_502_and_saves_nothing(): void
    {
        Http::fake(['llm.test/*' => Http::response(['error' => 'boom'], 500)]);
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['posts:write']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")
            ->assertStatus(502)
            ->assertJsonMissingPath('trace');

        $this->assertNull($post->fresh()->summary);
    }

    public function test_editing_the_body_clears_the_saved_summary(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'summary' => 'Old summary.']);
        Sanctum::actingAs($user, ['posts:write']);

        $this->putJson("/api/v1/posts/{$post->id}", [
            'title' => 'Some valid title',
            'body' => 'A completely new body that is long enough.',
        ])->assertOk();

        $this->assertNull($post->fresh()->summary);
    }
}