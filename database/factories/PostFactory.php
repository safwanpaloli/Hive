<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $statuses = ['draft', 'scheduled', 'posted', 'skipped'];

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'script_body' => fake()->paragraphs(3, true),
            'media_links' => fake()->randomElements(['https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', 'https://images.unsplash.com/photo-1519638399535-1b036603ac77'], 1),
            'scheduled_at' => fake()->dateTimeBetween('-7 days', '+14 days'),
            'status' => fake()->randomElement($statuses),
            'posted_at' => null,
        ];
    }

    public function scheduledToday(): static
    {
        return $this->state(fn () => [
            'status' => 'scheduled',
            'scheduled_at' => now()->setTime(10, 0),
        ]);
    }
}
