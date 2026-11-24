<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionPageTest extends TestCase
{
    use RefreshDatabase;

    private function queryCount(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_guest_can_read_a_question_with_its_answers(): void
    {
        $asker = User::factory()->create(['name' => 'Ada Lovelace']);
        $answerer = User::factory()->create(['name' => 'Grace Hopper']);
        $question = Question::factory()->create(['user_id' => $asker->id, 'title' => 'How do I cache a feed?']);
        $question->tags()->attach(Tag::forceCreate(['name' => 'caching', 'slug' => 'caching']));
        Answer::factory()->create(['question_id' => $question->id, 'user_id' => $answerer->id, 'body' => 'Use a versioned key.']);

        $this->get("/questions/{$question->id}/{$question->slug}")
            ->assertOk()
            ->assertSee('How do I cache a feed?')
            ->assertSee('Ada Lovelace')
            ->assertSee('Grace Hopper')
            ->assertSee('Use a versioned key.')
            ->assertSee('caching');
    }

    public function test_a_wrong_or_missing_slug_redirects_to_the_canonical_url(): void
    {
        $question = Question::factory()->create(['title' => 'How do I cache a feed?']);
        $canonical = "/questions/{$question->id}/how-do-i-cache-a-feed";

        $this->get("/questions/{$question->id}/old-title")->assertRedirect($canonical)->assertStatus(301);
        $this->get("/questions/{$question->id}")->assertRedirect($canonical)->assertStatus(301);
        $this->get($canonical)->assertOk();
    }

    public function test_a_title_with_no_sluggable_characters_does_not_redirect_forever(): void
    {
        $question = Question::factory()->create(['title' => '???']);

        $this->assertSame('', $question->slug);
        $this->get("/questions/{$question->id}")->assertOk();
    }

    public function test_answers_are_listed_oldest_first(): void
    {
        $question = Question::factory()->create();
        Answer::factory()->create(['question_id' => $question->id, 'body' => 'Newest answer', 'created_at' => now()->subHour()]);
        Answer::factory()->create(['question_id' => $question->id, 'body' => 'Oldest answer', 'created_at' => now()->subHours(2)]);

        $this->get("/questions/{$question->id}/{$question->slug}")
            ->assertSeeInOrder(['Oldest answer', 'Newest answer']);
    }

    public function test_query_count_does_not_grow_with_the_number_of_answers(): void
    {
        $question = Question::factory()->create();
        $url = "/questions/{$question->id}/{$question->slug}";

        Answer::factory()->count(2)->create(['question_id' => $question->id]);
        $small = $this->queryCount($url);

        Answer::factory()->count(6)->create(['question_id' => $question->id]);
        $large = $this->queryCount($url);

        $this->assertGreaterThan(0, $small);
        $this->assertSame($small, $large, "Question page ran $small queries for 2 answers but $large for 8 (N+1)");
    }

    public function test_a_logged_in_user_can_post_an_answer(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create();

        $this->actingAs($user)
            ->post("/questions/{$question->id}/answers", ['body' => 'Cache it with a version key.'])
            ->assertRedirect(route('questions.show', [$question, $question->slug]));

        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'user_id' => $user->id,
            'body' => 'Cache it with a version key.',
        ]);
    }

    public function test_guests_cannot_post_answers(): void
    {
        $question = Question::factory()->create();

        $this->post("/questions/{$question->id}/answers", ['body' => 'Anonymous answer text.'])
            ->assertRedirect('/login');

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_answer_body_is_validated(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create();
        $url = "/questions/{$question->id}/answers";

        $this->actingAs($user)->post($url, ['body' => ''])->assertSessionHasErrors('body');
        $this->actingAs($user)->post($url, ['body' => 'too short'])->assertSessionHasErrors('body');
        $this->actingAs($user)->post($url, ['body' => str_repeat('a', 10001)])->assertSessionHasErrors('body');

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_the_author_cannot_be_set_by_the_client(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();
        $question = Question::factory()->create();

        $this->actingAs($user)->post("/questions/{$question->id}/answers", [
            'body' => 'This answer claims another author.',
            'user_id' => $someoneElse->id,
            'question_id' => 999,
        ]);

        $answer = Answer::firstOrFail();
        $this->assertSame($user->id, $answer->user_id);
        $this->assertSame($question->id, $answer->question_id);
    }

    public function test_answer_text_is_escaped(): void
    {
        $question = Question::factory()->create();
        Answer::factory()->create(['question_id' => $question->id, 'body' => '<script>alert(1)</script>']);

        $this->get("/questions/{$question->id}/{$question->slug}")
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_posting_answers_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create();
        $url = "/questions/{$question->id}/answers";

        foreach (range(1, 10) as $i) {
            $this->actingAs($user)->post($url, ['body' => "Answer number $i, long enough."])->assertRedirect();
        }

        $this->actingAs($user)->post($url, ['body' => 'One answer too many, long enough.'])->assertStatus(429);
    }
}