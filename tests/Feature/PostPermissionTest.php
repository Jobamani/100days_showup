<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PostPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_edit_page(): void
    {
        $owner = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('posts.edit', $post->id))->assertOk();
    }

    public function test_owner_can_update_own_post(): void
    {
        $owner = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->put(route('posts.update', $post->id), [
            'title' => 'Updated Title',
            'body'  => 'Updated body that is long enough.',
        ])->assertRedirect(route('posts.show', $post->id));

        $this->assertSame('Updated Title', $post->fresh()->title);
    }

    public function test_stranger_cannot_update_post(): void
    {
        $post     = Post::factory()->create();
        $stranger = User::factory()->create();
        $original = $post->title;

        $this->actingAs($stranger)->put(route('posts.update', $post->id), [
            'title' => 'Hacked Title',
            'body'  => 'Hacked body that is long enough.',
        ])->assertForbidden();

        $this->assertSame($original, $post->fresh()->title);
    }

    public function test_stranger_cannot_delete_post(): void
    {
        $post     = Post::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->delete(route('posts.destroy', $post->id))
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_owner_can_delete_own_post(): void
    {
        $owner = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('posts.destroy', $post->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_admin_can_delete_any_post(): void
    {
        $post  = Post::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('posts.destroy', $post->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_guest_cannot_delete_post(): void
    {
        $post = Post::factory()->create();

        $this->delete(route('posts.destroy', $post->id))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public static function roles(): array
    {
        return [
            'user is refused'   => [Role::User,   false],
            'editor is allowed' => [Role::Editor, true],
            'admin is allowed'  => [Role::Admin,  true],
        ];
    }

    #[DataProvider('roles')]
    public function test_manage_tags_gate_by_role(Role $role, bool $allowed): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }
}