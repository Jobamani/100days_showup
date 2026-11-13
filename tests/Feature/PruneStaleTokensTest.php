<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class PruneStaleTokensTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user, ?int $lastUsedDaysAgo, int $createdDaysAgo = 1): PersonalAccessToken
    {
        $token = $user->createToken('test')->accessToken;

        $token->forceFill([
            'last_used_at' => $lastUsedDaysAgo === null ? null : now()->subDays($lastUsedDaysAgo),
            'created_at' => now()->subDays($createdDaysAgo),
        ])->save();

        return $token;
    }

    public function test_it_deletes_tokens_not_used_for_more_than_30_days(): void
    {
        $token = $this->tokenFor(User::factory()->create(), lastUsedDaysAgo: 40, createdDaysAgo: 90);

        $this->artisan('tokens:prune-stale')->assertExitCode(0);

        $this->assertModelMissing($token);
    }

    public function test_it_keeps_recently_used_tokens(): void
    {
        $token = $this->tokenFor(User::factory()->create(), lastUsedDaysAgo: 5, createdDaysAgo: 90);

        $this->artisan('tokens:prune-stale')->assertExitCode(0);

        $this->assertModelExists($token);
    }

    public function test_it_deletes_old_tokens_that_were_never_used(): void
    {
        $token = $this->tokenFor(User::factory()->create(), lastUsedDaysAgo: null, createdDaysAgo: 40);

        $this->artisan('tokens:prune-stale')->assertExitCode(0);

        $this->assertModelMissing($token);
    }

    public function test_it_keeps_new_tokens_that_were_never_used(): void
    {
        $token = $this->tokenFor(User::factory()->create(), lastUsedDaysAgo: null, createdDaysAgo: 2);

        $this->artisan('tokens:prune-stale')->assertExitCode(0);

        $this->assertModelExists($token);
    }

    public function test_days_option_changes_the_cutoff(): void
    {
        $token = $this->tokenFor(User::factory()->create(), lastUsedDaysAgo: 10, createdDaysAgo: 20);

        $this->artisan('tokens:prune-stale', ['--days' => 7])->assertExitCode(0);

        $this->assertModelMissing($token);
    }

    public function test_dry_run_reports_but_deletes_nothing(): void
    {
        $token = $this->tokenFor(User::factory()->create(), lastUsedDaysAgo: 40, createdDaysAgo: 90);

        $this->artisan('tokens:prune-stale', ['--dry-run' => true])
            ->expectsOutput('Would prune 1 stale tokens.')
            ->assertExitCode(0);

        $this->assertModelExists($token);
    }

    public function test_it_reports_how_many_tokens_were_pruned(): void
    {
        $user = User::factory()->create();
        $this->tokenFor($user, lastUsedDaysAgo: 40, createdDaysAgo: 90);
        $this->tokenFor($user, lastUsedDaysAgo: null, createdDaysAgo: 60);
        $this->tokenFor($user, lastUsedDaysAgo: 1, createdDaysAgo: 60);

        $this->artisan('tokens:prune-stale')
            ->expectsOutput('Pruned 2 stale tokens.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_it_rejects_a_days_value_below_one(): void
    {
        $this->artisan('tokens:prune-stale', ['--days' => 0])->assertExitCode(1);
    }

    public function test_the_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('tokens:prune-stale');
    }
}