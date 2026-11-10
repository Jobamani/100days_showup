<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\PostSummarizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateSummary implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30; // longer than the 15s HTTP timeout inside the service

    public function __construct(public int $postId)
    {
    }

    /** Seconds to wait before attempt 2, then 3. */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(PostSummarizer $summarizer): void
    {
        $post = Post::find($this->postId);

        if (! $post || $post->summary !== null) {
            return;
        }

        $post->summary = $summarizer->summarize($post);
        $post->save();
    }
}