<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\PlatformConnector;
use RuntimeException;

class FacebookConnector implements PlatformConnector
{
    private const BASE_URL = 'https://graph.facebook.com/v21.0';

    private string $token = '';

    public function overview(array $credentials, string $handle): array
    {
        $this->init($credentials);
        $pageId = $this->resolvePageId($credentials, $handle);
        $data = $this->call('/'.$pageId, [
            'fields' => 'name,followers_count,picture{url}',
        ]);

        return [
            'followers' => (int) ($data['followers_count'] ?? 0),
            'title' => $data['name'] ?? $handle,
            'avatar' => $data['picture']['data']['url'] ?? null,
            'url' => 'https://facebook.com/'.$pageId,
        ];
    }

    public function posts(array $credentials, string $handle, int $limit = 8): array
    {
        $this->init($credentials);
        $pageId = $this->resolvePageId($credentials, $handle);
        $data = $this->call('/'.$pageId.'/posts', [
            'fields' => 'message,created_time,full_picture,permalink_url,reactions.summary(true),comments.summary(true)',
            'limit' => $limit,
        ]);

        return collect($data['data'] ?? [])->map(function (array $item) {
            $message = $item['message'] ?? '';
            $lines = preg_split('/\R/', $message);

            return [
                'id' => 'fb:'.($item['id'] ?? ''),
                'title' => mb_substr(($lines[0] ?? '') !== '' ? $lines[0] : 'Facebook post', 0, 140),
                'description' => $message,
                'thumbnail' => $item['full_picture'] ?? null,
                'url' => $item['permalink_url'] ?? null,
                'views' => null,
                'likes' => (int) ($item['reactions']['summary']['total_count'] ?? 0),
                'comments' => (int) ($item['comments']['summary']['total_count'] ?? 0),
                'published_at' => $item['created_time'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function resolvePageId(array $credentials, string $handle): string
    {
        if (! empty($credentials['page_id'])) {
            return (string) $credentials['page_id'];
        }

        $data = $this->call('/me/accounts', ['fields' => 'id,name', 'limit' => 100]);
        $pages = $data['data'] ?? [];
        $needle = strtolower($handle);

        foreach ($pages as $page) {
            $name = strtolower($page['name'] ?? '');
            if ($name !== '' && ($name === $needle || str_contains($name, $needle) || str_contains($needle, $name))) {
                return (string) $page['id'];
            }
        }

        if ($pages !== []) {
            return (string) $pages[0]['id'];
        }

        throw new RuntimeException('No Facebook page found for this token.');
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
                'Facebook API error ('.$response->status().'): '.$response->body()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function init(array $credentials): void
    {
        $accessToken = $credentials['access_token'] ?? config('services.facebook.access_token');

        if (blank($accessToken)) {
            throw new RuntimeException('No Facebook access token configured.');
        }

        $this->token = (string) $accessToken;
    }
}
