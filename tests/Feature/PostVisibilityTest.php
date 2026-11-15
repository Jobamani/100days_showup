<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_a_published_post(): void
    {
        $post = Post::factory()->create(['published' => true, 'title' => 'Hello World']);

        $this->get(route('posts.show', $post->id))
            ->assertOk()
            ->assertSee('Hello World');
    }

    public function test_guest_cannot_view_a_draft(): void
    {
        $draft = Post::factory()->create(['published' => false]);

        $this->get(route('posts.show', $draft->id))->assertNotFound();
    }

    public function test_other_users_cannot_view_someone_elses_draft(): void
    {
        $draft = Post::factory()->create(['published' => false]);

        $this->actingAs(User::factory()->create())
            ->get(route('posts.show', $draft->id))
            ->assertNotFound();
    }

    public function test_author_can_view_their_own_draft(): void
    {
        $author = User::factory()->create();
        $draft = Post::factory()->create(['published' => false, 'user_id' => $author->id]);

        $this->actingAs($author)
            ->get(route('posts.show', $draft->id))
            ->assertOk();
    }

    public function test_create_another_link_is_hidden_from_guests(): void
    {
        $post = Post::factory()->create(['published' => true]);

        $this->get(route('posts.show', $post->id))->assertDontSee('Create another');
    }
}