<?php

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;
use ValueError;

class RoleEnumTest extends TestCase
{
    public function test_roles_have_expected_values(): void
    {
        $this->assertSame('user', Role::User->value);
        $this->assertSame('editor', Role::Editor->value);
        $this->assertSame('admin', Role::Admin->value);
    }

    public function test_from_returns_the_matching_case(): void
    {
        $this->assertSame(Role::Admin, Role::from('admin'));
    }

    public function test_try_from_returns_null_for_unknown_role(): void
    {
        $this->assertNull(Role::tryFrom('superuser'));
    }

    public function test_from_throws_for_unknown_role(): void
    {
        $this->expectException(ValueError::class);

        Role::from('superuser');
    }
}