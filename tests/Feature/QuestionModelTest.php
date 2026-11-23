<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends TestCase
 */
class QuestionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_question_belongs_to_its_author(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($question->user->is($user));
    }

    public function test_a_question_has_many_answers_written_by_users(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->create(['question_id' => $question->id]);

        $this->assertTrue($question->answers->first()->is($answer));
        $this->assertTrue($answer->question->is($question));
        $this->assertInstanceOf(User::class, $answer->user);
    }

    public function test_deleting_a_question_deletes_its_answers(): void
    {
        $question = Question::factory()->create();
        Answer::factory()->count(3)->create(['question_id' => $question->id]);

        $question->delete();

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_deleting_a_user_deletes_their_questions(): void
    {
        $user = User::factory()->create();
        Question::factory()->count(2)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_a_question_can_be_tagged_and_a_tag_attaches_only_once(): void
    {
        $question = Question::factory()->create();
        // match the columns your tags table needs (see tagged() in HomeFeedTest)
        $tag = Tag::forceCreate(['name' => 'laravel', 'slug' => 'laravel']);

        $question->tags()->attach($tag);
        $this->assertTrue($question->tags->first()->is($tag));

        $this->expectException(QueryException::class);
        $question->tags()->attach($tag); // duplicate pivot row rejected by the primary key
    }

    public function test_the_slug_is_generated_from_the_title_and_follows_changes(): void
    {
        $question = Question::factory()->create(['title' => 'How do I cache a feed?']);
        $this->assertSame('how-do-i-cache-a-feed', $question->slug);

        $question->update(['title' => 'Why is my cache stale?']);
        $this->assertSame('why-is-my-cache-stale', $question->fresh()->slug);
    }

    public function test_listing_columns_are_indexed(): void
    {
        $this->assertTrue(Schema::hasIndex('answers', ['question_id', 'created_at']));
        $this->assertTrue(Schema::hasIndex('questions', ['created_at']));
    }
}