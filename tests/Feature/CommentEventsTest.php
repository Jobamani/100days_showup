<?php

namespace Tests\Feature;

use App\Events\CommentCreated;
use App\Listeners\LogCommentActivity;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommentEventsTest extends TestCase
{
    use RefreshDatabase;

    private function makeComment(Post $post): Comment
    {
        return Comment::factory()->create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);
    }

    public function test_creating_a_comment_dispatches_comment_created(): void
    {
        Event::fake([CommentCreated::class]);

        $comment = $this->makeComment(Post::factory()->create());

        Event::assertDispatched(CommentCreated::class, fn (CommentCreated $event) => $event->comment->is($comment));
    }

    public function test_comment_created_increments_the_posts_comment_count(): void
    {
        $post = Post::factory()->create();

        $this->makeComment($post);
        $this->makeComment($post);

        $this->assertSame(2, $post->fresh()->comments_count);
    }

    public function test_a_comment_does_not_change_another_posts_count(): void
    {
        $post = Post::factory()->create();
        $other = Post::factory()->create();

        $this->makeComment($post);

        $this->assertSame(0, $other->fresh()->comments_count);
    }

    public function test_logging_listener_is_queued(): void
    {
        Queue::fake();

        $this->makeComment(Post::factory()->create());

        Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === LogCommentActivity::class);
    }

    public function test_logging_listener_writes_a_log_entry(): void
    {
        $comment = $this->makeComment(Post::factory()->create());
        Log::spy();

        (new LogCommentActivity)->handle(new CommentCreated($comment));

        Log::shouldHaveReceived('info')->with('comment.created', \Mockery::type('array'))->once();
    }
}