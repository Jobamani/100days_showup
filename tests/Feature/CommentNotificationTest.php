<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewCommentOnYourPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function commentOn(Post $post, User $commenter): Comment
    {
        return Comment::factory()->create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'user_id' => $commenter->id,
        ]);
    }

    public function test_post_author_is_notified_when_someone_else_comments(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->commentOn($post, $commenter);

        Notification::assertSentTo(
            $author,
            NewCommentOnYourPost::class,
            fn (NewCommentOnYourPost $notification) => $notification->comment->user_id === $commenter->id
        );
    }

    public function test_author_is_not_notified_about_their_own_comment(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->commentOn($post, $author);

        Notification::assertNothingSent();
    }

    public function test_banned_author_is_not_notified(): void
    {
        Notification::fake();
        $author = User::factory()->banned()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->commentOn($post, User::factory()->create());

        Notification::assertNothingSent();
    }

    public function test_notification_goes_to_database_and_mail(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->commentOn($post, User::factory()->create());

        Notification::assertSentTo(
            $author,
            NewCommentOnYourPost::class,
            fn ($notification, array $channels) => $channels === ['database', 'mail']
        );
    }

    public function test_database_notification_stores_the_expected_data(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create(['name' => 'Priya Sharma']);
        $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'My Great Post']);

        $comment = $this->commentOn($post, $commenter);

        $this->assertCount(1, $author->fresh()->notifications);

        $data = $author->fresh()->notifications->first()->data;
        $this->assertSame($comment->id, $data['comment_id']);
        $this->assertSame($post->id, $data['post_id']);
        $this->assertSame('Priya Sharma', $data['commenter_name']);
    }

    public function test_mail_mentions_the_commenter_and_the_post(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create(['name' => 'Priya Sharma']);
        $post = Post::factory()->create(['user_id' => $author->id, 'title' => 'My Great Post']);
        Notification::fake();
        $comment = $this->commentOn($post, $commenter);

        $mail = (new NewCommentOnYourPost($comment))->toMail($author);

        $this->assertSame('New comment on your post', $mail->subject);
        $text = implode(' ', $mail->introLines);
        $this->assertStringContainsString('Priya Sharma', $text);
        $this->assertStringContainsString('My Great Post', $text);
    }
}