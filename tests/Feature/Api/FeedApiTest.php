<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeedApiTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(array $attributes = []): Post
    {
        return Post::factory()->create(array_merge(['published' => true], $attributes));
    }

    private function queryCount(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_feed_returns_ten_posts_and_a_next_cursor(): void
    {
        Post::factory()->count(12)->create(['published' => true]);

        $response = $this->getJson('/api/v1/feed')->assertOk();

        $this->assertCount(10, $response->json('data'));
        $this->assertNotNull($response->json('meta.next_cursor'));
    }

    public function test_following_the_cursor_returns_the_rest(): void
    {
        Post::factory()->count(12)->create(['published' => true]);
        $first = $this->getJson('/api/v1/feed')->assertOk();

        $second = $this->getJson('/api/v1/feed?cursor='.$first->json('meta.next_cursor'))->assertOk();

        $this->assertCount(2, $second->json('data'));
        $this->assertNull($second->json('meta.next_cursor'));
    }

    public function test_a_new_post_between_pages_does_not_repeat_rows(): void
    {
        Post::factory()->count(12)->create(['published' => true]);
        $first = $this->getJson('/api/v1/feed')->assertOk();
        $firstIds = collect($first->json('data'))->pluck('id');

        $this->publishedPost(); // a new post arrives while the client is paging

        $second = $this->getJson('/api/v1/feed?cursor='.$first->json('meta.next_cursor'))->assertOk();
        $secondIds = collect($second->json('data'))->pluck('id');

        $this->assertCount(2, $secondIds);
        $this->assertEmpty($firstIds->intersect($secondIds)->all());
    }

    public function test_feed_excludes_drafts(): void
    {
        $this->publishedPost();
        Post::factory()->create(['published' => false]);

        $this->assertCount(1, $this->getJson('/api/v1/feed')->assertOk()->json('data'));
    }

    public function test_feed_query_count_does_not_grow_with_posts(): void
    {
        Post::factory()->count(3)->create(['published' => true]);
        $small = $this->queryCount('/api/v1/feed');

        Post::factory()->count(7)->create(['published' => true]);
        $large = $this->queryCount('/api/v1/feed');

        $this->assertGreaterThan(0, $small);
        $this->assertSame($small, $large, "Feed ran $small queries for 3 posts but $large for 10 (N+1)");
    }

    public function test_post_response_has_an_etag(): void
    {
        $post = $this->publishedPost();

        $response = $this->getJson("/api/v1/posts/{$post->id}")->assertOk();

        $this->assertNotEmpty($response->headers->get('ETag'));
    }

    public function test_matching_if_none_match_returns_304_with_no_body(): void
    {
        $post = $this->publishedPost();
        $etag = $this->getJson("/api/v1/posts/{$post->id}")->headers->get('ETag');

        $this->getJson("/api/v1/posts/{$post->id}", ['If-None-Match' => $etag])->assertNoContent(304);
    }

    public function test_a_changed_post_gets_a_new_etag_and_a_full_response(): void
    {
        $post = $this->publishedPost();
        $old = $this->getJson("/api/v1/posts/{$post->id}")->headers->get('ETag');

        $post->update(['title' => 'A completely different title']);

        $response = $this->getJson("/api/v1/posts/{$post->id}", ['If-None-Match' => $old])->assertOk();
        $this->assertNotSame($old, $response->headers->get('ETag'));
    }

    public function test_feed_supports_304_too(): void
    {
        $this->publishedPost();
        $etag = $this->getJson('/api/v1/feed')->headers->get('ETag');

        $this->getJson('/api/v1/feed', ['If-None-Match' => $etag])->assertNoContent(304);
    }

    public function test_feed_items_do_not_include_the_full_body(): void
    {
        $this->publishedPost();

        $item = $this->getJson('/api/v1/feed')->assertOk()->json('data.0');

        $this->assertArrayNotHasKey('body', $item);
    }
}