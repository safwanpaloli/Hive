<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'safwanpaloli7@gmail.com'],
            [
                'name' => 'Safwan Paloli',
                'password' => 'Safwanpaloli7@6960',
                'email_verified_at' => now(),
            ]
        );

        $accounts = collect([
            ['Facebook', '@safwanpaloli', 'https://facebook.com/safwanpaloli', 'Business'],
            ['Instagram', '@safwan.paloli', 'https://instagram.com/safwan.paloli', 'Creator'],
            ['X (Twitter)', '@safwanpaloli', 'https://x.com/safwanpaloli', 'Personal'],
            ['LinkedIn', 'safwan-paloli', 'https://linkedin.com/in/safwan-paloli', 'Business'],
            ['YouTube', 'Safwan Paloli', 'https://youtube.com/@safwanpaloli', 'Creator'],
        ])->map(function ($row) use ($user) {
            [$platform, $handle, $url, $type] = $row;

            return SocialAccount::updateOrCreate(
                ['user_id' => $user->id, 'platform_name' => $platform, 'handle' => $handle],
                ['profile_url' => $url, 'account_type' => $type, 'notes' => null]
            );
        });

        $today = now()->startOfDay();

        $todayPosts = [
            [
                'title' => 'Monday Motivation Thread',
                'body' => "Start the week strong.\n\nWhat is one win you are chasing this week?\n\nDrop it in the comments \u{1F4AA}",
                'time' => 9,
                'platforms' => ['Facebook', 'Instagram', 'X (Twitter)'],
            ],
            [
                'title' => 'Product Spotlight: Behind the Scenes',
                'body' => "Here is a peek at how we build every product we ship.\n\nFull story on the blog \u2192",
                'time' => 13,
                'platforms' => ['LinkedIn'],
            ],
            [
                'title' => 'Evening Reel Tease',
                'body' => "You asked, we delivered.\n\nNew reel dropping tonight \u{1F3A5}",
                'time' => 18,
                'platforms' => ['Instagram'],
            ],
        ];

        foreach ($todayPosts as $i => $item) {
            $platforms = collect($item['platforms'])
                ->map(fn ($name) => $accounts->firstWhere('platform_name', $name)->id)
                ->all();

            $post = Post::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $item['title'],
                    'scheduled_at' => $today->copy()->setTime($item['time'], 0),
                ],
                [
                    'script_body' => $item['body'],
                    'media_links' => ['https://images.unsplash.com/photo-1500530855697-b586d89ba3ee'],
                    'status' => 'scheduled',
                ]
            );

            $post->platforms()->sync($platforms);
        }

        Post::factory()->count(18)
            ->create(['user_id' => $user->id])
            ->each(function (Post $post) use ($accounts) {
                $post->platforms()->sync($accounts->random(1, 3)->pluck('id'));
            });

        $this->command->info('Demo data seeded. Login: safwanpaloli7@gmail.com');
    }
}
