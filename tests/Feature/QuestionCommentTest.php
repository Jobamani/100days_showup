<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Comment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use App\Controllers\QuestionCommentController;


class QuestionCommentTest extends TestCase
{
    use RefreshDatabase;

    private function page(Question $question): string
    {
        return route('questions.show', [$question, $question->slug]);
    }

    public function test_guests_cannot_comment(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->for($question)->create();

        $this->post("/questions/{$question->id}/comments", ['comment' => 'Nice one.'])->assertRedirect('/login');
        $this->post("/answers/{$answer->id}/comments", ['comment' => 'Nice one.'])->assertRedirect('/login');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_user_can_comment_on_a_question(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create();

        $this->actingAs($user)
            ->post("/questions/{$question->id}/comments", ['comment' => 'Which Laravel version?'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'question',
            'commentable_id' => $question->id,
            'user_id' => $user->id,
            'body' => 'Which Laravel version?',
        ]);
    }

    public function test_a_user_can_comment_on_an_answer(): void
    {
        $user = User::factory()->create();
        $answer = Answer::factory()->create();

        $this->actingAs($user)
            ->post("/answers/{$answer->id}/comments", ['comment' => 'Thanks, that worked.'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'answer',
            'commentable_id' => $answer->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_the_author_and_target_come_from_the_session_and_route(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $question = Question::factory()->create();

        $this->actingAs($user)->post("/questions/{$question->id}/comments", [
            'comment' => 'Hello there',
            'user_id' => $other->id,
            'commentable_type' => 'App\\Models\\Post',
            'commentable_id' => 999,
        ]);

        $this->assertDatabaseCount('comments', 1);
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => 'question',
            'commentable_id' => $question->id,
        ]);
    }

    public function test_the_comment_is_validated(): void
    {
        $user = User::factory()->create();
        $url = '/questions/'.Question::factory()->create()->id.'/comments';

        $this->actingAs($user)->post($url, ['comment' => ''])->assertSessionHasErrors('comment');
        $this->actingAs($user)->post($url, ['comment' => 'ab'])->assertSessionHasErrors('comment');
        $this->actingAs($user)->post($url, ['comment' => str_repeat('a', 1001)])->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comments_are_escaped(): void
    {
        $question = Question::factory()->create();
        Comment::factory()->for($question, 'commentable')->create(['body' => '<script>alert(1)</script>']);

        $this->get($this->page($question))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_the_page_shows_comments_on_the_question_and_its_answers(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->for($question)->create();
        Comment::factory()->for($question, 'commentable')->create(['body' => 'Question comment text']);
        Comment::factory()->for($answer, 'commentable')->create(['body' => 'Answer comment text']);

        $this->get($this->page($question))
            ->assertOk()
            ->assertSee('Question comment text')
            ->assertSee('Answer comment text');
    }

    public function test_authors_can_delete_their_own_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->for(Question::factory()->create(), 'commentable')
            ->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('comments.destroy', $comment))->assertRedirect();

        $this->assertModelMissing($comment);
    }

    public function test_other_users_cannot_delete_a_comment(): void
    {
        $comment = Comment::factory()->for(Question::factory()->create(), 'commentable')->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('comments.destroy', $comment))->assertForbidden();

        $this->assertModelExists($comment);
    }

    public function test_post_comments_cannot_be_deleted_through_this_route(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]); // factory default: a Post comment

        $this->actingAs($user)->delete(route('comments.destroy', $comment))->assertNotFound();

        $this->assertModelExists($comment);
    }

    public function test_deleting_a_question_deletes_comments_on_it_and_on_its_answers(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->for($question)->create();
        Comment::factory()->for($question, 'commentable')->create();
        Comment::factory()->for($answer, 'commentable')->create();
        $postComment = Comment::factory()->create(); // must survive

        $question->delete();

        $this->assertDatabaseCount('comments', 1);
        $this->assertModelExists($postComment);
    }

    public function test_deleting_an_answer_deletes_its_comments(): void
    {
        $answer = Answer::factory()->create();
        Comment::factory()->for($answer, 'commentable')->create();

        $answer->delete();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_deleting_a_user_deletes_comments_on_their_questions(): void
    {
        $author = User::factory()->create();
        $question = Question::factory()->for($author)->create();
        Comment::factory()->for($question, 'commentable')->create(); // written by someone else

        $author->delete();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_commenting_has_its_own_rate_limit_bucket(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create();
        $url = "/questions/{$question->id}/comments";

        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($user)->post($url, ['comment' => "Comment number $i here."])->assertRedirect();
        }
        $this->actingAs($user)->post($url, ['comment' => 'One comment too many.'])->assertStatus(429);

        // voting uses a different bucket and still works
        $other = Question::factory()->create();
        $this->actingAs($user)->post("/questions/{$other->id}/vote", ['value' => 1])->assertRedirect();
    }

    public function test_query_count_stays_constant_with_more_comments(): void
    {
        $viewer = User::factory()->create();
        $question = Question::factory()->create();
        $answers = Answer::factory()->count(2)->for($question)->create();

        // start with at least one comment per level: nested eager loads are skipped when empty
        Comment::factory()->for($question, 'commentable')->create();
        Comment::factory()->for($answers[0], 'commentable')->create();

        $count = function () use ($viewer, $question) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($viewer)->get($this->page($question))->assertOk();

            return count(DB::getQueryLog());
        };

        $before = $count();

        Comment::factory()->count(5)->for($question, 'commentable')->create();
        Comment::factory()->count(5)->for($answers[0], 'commentable')->create();
        Comment::factory()->count(5)->for($answers[1], 'commentable')->create();

        $this->assertSame($before, $count());
    }

    public function test_a_question_comment_does_not_bump_the_posts_feed_cache(): void
    {
        $question = Question::factory()->create();
        Cache::put('feed:version', 5);

        $this->actingAs(User::factory()->create())
            ->post("/questions/{$question->id}/comments", ['comment' => 'Does this touch the feed?']);

        $this->assertSame(5, Cache::get('feed:version'));
    }

    public function test_commenting_on_a_question_sends_no_post_notifications(): void
    {
        Notification::fake();
        Mail::fake();
        $question = Question::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/questions/{$question->id}/comments", ['comment' => 'Any notification?'])
            ->assertRedirect();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertDatabaseCount('notifications', 0);
    }
}