<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AskQuestionTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'How do I invalidate a cached feed?',
            'body'  => 'I cache my home feed and it goes stale when a post changes. What is the usual approach?',
            'tags'  => 'laravel, caching',
        ], $overrides);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/questions/create')->assertRedirect('/login');
        $this->post('/questions', $this->validData())->assertRedirect('/login');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_the_ask_form_is_shown_to_logged_in_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/questions/create')
            ->assertOk()
            ->assertSee('name="title"', false)
            ->assertSee('name="tags"', false);
    }

    public function test_a_user_can_ask_a_question_with_tags(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/questions', $this->validData());

        $question = Question::firstOrFail();
        $response->assertRedirect(route('questions.show', [$question, $question->slug]));
        $this->assertSame($user->id, $question->user_id);
        $this->assertSame(['caching', 'laravel'], $question->tags->pluck('slug')->sort()->values()->all());
    }

    public function test_existing_tags_are_reused_not_duplicated(): void
    {
        Tag::forceCreate(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs(User::factory()->create())
            ->post('/questions', $this->validData(['tags' => 'laravel, Caching']));

        $this->assertSame(1, Tag::where('slug', 'laravel')->count());
        $this->assertSame('Laravel', Tag::where('slug', 'laravel')->value('name')); // original spelling kept
    }

    public function test_the_author_cannot_be_set_by_the_client(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post('/questions', $this->validData(['user_id' => $other->id]));

        $this->assertSame($user->id, Question::firstOrFail()->user_id);
    }

    public function test_title_and_body_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/questions', $this->validData(['title' => 'Short']))->assertSessionHasErrors('title');
        $this->actingAs($user)->post('/questions', $this->validData(['title' => str_repeat('a', 151)]))->assertSessionHasErrors('title');
        $this->actingAs($user)->post('/questions', $this->validData(['body' => 'Too short body']))->assertSessionHasErrors('body');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_tags_are_limited_in_number_length_and_shape(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/questions', $this->validData(['tags' => '']))->assertSessionHasErrors('tags');
        $this->actingAs($user)->post('/questions', $this->validData(['tags' => ',']))->assertSessionHasErrors('tags');
        $this->actingAs($user)->post('/questions', $this->validData(['tags' => 'a1,b2,c3,d4,e5,f6']))->assertSessionHasErrors('tags');
        $this->actingAs($user)->post('/questions', $this->validData(['tags' => str_repeat('x', 31)]))->assertSessionHasErrors('tags');
        $this->actingAs($user)->post('/questions', $this->validData(['tags' => '???']))->assertSessionHasErrors('tags');

        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_asking_questions_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->actingAs($user)->post('/questions', $this->validData(['title' => "Question number $i, long enough"]))->assertRedirect();
        }

        $this->actingAs($user)->post('/questions', $this->validData())->assertStatus(429);
    }

    public function test_markdown_in_a_question_is_rendered_safely(): void
    {
        $this->actingAs(User::factory()->create())->post('/questions', $this->validData([
            'body' => "Use **versioned keys**.\n\n<script>alert(1)</script>",
        ]));
        $question = Question::firstOrFail();

        $this->get("/questions/{$question->id}/{$question->slug}")
            ->assertSee('<strong>versioned keys</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_tag_slugs_are_unique_at_the_database_level(): void
    {
        $this->assertTrue(Schema::hasIndex('tags', ['slug'], 'unique'));
    }

    public function test_the_ask_link_is_in_the_nav_for_logged_in_users_only(): void
    {
        $this->get('/')->assertDontSee(route('questions.create'));

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertSee(route('questions.create'));
    }
}