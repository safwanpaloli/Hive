<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\PlatformConnector;
use RuntimeException;

class InstagramConnector implements PlatformConnector
{
    private const BASE_URL = 'https://graph.facebook.com/v21.0';

    private string $token = '';

    public function overview(array $credentials, string $handle): array
    {
        $this->init($credentials);
        $igId = $this->resolveIgId($credentials, $handle);
        $data = $this->call('/'.$igId, [
            'fields' => 'username,followers_count,media_count,profile_picture_url',
        ]);

        return [
            'followers' => (int) ($data['followers_count'] ?? 0),
            'title' => $data['username'] ?? $handle,
            'avatar' => $data['profile_picture_url'] ?? null,
            'url' => 'https://instagram.com/'.$handle,
        ];
    }

    public function posts(array $credentials, string $handle, int $limit = 8): array
    {
        $this->init($credentials);
        $igId = $this->resolveIgId($credentials, $handle);
        $data = $this->call('/'.$igId.'/media', [
            'fields' => 'caption,media_type,media_url,thumbnail_url,permalink,like_count,comments_count',
            'limit' => $limit,
        ]);

        return collect($data['data'] ?? [])->map(function (array $item) {
            $caption = $item['caption'] ?? '';
            $lines = preg_split('/\R/', $caption);

            return [
                'id' => 'ig:'.($item['id'] ?? ''),
                'title' => mb_substr(($lines[0] ?? '') !== '' ? $lines[0] : 'Instagram post', 0, 140),
                'description' => $caption,
                'thumbnail' => $item['thumbnail_url'] ?? $item['media_url'] ?? null,
                'url' => $item['permalink'] ?? null,
                'views' => null,
                'likes' => (int) ($item['like_count'] ?? 0),
                'comments' => (int) ($item['comments_count'] ?? 0),
                'published_at' => null,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function resolveIgId(array $credentials, string $handle): string
    {
        if (! empty($credentials['ig_user_id'])) {
            return (string) $credentials['ig_user_id'];
        }

        $data = $this->call('/me/accounts', [
            'fields' => 'id,name,instagram_business_account{id,username}',
            'limit' => 100,
        ]);
        $pages = $data['data'] ?? [];
        $needle = strtolower(ltrim($handle, '@'));

        foreach ($pages as $page) {
            $ig = $page['instagram_business_account'] ?? null;
            if ($ig && strtolower($ig['username'] ?? '') === $needle) {
                return (string) $ig['id'];
            }
        }

        foreach ($pages as $page) {
            if (! empty($page['instagram_business_account']['id'])) {
                return (string) $page['instagram_business_account']['id'];
            }
        }

        throw new RuntimeException('No Instagram business account found for this token.');
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function call(string $path, array $query): array
    {
        $response = HttpClientFactory::make(self::BASE_URL)
            ->get($path, array_merge($query, ['access_token' => $this->token]));

        if ($response->failed()) {
            throw new RuntimeException(
                'Instagram API error ('.$response->status().'): '.$response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function init(array $credentials): void
    {
        $accessToken = $credentials['access_token'] ?? config('services.instagram.access_token');

        if (blank($accessToken)) {
            throw new RuntimeException('No Instagram access token configured.');
        }

        $this->token = (string) $accessToken;
    }
}
