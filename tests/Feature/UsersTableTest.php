<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsersTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_no_legacy_is_admin_column(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
    }
}