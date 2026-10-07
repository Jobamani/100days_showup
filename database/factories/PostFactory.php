<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'user_id' => 1,
            'title'   => $title,
            'slug'    => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 100000),
            'body'    => fake()->paragraphs(3, true),
            'image'   => null,
        ];
    }
}
