<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentOnYourPost extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Comment $comment)
    {        
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $post = $this->comment->commentable;

        return (new MailMessage)
            ->subject('New comment on your post')
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->comment->user->name} commented on your post \"{$post->title}\".")
            ->line(Str::limit($this->comment->body, 120))
            ->action('View the post', url("/posts/{$post->id}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $post = $this->comment->commentable;
        return [
            'comment_id' => $this->comment->id,
            'post_id' => $post->id,
            'post_title' => $post->title,
            'commenter_name' => $this->comment->user->name,
        ];
    }
}
