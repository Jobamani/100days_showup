<?php

namespace App\Console\Commands;

use App\Mail\WeeklyDigest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigest extends Command
{
    protected $signature = 'digest:send-weekly';

    protected $description = 'Queue the weekly digest email for all active users';

    public function handle(): int
    {
        $posts = Post::published()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('comments_count')
            ->limit(5)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No posts this week; nothing sent.');

            return self::SUCCESS;
        }

        $queued = 0;

        User::query()->whereNull('banned_at')->each(function (User $user) use ($posts, &$queued) {
            Mail::to($user)->queue(new WeeklyDigest($user, $posts));
            $queued++;
        });

        $this->info("Queued {$queued} digests.");

        return self::SUCCESS;
    }
}