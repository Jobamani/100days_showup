<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts(): void
    {
        Post::factory()->count(3)->create(['published' => true]);
        Post::factory()->create(['published' => false]);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_is_paginated_ten_per_page(): void
    {
        Post::factory()->count(12)->create(['published' => true]);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_second_page_returns_the_remaining_posts(): void
    {
        Post::factory()->count(12)->create(['published' => true]);

        $this->getJson('/api/v1/posts?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_a_published_post(): void
    {
        $post = Post::factory()->create(['published' => true]);

        $this->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.title', $post->title);
    }

    public function test_show_returns_404_for_an_unpublished_post(): void
    {
        $post = Post::factory()->create(['published' => false]);

        $this->getJson("/api/v1/posts/{$post->id}")->assertNotFound();
    }

    public function test_show_returns_404_for_a_missing_post(): void
    {
        $this->getJson('/api/v1/posts/99999')->assertNotFound();
    }


    public function test_show_returns_clean_shape_without_internal_columns(): void
    {
        $post = Post::factory()->create(['published' => true, 'featured' => false]);

        $this->getJson("/api/v1/posts/{$post->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'title', 'slug', 'excerpt', 'body', 'image_url', 'featured',
                'author' => ['id', 'name'],
                'created_at',
            ]])
            ->assertJsonPath('data.featured', false)
            ->assertJsonMissingPath('data.user_id')
            ->assertJsonMissingPath('data.updated_at')
            ->assertJsonMissingPath('data.published');
    }

    public function test_index_hides_body_and_never_exposes_author_email(): void
    {
        $user = User::factory()->create(['email' => 'secret@example.com']);
        Post::factory()->create(['user_id' => $user->id, 'published' => true]);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonMissingPath('data.0.body')
            ->assertJsonMissingPath('data.0.author.email')
            ->assertJsonMissingPath('data.0.author.password');
    }

    public function test_pagination_has_clean_top_level_links(): void
    {
        Post::factory()->count(12)->create(['published' => true]);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['total'],
            ]);
    }
}
