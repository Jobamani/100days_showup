<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public static function adminChecks(): array
    {
        return [
            'admin is admin'      => [Role::Admin,  true],
            'editor is not admin' => [Role::Editor, false],
            'user is not admin'   => [Role::User,   false],
        ];
    }

    #[DataProvider('adminChecks')]
    public function test_is_admin(Role $role, bool $expected): void
    {
        $user = User::factory()->make(['role' => $role]);

        $this->assertSame($expected, $user->isAdmin());
    }

    public function test_has_role_matches_any_of_the_given_roles(): void
    {
        $editor = User::factory()->make(['role' => Role::Editor]);

        $this->assertTrue($editor->hasRole(Role::Admin, Role::Editor));
        $this->assertFalse($editor->hasRole(Role::Admin));
    }

    public function test_role_is_cast_to_an_enum(): void
    {
        $user = User::factory()->make(['role' => 'editor']);

        $this->assertInstanceOf(Role::class, $user->role);
    }

    public function test_is_banned(): void
    {
        $this->assertFalse(User::factory()->make(['banned_at' => null])->isBanned());
        $this->assertTrue(User::factory()->banned()->make()->isBanned());
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = new User(['name' => 'Sneaky', 'role' => Role::Admin]);

        $this->assertNotSame(Role::Admin, $user->role);
    }
}