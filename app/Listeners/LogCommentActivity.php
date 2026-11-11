<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogCommentActivity implements ShouldQueue
{
    public bool $deleteWhenMissingModels = true;

    public function handle(CommentCreated $event): void
    {
        Log::info('comment.created', [
            'comment_id' => $event->comment->id,
            'user_id' => $event->comment->user_id,
        ]);
    }
}