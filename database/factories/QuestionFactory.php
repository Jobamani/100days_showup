<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;


/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [            
            'user_id' => User::factory(),
            'title'   => rtrim(fake()->sentence(6), '.').'?',
            'body'    => fake()->paragraphs(2, true),
        ];
    }
}
