<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_guest_can_view_a_post(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', $post->id))
            ->assertOk()
            ->assertSee($post->title);
    }

    public function test_missing_post_returns_404(): void
    {
        $this->get('/posts/9999')->assertNotFound();
    }

    public function test_guest_cannot_open_create_page(): void
    {
        $this->get(route('posts.create'))->assertRedirect(route('login'));
    }

   public function test_logged_in_user_can_create_a_post(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'My first tested post',
            'body'  => 'This body is longer than ten characters.',
            'image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'title'   => 'My First Tested Post',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_edit_someone_elses_post(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('posts.edit', $post->id))
            ->assertForbidden();
    }

    public function test_banned_user_is_logged_out(): void
    {
        $user = User::factory()->create(['banned_at' => now()]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}