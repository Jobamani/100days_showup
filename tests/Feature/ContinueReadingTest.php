<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContinueReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_a_post_sets_the_last_post_cookie(): void
    {
        $post = Post::factory()->create(['published' => true]);

        $this->get(route('posts.show', $post->id))
            ->assertOk()
            ->assertCookie('last_post', (string) $post->id);
    }

    public function test_dashboard_links_to_the_last_viewed_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['published' => true]);

        $response = $this->actingAs($user)
            ->withCookie('last_post', (string) $post->id)
            ->get(route('dashboard'));

        dump($response->viewData('lastPost')); // TEMPORARY: delete after reading the result

        $response->assertOk()
            ->assertSee(route('posts.show', $post->id));
    }

    public function test_dashboard_has_no_link_without_the_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('lastPost'));
    }

    public function test_dashboard_ignores_a_cookie_pointing_at_an_unpublished_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['published' => false]);

        $response = $this->actingAs($user)
            ->withCookie('last_post', (string) $post->id)
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('lastPost'));
    }

    public function test_dashboard_ignores_a_cookie_pointing_at_a_missing_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withCookie('last_post', '99999')
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('lastPost'));
    }
}