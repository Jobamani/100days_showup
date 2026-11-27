<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcceptAnswerTest extends TestCase
{
    use RefreshDatabase;

    private function setupQuestion(): array
    {
        $asker = User::factory()->create();
        $question = Question::factory()->for($asker)->create();
        $answer = Answer::factory()->for($question)->create();

        return [$asker, $question, $answer];
    }

    private function page(Question $question): string
    {
        return route('questions.show', [$question, $question->slug]);
    }

    public function test_guests_cannot_accept(): void
    {
        [, , $answer] = $this->setupQuestion();

        $this->post(route('answers.accept', $answer))->assertRedirect('/login');
    }

    public function test_the_question_author_can_accept(): void
    {
        [$asker, $question, $answer] = $this->setupQuestion();

        $this->actingAs($asker)->post(route('answers.accept', $answer))->assertRedirect();

        $this->assertSame($answer->id, $question->fresh()->accepted_answer_id);
    }

    public function test_other_users_cannot_accept(): void
    {
        [, $question, $answer] = $this->setupQuestion();

        $this->actingAs(User::factory()->create())
            ->post(route('answers.accept', $answer))->assertForbidden();

        $this->assertNull($question->fresh()->accepted_answer_id);
    }

    public function test_the_answer_author_cannot_accept_their_own_answer_on_someone_elses_question(): void
    {
        [, $question, $answer] = $this->setupQuestion();

        $this->actingAs($answer->user)
            ->post(route('answers.accept', $answer))->assertForbidden();

        $this->assertNull($question->fresh()->accepted_answer_id);
    }

    public function test_accepting_another_answer_replaces_the_first(): void
    {
        [$asker, $question, $first] = $this->setupQuestion();
        $second = Answer::factory()->for($question)->create();

        $this->actingAs($asker)->post(route('answers.accept', $first));
        $this->actingAs($asker)->post(route('answers.accept', $second));

        $this->assertSame($second->id, $question->fresh()->accepted_answer_id);
    }

    public function test_accepting_the_same_answer_again_un_accepts_it(): void
    {
        [$asker, $question, $answer] = $this->setupQuestion();

        $this->actingAs($asker)->post(route('answers.accept', $answer));
        $this->actingAs($asker)->post(route('answers.accept', $answer));

        $this->assertNull($question->fresh()->accepted_answer_id);
    }

    public function test_deleting_the_accepted_answer_clears_the_question(): void
    {
        [$asker, $question, $answer] = $this->setupQuestion();
        $this->actingAs($asker)->post(route('answers.accept', $answer));

        $answer->delete();

        $this->assertNull($question->fresh()->accepted_answer_id);
    }

    public function test_the_accepted_answer_is_listed_first_and_marked(): void
    {
        [$asker, $question, $accepted] = $this->setupQuestion();
        $popular = Answer::factory()->for($question)->create();
        Vote::forceCreate([
            'user_id' => User::factory()->create()->id,
            'votable_type' => 'answer', 'votable_id' => $popular->id, 'value' => 1,
        ]);
        $this->actingAs($asker)->post(route('answers.accept', $accepted));

        $this->get($this->page($question))
            ->assertSeeInOrder(['data-answer-id="'.$accepted->id.'"', 'data-answer-id="'.$popular->id.'"'], false)
            ->assertSee('data-accepted="1"', false);
    }

    public function test_remaining_answers_sort_by_score_and_zero_beats_negative(): void
    {
        $question = Question::factory()->create();
        $negative = Answer::factory()->for($question)->create();
        $unvoted = Answer::factory()->for($question)->create();
        $positive = Answer::factory()->for($question)->create();

        Vote::forceCreate(['user_id' => User::factory()->create()->id,
            'votable_type' => 'answer', 'votable_id' => $negative->id, 'value' => -1]);
        Vote::forceCreate(['user_id' => User::factory()->create()->id,
            'votable_type' => 'answer', 'votable_id' => $positive->id, 'value' => 1]);

        $this->get($this->page($question))->assertSeeInOrder([
            'data-answer-id="'.$positive->id.'"',
            'data-answer-id="'.$unvoted->id.'"',
            'data-answer-id="'.$negative->id.'"',
        ], false);
    }

    public function test_only_the_asker_sees_the_accept_button(): void
    {
        [$asker, $question] = $this->setupQuestion();

        $this->actingAs($asker)->get($this->page($question))
            ->assertSee('data-accept-button', false);

        $this->actingAs(User::factory()->create())->get($this->page($question))
            ->assertDontSee('data-accept-button', false);
    }

    public function test_accepted_answer_id_cannot_be_mass_assigned(): void
    {
        $this->assertNotContains('accepted_answer_id', (new Question)->getFillable());
    }

    public function test_query_count_stays_constant_with_more_answers(): void
    {
        [$asker, $question] = $this->setupQuestion();
        $count = function () use ($asker, $question) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($asker)->get($this->page($question))->assertOk();
            return count(DB::getQueryLog());
        };

        $before = $count();
        Answer::factory()->count(5)->for($question)->create();

        $this->assertSame($before, $count());
    }
}