<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class PruneStaleTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:prune-stale
                            {--days=30 : Delete tokens unused for this many days }
                            {--dry-run : Report how many would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete API tokens that have not been used recently';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('--days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $query = PersonalAccessToken::query()->where(function ($q) use ($cutoff) {
            $q->where('last_used_at', '<', $cutoff)
                ->orWhere(function ($q) use ($cutoff) {
                    $q->whereNull('last_used_at')->where('created_at', '<', $cutoff);
                });
        });

        $count = $query->count();

        if ($this->option('dry-run')) {
            $this->info("Would prune {$count} stale tokens.");

            return self::SUCCESS;
        }

        $query->delete();
        $this->info("Pruned {$count} stale tokens.");

        return self::SUCCESS;
    }
}
