<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/analytics/social')->assertUnauthorized();
    }

    public function test_returns_demo_data_when_no_credentials_configured(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'platform_name' => 'YouTube',
            'handle' => '@myChannel',
            'credentials' => null,
        ]);
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'platform_name' => 'Instagram',
            'handle' => '@mygram',
            'credentials' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/social');

        $response->assertOk()
            ->assertJsonPath('demo', true)
            ->assertJsonCount(2, 'accounts')
            ->assertJsonStructure([
                'accounts' => [['id', 'platform', 'handle', 'followers', 'title', 'avatar', 'url', 'live', 'note']],
                'posts' => [['id', 'platform', 'account_id', 'title', 'description', 'thumbnail', 'views', 'likes', 'comments', 'published_at', 'live']],
                'refreshed_at',
            ]);

        foreach ($response->json('accounts') as $account) {
            $this->assertFalse($account['live']);
            $this->assertGreaterThan(0, $account['followers']);
            $this->assertNotSame('', $account['note']);
        }

        $this->assertNotEmpty($response->json('posts'));

        $youtubePost = collect($response->json('posts'))->firstWhere('platform', 'YouTube');
        $this->assertNotNull($youtubePost);
        $this->assertNotNull($youtubePost['views']);
        $this->assertStringContainsString('picsum.photos', $youtubePost['thumbnail']);
    }

    public function test_returns_live_youtube_data_when_api_key_configured(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'platform_name' => 'YouTube',
            'handle' => '@myChannel',
            'credentials' => ['api_key' => 'test-key'],
        ]);

        Http::fake([
            'googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [[
                    'id' => 'UC123',
                    'snippet' => ['title' => 'My Channel', 'thumbnails' => ['high' => ['url' => 'https://yt.thumb/ch.png']]],
                    'statistics' => ['subscriberCount' => '12500'],
                ]],
            ]),
            'googleapis.com/youtube/v3/search*' => Http::response([
                'items' => [[
                    'id' => ['videoId' => 'v1'],
                    'snippet' => [
                        'title' => 'First video',
                        'description' => 'The first one.',
                        'thumbnails' => ['high' => ['url' => 'https://yt.thumb/v1.png']],
                        'publishedAt' => '2026-08-01T10:00:00Z',
                    ],
                ]],
            ]),
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [[
                    'id' => 'v1',
                    'statistics' => ['viewCount' => '9000', 'likeCount' => '400', 'commentCount' => '55'],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/social');

        $response->assertOk()
            ->assertJsonPath('demo', false)
            ->assertJsonPath('accounts.0.live', true)
            ->assertJsonPath('accounts.0.followers', 12500)
            ->assertJsonPath('accounts.0.title', 'My Channel');

        $post = $response->json('posts.0');
        $this->assertSame(9000, $post['views']);
        $this->assertSame(400, $post['likes']);
        $this->assertSame(55, $post['comments']);
    }

    public function test_falls_back_to_demo_when_live_fetch_fails(): void
    {
        $user = User::factory()->create();

        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'platform_name' => 'Facebook',
            'handle' => 'MyPage',
            'credentials' => ['access_token' => 'invalid-token'],
        ]);

        Http::fake(['*' => Http::response(['error' => 'Invalid token.'], 400)]);

        $response = $this->actingAs($user)->getJson('/api/v1/analytics/social');

        $response->assertOk()
            ->assertJsonPath('demo', true)
            ->assertJsonPath('accounts.0.live', false)
            ->assertJsonPath('accounts.0.note', 'Live fetch failed: Facebook API error (400): {"error":"Invalid token."} — showing sample data.');
    }

    public function test_refresh_forgets_cache(): void
    {
        $user = User::factory()->create();
        SocialAccount::factory()->create([
            'user_id' => $user->id,
            'platform_name' => 'YouTube',
            'handle' => '@ch',
            'credentials' => null,
        ]);

        $first = $this->actingAs($user)->getJson('/api/v1/analytics/social');
        $first->assertOk();

        $second = $this->actingAs($user)->getJson('/api/v1/analytics/social?refresh=1');
        $second->assertOk();
    }
}
