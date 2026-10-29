<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_can_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->post(route('comments.store', $post->id), ['body' => 'Nice post!'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_id'   => $post->id,
            'commentable_type' => Post::class,
            'user_id'          => $user->id,
            'body'             => 'Nice post!',
        ]);
    }

    public function test_guest_cannot_comment(): void
    {
        $post = Post::factory()->create();

        $this->post(route('comments.store', $post->id), ['body' => 'Nice post!'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_too_short_comment_is_rejected(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->post(route('comments.store', $post->id), ['body' => 'hi'])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }
}