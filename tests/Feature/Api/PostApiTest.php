<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts(): void
    {
        Post::factory()->count(3)->create(['published' => true]);
        Post::factory()->create(['published' => false]);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_is_paginated_ten_per_page(): void
    {
        Post::factory()->count(12)->create(['published' => true]);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('total', 12)
            ->assertJsonPath('per_page', 10);
    }

    public function test_second_page_returns_the_remaining_posts(): void
    {
        Post::factory()->count(12)->create(['published' => true]);

        $this->getJson('/api/posts?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_show_returns_a_published_post(): void
    {
        $post = Post::factory()->create(['published' => true]);

        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('id', $post->id)
            ->assertJsonPath('title', $post->title);
    }

    public function test_show_returns_404_for_an_unpublished_post(): void
    {
        $post = Post::factory()->create(['published' => false]);

        $this->getJson("/api/posts/{$post->id}")->assertNotFound();
    }

    public function test_show_returns_404_for_a_missing_post(): void
    {
        $this->getJson('/api/posts/99999')->assertNotFound();
    }
}