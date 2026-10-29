<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PostValidationTest extends TestCase
{
    use RefreshDatabase;

    public static function invalidPosts(): array
    {
        $body = 'A body that is long enough.';

        return [
            'missing title' => [['title' => '',               'body' => $body], 'title'],
            'title too short' => [['title' => 'ab',           'body' => $body], 'title'],
            'title too long'  => [['title' => str_repeat('a', 256), 'body' => $body], 'title'],
            'missing body'  => [['title' => 'Valid title',    'body' => ''],    'body'],
            'body too short' => [['title' => 'Valid title',   'body' => 'short'], 'body'],
        ];
    }

    #[DataProvider('invalidPosts')]
    public function test_invalid_post_is_rejected(array $data, string $field): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('posts.store'), $data)
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_non_image_file_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Valid title',
            'body'  => 'A body that is long enough.',
            'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_oversized_image_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Valid title',
            'body'  => 'A body that is long enough.',
            'image' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseCount('posts', 0);
    }
}