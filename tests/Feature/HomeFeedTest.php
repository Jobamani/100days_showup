<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Str;

class HomeFeedTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(array $attributes = []): Post
    {
        return Post::factory()->create(array_merge(['published' => true], $attributes));
    }

   private function tagged(Post $post, string ...$names): Post
    {
        foreach ($names as $name) {
            $tag = Tag::where('name', $name)->first()
                ?? Tag::forceCreate(['name' => $name, 'slug' => Str::slug($name)]);

            $post->tags()->attach($tag);
        }

        return $post;
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

   public function test_guest_sees_published_posts_on_the_home_page(): void
    {
        $post = $this->publishedPost(['title' => 'Hello feed']);

        $this->get('/')->assertOk()->assertSee($post->fresh()->title);
    }

    public function test_drafts_are_not_listed(): void
    {
        $visible = $this->publishedPost(['title' => 'Visible post']);
        $draft = Post::factory()->create(['published' => false, 'title' => 'Secret draft']);

        $this->get('/')
            ->assertSee($visible->fresh()->title)
            ->assertDontSee($draft->fresh()->title);
    }

    public function test_card_shows_author_tags_and_comment_count(): void
    {
        $author = User::factory()->create(['name' => 'Ada Lovelace']);
        $post = $this->publishedPost(['user_id' => $author->id, 'comments_count' => 3]);
        $this->tagged($post, 'Laravel');

        $this->get('/')
            ->assertSee('Ada Lovelace')
            ->assertSee('Laravel')
            ->assertSee('3 comments');
    }

    public function test_feed_is_paginated_ten_per_page(): void
    {
        Post::factory()->count(12)->create(['published' => true]);

        $this->get('/')->assertViewHas('posts', fn ($p) => $p->count() === 10 && $p->total() === 12);
        $this->get('/?page=2')->assertViewHas('posts', fn ($p) => $p->count() === 2);
    }

    public function test_query_count_does_not_grow_with_the_number_of_posts(): void
    {
        foreach (range(1, 3) as $i) {
            $this->tagged($this->publishedPost(), "a-$i", "b-$i");
        }
        $small = $this->queryCount('/');

        foreach (range(4, 10) as $i) {
            $this->tagged($this->publishedPost(), "a-$i", "b-$i");
        }
        $large = $this->queryCount('/');

        $this->assertSame($small, $large, "Feed ran $small queries for 3 posts but $large for 10 (N+1)");
        $this->assertLessThanOrEqual(6, $large);
    }
}