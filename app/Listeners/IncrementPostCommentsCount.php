<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;




class IncrementPostCommentsCount
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CommentCreated $event): void
    {
        $commentable = $event->comment->commentable;

        if ($commentable instanceof Post) {
            $commentable->increment('comments_count');
        }
    }
}
