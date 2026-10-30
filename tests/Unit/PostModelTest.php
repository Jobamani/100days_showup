<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_mutator_capitalises_each_word(): void
    {
        $post = new Post(['title' => 'hello world from laravel']);

        $this->assertSame('Hello World From Laravel', $post->title);
    }

    public function test_excerpt_shortens_a_long_body(): void
    {
        $post = new Post(['body' => str_repeat('word ', 100)]);

        $this->assertLessThan(strlen($post->body), strlen($post->excerpt));
    }

    public function test_excerpt_keeps_a_short_body_unchanged(): void
    {
        $post = new Post(['body' => 'Short body text.']);

        $this->assertSame('Short body text.', $post->excerpt);
    }

    public function test_published_scope_returns_only_published_posts(): void
    {
        Post::factory()->count(2)->create(['published' => true]);
        Post::factory()->create(['published' => false]);

        $this->assertCount(2, Post::published()->get());
    }

    public function test_by_user_scope_returns_only_that_users_posts(): void
    {
        $alice = User::factory()->create();
        $bob   = User::factory()->create();

        Post::factory()->count(2)->create(['user_id' => $alice->id]);
        Post::factory()->create(['user_id' => $bob->id]);

        $this->assertCount(2, Post::byUser($alice->id)->get());
    }

    public function test_tags_can_be_attached_and_detached(): void
    {
        $post = Post::factory()->create();
        $tag  = Tag::create(['name' => 'PHP', 'slug' => 'php']);

        $post->tags()->attach($tag);
        $this->assertCount(1, $post->fresh()->tags);
        $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $tag->id]);

        $post->tags()->detach($tag);
        $this->assertCount(0, $post->fresh()->tags);
    }

    public function test_comment_belongs_to_its_post_and_author(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'body'    => 'Nice post!',
        ]);

        $this->assertTrue($comment->commentable->is($post));
        $this->assertTrue($comment->user->is($user));
        $this->assertSame(Post::class, $comment->commentable_type);
    }
}