<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PostIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_has_a_composite_index_for_the_feed(): void
    {
        $this->assertTrue(
            Schema::hasIndex('posts', ['published', 'created_at']),
            'The feed query (published = 1 order by created_at desc) needs a composite index.'
        );
    }
}