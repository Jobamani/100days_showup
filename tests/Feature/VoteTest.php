<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    private function castVote(User $user, Question|Answer $votable, int $value): Vote
    {
        return Vote::forceCreate([
            'user_id' => $user->id,
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->id,
            'value' => $value,
        ]);
    }

    private function addVotedAnswers(Question $question, int $count): void
    {
        Answer::factory()->count($count)->create(['question_id' => $question->id])
            ->each(fn (Answer $answer) => $this->castVote(User::factory()->create(), $answer, 1));
    }

    private function queryCount(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_guests_cannot_vote(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create();

        $this->post("/questions/{$question->id}/vote", ['value' => 1])->assertRedirect('/login');
        $this->post("/answers/{$answer->id}/vote", ['value' => 1])->assertRedirect('/login');

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_a_user_can_upvote_a_question(): void
    {
        $voter = User::factory()->create();
        $question = Question::factory()->create();

        $this->actingAs($voter)
            ->post("/questions/{$question->id}/vote", ['value' => 1])
            ->assertRedirect(route('questions.show', [$question, $question->slug]));

        $this->assertDatabaseHas('votes', [
            'user_id' => $voter->id,
            'votable_type' => 'question',
            'votable_id' => $question->id,
            'value' => 1,
        ]);
    }

    public function test_a_user_can_downvote_an_answer(): void
    {
        $voter = User::factory()->create();
        $answer = Answer::factory()->create();
        $question = $answer->question;

        $this->actingAs($voter)
            ->post("/answers/{$answer->id}/vote", ['value' => -1])
            ->assertRedirect(route('questions.show', [$question, $question->slug]));

        $this->assertDatabaseHas('votes', [
            'votable_type' => 'answer',
            'votable_id' => $answer->id,
            'value' => -1,
        ]);
    }

    public function test_voting_the_same_way_again_retracts_the_vote(): void
    {
        $voter = User::factory()->create();
        $question = Question::factory()->create();

        $this->actingAs($voter)->post("/questions/{$question->id}/vote", ['value' => 1]);
        $this->actingAs($voter)->post("/questions/{$question->id}/vote", ['value' => 1]);

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_voting_the_other_way_switches_the_vote(): void
    {
        $voter = User::factory()->create();
        $question = Question::factory()->create();

        $this->actingAs($voter)->post("/questions/{$question->id}/vote", ['value' => 1]);
        $this->actingAs($voter)->post("/questions/{$question->id}/vote", ['value' => -1]);

        $this->assertDatabaseCount('votes', 1);
        $this->assertSame(-1, Vote::firstOrFail()->value);
    }

    public function test_you_cannot_vote_on_your_own_question_or_answer(): void
    {
        $author = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $author->id]);
        $answer = Answer::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)->post("/questions/{$question->id}/vote", ['value' => 1])->assertForbidden();
        $this->actingAs($author)->post("/answers/{$answer->id}/vote", ['value' => 1])->assertForbidden();

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_the_vote_value_must_be_plus_or_minus_one(): void
    {
        $voter = User::factory()->create();
        $question = Question::factory()->create();

        foreach (['0', '2', '-2', 'up', ''] as $bad) {
            $this->actingAs($voter)
                ->post("/questions/{$question->id}/vote", ['value' => $bad])
                ->assertSessionHasErrors('value');
        }

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_one_vote_per_user_per_item_is_enforced_by_the_database(): void
    {
        $voter = User::factory()->create();
        $question = Question::factory()->create();
        $this->castVote($voter, $question, 1);

        $this->expectException(QueryException::class);
        $this->castVote($voter, $question, -1);
    }

    public function test_the_page_shows_scores_including_zero(): void
    {
        $question = Question::factory()->create();
        Answer::factory()->create(['question_id' => $question->id]); // no votes
        foreach ([1, 1, -1] as $value) {
            $this->castVote(User::factory()->create(), $question, $value);
        }

        $this->get("/questions/{$question->id}/{$question->slug}")
            ->assertSee('data-question-score="1"', false)
            ->assertSee('data-answer-score="0"', false);
    }

    public function test_the_viewers_own_vote_is_marked_on_the_page(): void
    {
        $viewer = User::factory()->create();
        $question = Question::factory()->create();
        $url = "/questions/{$question->id}/{$question->slug}";
        $this->castVote($viewer, $question, 1);

        $this->get($url)->assertDontSee('data-my-vote', false);
        $this->actingAs($viewer)->get($url)->assertSee('data-my-vote="1"', false);
    }

    public function test_query_count_does_not_grow_with_answers_and_votes(): void
    {
        $question = Question::factory()->create();
        $url = "/questions/{$question->id}/{$question->slug}";
        $this->actingAs(User::factory()->create());

        $this->addVotedAnswers($question, 2);
        $small = $this->queryCount($url);

        $this->addVotedAnswers($question, 6);
        $large = $this->queryCount($url);

        $this->assertGreaterThan(0, $small);
        $this->assertSame($small, $large, "Question page ran $small queries for 2 answers but $large for 8 (N+1)");
    }

    public function test_the_vote_limit_is_its_own_bucket(): void
    {
        $voter = User::factory()->create();
        $question = Question::factory()->create();
        $url = "/questions/{$question->id}/vote";

        foreach (range(1, 20) as $i) {
            $this->actingAs($voter)->post($url, ['value' => 1])->assertRedirect();
        }
        $this->actingAs($voter)->post($url, ['value' => 1])->assertStatus(429);

        // using up the vote limit must not block asking a question
        $this->actingAs($voter)->post('/questions', [
            'title' => 'Can I still ask a question?',
            'body'  => 'The vote limit is used up, but asking should have its own limit.',
            'tags'  => 'laravel',
        ])->assertRedirect();

        $this->assertDatabaseCount('questions', 2); // the factory one and the new one
    }

    public function test_deleting_a_question_deletes_the_votes_on_it_and_on_its_answers(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);
        $this->castVote(User::factory()->create(), $question, 1);
        $this->castVote(User::factory()->create(), $answer, 1);

        $other = Question::factory()->create();
        $this->castVote(User::factory()->create(), $other, 1); // must survive

        $question->delete();

        $this->assertDatabaseCount('votes', 1);
    }

    public function test_deleting_a_user_deletes_votes_on_their_questions_and_answers(): void
    {
        $author = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $author->id]);
        $answerOnSomeoneElsesQuestion = Answer::factory()->create(['user_id' => $author->id]);
        $this->castVote(User::factory()->create(), $question, 1);
        $this->castVote(User::factory()->create(), $answerOnSomeoneElsesQuestion, 1);

        $author->delete();

        $this->assertDatabaseCount('votes', 0);
    }
}