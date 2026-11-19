<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeedCacheTest extends TestCase
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
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_second_request_is_served_from_cache(): void
    {
        $this->publishedPost();

        $cold = $this->queryCount('/');
        $warm = $this->queryCount('/');

        $this->assertGreaterThan(0, $cold);
        $this->assertSame(0, $warm, "A cached feed should not touch the database but ran $warm queries");
    }

    public function test_new_published_post_appears_immediately(): void
    {
        $first = $this->publishedPost(['title' => 'First post']);
        $this->get('/')->assertSee($first->fresh()->title); // warms the cache

        $second = $this->publishedPost(['title' => 'Second post']);

        $this->get('/')->assertSee($second->fresh()->title);
    }

    public function test_publishing_a_draft_makes_it_appear(): void
    {
        $draft = Post::factory()->create(['published' => false, 'title' => 'Draft post']);
        $this->get('/')->assertDontSee($draft->fresh()->title); // warms the cache without it

        $draft->update(['published' => true]);

        $this->get('/')->assertSee($draft->fresh()->title);
    }

    public function test_deleted_post_disappears(): void
    {
        $post = $this->publishedPost(['title' => 'Doomed post']);
        $title = $post->fresh()->title;
        $this->get('/')->assertSee($title);

        $post->delete();

        $this->get('/')->assertDontSee($title);
    }

    public function test_new_comment_updates_the_count_on_the_feed(): void
    {
        $post = $this->publishedPost();
        $commenter = User::factory()->create();
        $this->get('/')->assertSee('0 comments');

        $this->actingAs($commenter)
            ->post(route('comments.store', $post->id), ['body' => 'This is a perfectly valid comment body.'])
            ->assertSessionHasNoErrors();

        $this->get('/')->assertSee('1 comment');
    }

    public function test_renaming_an_author_updates_the_feed(): void
    {
        $author = User::factory()->create(['name' => 'Old Name']);
        $this->publishedPost(['user_id' => $author->id]);
        $this->get('/')->assertSee('Old Name');

        $author->update(['name' => 'New Name']);

        $this->get('/')->assertSee('New Name')->assertDontSee('Old Name');
    }
}