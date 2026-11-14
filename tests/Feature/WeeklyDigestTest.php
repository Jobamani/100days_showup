<?php

namespace Tests\Feature;

use App\Mail\WeeklyDigest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(array $attributes = []): Post
    {
        return Post::factory()->create(array_merge(['published' => true], $attributes));
    }

    public function test_it_queues_a_digest_for_each_active_user(): void
    {
        Mail::fake();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->publishedPost(['user_id' => $a->id]);

        $this->artisan('digest:send-weekly')->assertExitCode(0);

        Mail::assertQueued(WeeklyDigest::class, 2);
        Mail::assertQueued(WeeklyDigest::class, fn ($mail) => $mail->hasTo($a->email));
        Mail::assertQueued(WeeklyDigest::class, fn ($mail) => $mail->hasTo($b->email));
    }

    public function test_banned_users_do_not_get_a_digest(): void
    {
        Mail::fake();
        $banned = User::factory()->banned()->create();
        $this->publishedPost();

        $this->artisan('digest:send-weekly')->assertExitCode(0);

        Mail::assertNotQueued(WeeklyDigest::class, fn ($mail) => $mail->hasTo($banned->email));
    }

    public function test_nothing_is_sent_when_there_are_no_posts_this_week(): void
    {
        Mail::fake();
        User::factory()->create();
        $this->publishedPost(['created_at' => now()->subDays(10)]);
        $this->publishedPost(['published' => false]);

        $this->artisan('digest:send-weekly')
            ->expectsOutput('No posts this week; nothing sent.')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_digest_only_includes_published_posts_from_the_last_7_days(): void
    {
        Mail::fake();
        User::factory()->create();
        $fresh = $this->publishedPost();
        $old = $this->publishedPost(['created_at' => now()->subDays(10)]);
        $draft = $this->publishedPost(['published' => false]);

        $this->artisan('digest:send-weekly');

        Mail::assertQueued(WeeklyDigest::class, function ($mail) use ($fresh, $old, $draft) {
            return $mail->posts->contains($fresh)
                && ! $mail->posts->contains($old)
                && ! $mail->posts->contains($draft);
        });
    }

    public function test_digest_has_at_most_five_posts_most_commented_first(): void
    {
        Mail::fake();
        User::factory()->create();
        foreach (range(1, 7) as $count) {
            $this->publishedPost(['comments_count' => $count]);
        }

        $this->artisan('digest:send-weekly');

        Mail::assertQueued(WeeklyDigest::class, function ($mail) {
            return $mail->posts->pluck('comments_count')->all() === [7, 6, 5, 4, 3];
        });
    }

    public function test_mail_has_subject_title_and_link(): void
    {
        $user = User::factory()->create(['name' => 'Priya Sharma']);
        $post = $this->publishedPost(['title' => 'Laravel Queues Explained']);

        $mailable = new WeeklyDigest($user, collect([$post]));

        $mailable->assertHasSubject('Your weekly digest');
        $mailable->assertSeeInHtml('Priya Sharma');
        $mailable->assertSeeInHtml('Laravel Queues Explained');
        $mailable->assertSeeInHtml(url("/posts/{$post->id}"));
        $mailable->assertSeeInText('Laravel Queues Explained');
    }

    public function test_the_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('digest:send-weekly');
    }
}