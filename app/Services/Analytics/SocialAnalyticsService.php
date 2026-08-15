<?php

namespace App\Services\Analytics;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Analytics\Contracts\PlatformConnector;
use Illuminate\Support\Facades\Cache;

class SocialAnalyticsService
{
    /** @var array<string, class-string<PlatformConnector>> */
    private const CONNECTORS = [
        'youtube' => YouTubeConnector::class,
        'facebook' => FacebookConnector::class,
        'instagram' => InstagramConnector::class,
    ];

    /**
     * @return array{
     *     accounts: list<array<string, mixed>>,
     *     posts: list<array<string, mixed>>,
     *     demo: bool,
     *     refreshed_at: string
     * }
     */
    public function forUser(User $user, bool $refresh = false): array
    {
        $key = 'analytics.social.'.$user->id;

        if ($refresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(10), function () use ($user) {
            return $this->build($user);
        });
    }

    /**
     * @return array{
     *     accounts: list<array<string, mixed>>,
     *     posts: list<array<string, mixed>>,
     *     demo: bool,
     *     refreshed_at: string
     * }
     */
    private function build(User $user): array
    {
        $accounts = $user->socialAccounts()->get();

        $result = ['accounts' => [], 'posts' => [], 'demo' => false];
        $anyDemo = false;

        foreach ($accounts as $account) {
            $platform = $this->platformKey($account->platform_name);
            $credentials = $account->credentials ?? [];
            $connector = $this->connector($platform);

            [$overview, $posts, $live, $note] = $this->fetch($connector, $platform, $account);

            if (! $live) {
                $anyDemo = true;
            }

            $result['accounts'][] = [
                'id' => $account->id,
                'platform' => $account->platform_name,
                'handle' => $account->handle,
                'followers' => $overview['followers'] ?? 0,
                'title' => $overview['title'] ?? $account->handle,
                'avatar' => $overview['avatar'] ?? null,
                'url' => $overview['url'] ?? $account->profile_url,
                'live' => $live,
                'note' => $note,
            ];

            foreach ($posts as $post) {
                $result['posts'][] = [
                    'id' => $post['id'],
                    'platform' => $account->platform_name,
                    'account_id' => $account->id,
                    'account_handle' => $account->handle,
                    'title' => $post['title'],
                    'description' => $post['description'],
                    'thumbnail' => $post['thumbnail'],
                    'url' => $post['url'],
                    'views' => $post['views'],
                    'likes' => $post['likes'],
                    'comments' => $post['comments'],
                    'published_at' => $post['published_at'],
                    'live' => $live,
                ];
            }
        }

        $result['demo'] = $anyDemo;
        $result['refreshed_at'] = now()->toIso8601String();

        return $result;
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>, 2: bool, 3: ?string}
     */
    private function fetch(?PlatformConnector $connector, string $platform, SocialAccount $account): array
    {
        $credentials = $account->credentials ?? [];
        $handle = $account->handle;

        if ($connector !== null && $this->hasCredentials($platform, $credentials)) {
            try {
                $overview = $connector->overview($credentials, $handle);
                $posts = $connector->posts($credentials, $handle, 8);

                return [$overview, $posts, true, null];
            } catch (\Throwable $e) {
                // Live fetch failed (bad token, rate limit, TLS…). Fall back to
                // demo data but surface the real reason so it is not silent.
                $demo = new DemoConnector($platform, $handle);
                $reason = str_replace("\n", ' ', $e->getMessage());

                return [
                    $demo->overview(),
                    $demo->posts(),
                    false,
                    'Live fetch failed: '.mb_substr($reason, 0, 160).' — showing sample data.',
                ];
            }
        }

        $demo = new DemoConnector($platform, $handle);

        return [
            $demo->overview(),
            $demo->posts(),
            false,
            $connector === null
                ? 'No live connector for this platform yet.'
                : 'No API credentials configured yet — showing sample data.',
        ];
    }

    public function forget(User $user): void
    {
        Cache::forget('analytics.social.'.$user->id);
    }

    private function platformKey(string $platformName): string
    {
        return strtolower(trim($platformName));
    }

    private function connector(string $platform): ?PlatformConnector
    {
        $class = self::CONNECTORS[$platform] ?? null;

        return $class !== null ? new $class : null;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function hasCredentials(string $platform, array $credentials): bool
    {
        return match ($platform) {
            'youtube' => ! blank($credentials['api_key'] ?? null) || ! blank(config('services.youtube.api_key')),
            'facebook' => ! blank($credentials['access_token'] ?? null) || ! blank(config('services.facebook.access_token')),
            'instagram' => ! blank($credentials['access_token'] ?? null) || ! blank(config('services.instagram.access_token')),
            default => false,
        };
    }
}
