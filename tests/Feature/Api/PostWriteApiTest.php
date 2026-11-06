<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PostWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'my api post',
            'body'  => 'This body is long enough to pass validation.',
        ], $overrides);
    }

    // ---------- create ----------

    public function test_guest_cannot_create_a_post(): void
    {
        $this->postJson('/api/posts', $this->validPayload())->assertUnauthorized();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_authenticated_user_can_create_a_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.title', 'My Api Post')
            ->assertJsonPath('data.author.id', $user->id)
            ->assertJsonPath('data.featured', false)
            ->assertJsonStructure(['data' => ['id', 'title', 'slug', 'body', 'author']]);

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'My Api Post']);        
    }

    public function test_client_cannot_choose_the_author(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', $this->validPayload(['user_id' => $other->id]))
            ->assertCreated();

        $this->assertDatabaseHas('posts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('posts', ['user_id' => $other->id]);
    }

    #[DataProvider('invalidPosts')]
    public function test_invalid_post_is_rejected(array $payload, string $field): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('posts', 0);
    }

    public static function invalidPosts(): array
    {
        return [
            'missing title'   => [['body' => 'long enough body text'], 'title'],
            'title too short' => [['title' => 'ab', 'body' => 'long enough body text'], 'title'],
            'missing body'    => [['title' => 'A good title'], 'body'],
            'body too short'  => [['title' => 'A good title', 'body' => 'short'], 'body'],
        ];
    }

    public function test_banned_user_cannot_create_a_post(): void
    {
        Sanctum::actingAs(User::factory()->banned()->create());

        $this->postJson('/api/posts', $this->validPayload())->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_can_be_created_with_an_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->post(
            '/api/posts',
            $this->validPayload(['image' => UploadedFile::fake()->image('cover.jpg')]),
            ['Accept' => 'application/json']
        )->assertCreated();

        Storage::disk('public')->assertExists(Post::first()->image);
    }

    // ---------- update ----------

    public function test_owner_can_update_their_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->putJson("/api/posts/{$post->id}", [
            'title' => 'updated title here',
            'body'  => 'Updated body that is long enough.',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title Here');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated Title Here']);
    }

    public function test_other_user_cannot_update_a_post(): void
    {
        $post = Post::factory()->create(['title' => 'Original Title']);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/posts/{$post->id}", $this->validPayload())->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Original Title']);
    }

    public function test_admin_can_update_any_post(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson("/api/posts/{$post->id}", $this->validPayload())->assertOk();
    }

    public function test_updating_a_missing_post_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/posts/99999', $this->validPayload())->assertNotFound();
    }

    // ---------- delete ----------

    public function test_guest_cannot_delete_a_post(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson("/api/posts/{$post->id}")->assertUnauthorized();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_owner_can_delete_their_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/posts/{$post->id}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_other_user_cannot_delete_a_post(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/posts/{$post->id}")->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
