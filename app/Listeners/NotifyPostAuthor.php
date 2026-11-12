<?php

namespace App\Listeners;

use App\Models\Post;
use App\Notifications\NewCommentOnYourPost;
use App\Events\CommentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPostAuthor
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {        
    }

    /**
     * Handle the event.
     */
    public function handle(CommentCreated $event): void
    {
        
        $comment = $event->comment;
        $post = $comment->commentable;

        if (! $post instanceof Post) {
            return;
        }

        $author = $post->user;

        if (! $author || $author->is($comment->user) || $author->isBanned()) {
            return;
        }

        $author->notify(new NewCommentOnYourPost($comment));
    }
}
