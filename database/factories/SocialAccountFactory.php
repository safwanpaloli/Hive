<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        $platforms = ['Facebook', 'Instagram', 'X (Twitter)', 'LinkedIn', 'YouTube', 'TikTok', 'Pinterest', 'Threads'];

        return [
            'user_id' => User::factory(),
            'platform_name' => fake()->randomElement($platforms),
            'handle' => '@'.fake()->userName(),
            'profile_url' => 'https://example.com/'.fake()->userName(),
            'account_type' => fake()->randomElement(['Personal', 'Business', 'Creator']),
            'notes' => fake()->optional()->sentence(),
            'credentials' => null,
        ];
    }
}
