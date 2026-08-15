<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\PlatformConnector;

/**
 * Generates stable, clearly-labelled sample data so the analytics page is
 * fully explorable before real API credentials are configured.
 */
class DemoConnector implements PlatformConnector
{
    private const SAMPLES = [
        'youtube' => [
            'titles' => [
                'We Finally Launched Something Big 🎉',
                'Behind the Scenes: How We Built This',
                '5 Tools That Changed Our Workflow',
                'Live Q&A — Your Questions Answered',
                'The 24-Hour Challenge You Asked For',
                'Top 10 Mistakes Beginners Make',
            ],
            'descriptions' => [
                'After months of work we are finally sharing it with you. This one took a while but we hope you love it as much as we do.',
                'Come with us behind the camera and see exactly how this video came together from idea to publish.',
                'We test every single tool so you do not have to. Here is the shortlist that actually makes a difference.',
                'You sent hundreds of questions and we answer the best ones right here.',
                'No sleep, no shortcuts — watch us take on a full day of back-to-back content.',
                'Avoid these common pitfalls and save yourself weeks of trial and error.',
            ],
        ],
        'instagram' => [
            'titles' => [
                'Golden hour vibes ✨',
                'New drop alert 🔥',
                'Behind the scenes today 📸',
                'Weekend plans? 🎒',
                'How it is made — thread',
                'Gratitude list 🧡',
            ],
            'descriptions' => [
                'The light today was unreal. Catch the full set on stories before it disappears.',
                'The collection you have been asking for finally lands this Friday. Swipe for a first look.',
                'BTS of everything that goes into one post — from styling to the final edit.',
                'We are going on an adventure this weekend and bringing you along. Save this post so you do not miss it.',
                'Breaking down every step of how we create our favourite product from scratch.',
                'This week we are grateful for every single one of you. Drop your wins below.',
            ],
        ],
        'facebook' => [
            'titles' => [
                'Check out our latest update',
                'Live Q&A this Friday 🎙️',
                'New team member, big news!',
                'Community highlight of the week',
                'We hit a milestone — thank you!',
                'Open House: come say hi',
            ],
            'descriptions' => [
                'A quick summary of everything new this month and what is coming next for our community.',
                'Join us live this Friday at 6 PM to ask us anything about our process, plans and people.',
                'We are growing! Meet the newest member of the family and hear what they will be working on.',
                'This week we are spotlighting a member who has been with us since day one.',
                'Reaching this milestone only happened because of your support. Here is a look back at the journey.',
                'If you are in the area, stop by our open house this weekend — coffee is on us.',
            ],
        ],
    ];

    private const RANGES = [
        'youtube' => [12_000, 480_000],
        'instagram' => [3_500, 320_000],
        'facebook' => [8_000, 240_000],
        'default' => [1_000, 60_000],
    ];

    public function __construct(
        private readonly string $platform,
        private readonly string $handle,
    ) {}

    public function overview(array $credentials = [], string $handle = ''): array
    {
        $seed = crc32($this->handle);
        $range = self::RANGES[$this->platform] ?? self::RANGES['default'];

        return [
            'followers' => $range[0] + ($seed % ($range[1] - $range[0])),
            'title' => $this->channelName($seed),
            'avatar' => 'https://picsum.photos/seed/avatar-'.$seed.'/128',
            'url' => $this->profileUrl(),
        ];
    }

    public function posts(array $credentials = [], string $handle = '', int $limit = 8): array
    {
        $seed = crc32($this->handle);
        $samples = self::SAMPLES[$this->platform] ?? self::SAMPLES['facebook'];
        $count = min($limit, count($samples['titles']));

        $posts = [];
        for ($i = 0; $i < $count; $i++) {
            $offset = ($seed + $i) % $count;
            $postSeed = crc32($this->handle.$i);
            $published = now()->subDays($i * 3 + 1)->subHours($seed % 12)->toIso8601String();

            $posts[] = [
                'id' => 'demo:'.$this->platform.':'.$i,
                'title' => $samples['titles'][$offset],
                'description' => $samples['descriptions'][$offset],
                'thumbnail' => 'https://picsum.photos/seed/'.$this->platform.'-'.$postSeed.'/640/360',
                'url' => $this->profileUrl(),
                'views' => $this->platform === 'youtube' ? 1_500 + ($postSeed % 80_000) : null,
                'likes' => 40 + ($postSeed % 1_800),
                'comments' => 2 + ($postSeed % 120),
                'published_at' => $published,
            ];
        }

        return $posts;
    }

    private function channelName(int $seed): string
    {
        $name = ucwords(str_replace(['-', '_', '.'], ' ', ltrim($this->handle, '@')));

        return trim($name) !== '' ? $name : ucfirst($this->platform);
    }

    private function profileUrl(): string
    {
        $handle = ltrim($this->handle, '@');

        return match ($this->platform) {
            'youtube' => 'https://www.youtube.com/@'.$handle,
            'instagram' => 'https://instagram.com/'.$handle,
            'facebook' => 'https://facebook.com/'.$handle,
            default => 'https://example.com/'.$handle,
        };
    }
}
