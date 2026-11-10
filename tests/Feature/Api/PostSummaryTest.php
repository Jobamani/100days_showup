<?php

namespace Tests\Feature\Api;

use App\Exceptions\SummaryFailed;
use App\Jobs\GenerateSummary;
use App\Models\Post;
use App\Models\User;
use App\Services\PostSummarizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

    private function runJob(int $postId): void
    {
        (new GenerateSummary($postId))->handle(app(PostSummarizer::class));
    }

    // ---------- endpoint ----------

    public function test_owner_request_dispatches_a_job_and_returns_202(): void
    {
        Queue::fake();
        Http::fake();
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['posts:write']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertStatus(202);

        Queue::assertPushed(GenerateSummary::class, fn (GenerateSummary $job) => $job->postId === $post->id);
        Http::assertNothingSent();
    }

    public function test_existing_summary_is_returned_without_dispatching(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'summary' => 'Already summarized.']);
        Sanctum::actingAs($user, ['posts:write']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.summary', 'Already summarized.');

        Queue::assertNothingPushed();
    }

    public function test_guest_cannot_request_a_summary(): void
    {
        Queue::fake();
        $post = Post::factory()->create();

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    public function test_other_user_cannot_request_a_summary(): void
    {
        Queue::fake();
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['posts:write']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_read_only_token_cannot_request_a_summary(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user, ['posts:read']);

        $this->postJson("/api/v1/posts/{$post->id}/summary")->assertForbidden();

        Queue::assertNothingPushed();
    }

    // ---------- the job ----------

    public function test_job_generates_and_saves_the_summary(): void
    {
        $this->fakeLlm();
        $post = Post::factory()->create(['body' => 'Distinctive body text for the summary test.']);

        $this->runJob($post->id);

        $this->assertSame('A short summary.', $post->fresh()->summary);
        Http::assertSent(fn (Request $request) =>
            $request->hasHeader('Authorization', 'Bearer test-key')
            && str_contains($request['messages'][1]['content'], 'Distinctive body text')
        );
    }

    public function test_job_skips_a_post_that_already_has_a_summary(): void
    {
        Http::fake();
        $post = Post::factory()->create(['summary' => 'Already there.']);

        $this->runJob($post->id);

        Http::assertNothingSent();
        $this->assertSame('Already there.', $post->fresh()->summary);
    }

    public function test_job_ignores_a_post_that_was_deleted(): void
    {
        Http::fake();

        $this->runJob(99999);

        Http::assertNothingSent();
    }

    public function test_job_throws_when_the_llm_fails_so_the_queue_can_retry(): void
    {
        Http::fake(['llm.test/*' => Http::response(['error' => 'boom'], 500)]);
        $post = Post::factory()->create();

        $this->assertThrows(fn () => $this->runJob($post->id), SummaryFailed::class);

        $this->assertNull($post->fresh()->summary);
    }

    public function test_job_is_configured_to_retry_with_backoff(): void
    {
        $job = new GenerateSummary(1);

        $this->assertGreaterThan(1, $job->tries);
        $this->assertNotEmpty($job->backoff());
        $this->assertGreaterThan(15, $job->timeout);
    }

    // ---------- staleness ----------

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