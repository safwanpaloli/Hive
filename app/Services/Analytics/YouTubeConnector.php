<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\PlatformConnector;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class YouTubeConnector implements PlatformConnector
{
    private const BASE_URL = 'https://www.googleapis.com/youtube/v3';

    public function overview(array $credentials, string $handle): array
    {
        $channel = $this->channel($credentials, $handle);
        $snippet = $channel['snippet'] ?? [];
        $stats = $channel['statistics'] ?? [];

        return [
            'followers' => (int) ($stats['subscriberCount'] ?? 0),
            'title' => $snippet['title'] ?? $handle,
            'avatar' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
            'url' => 'https://www.youtube.com/@'.ltrim($handle, '@'),
        ];
    }

    public function posts(array $credentials, string $handle, int $limit = 8): array
    {
        $client = $this->client($credentials);
        $channel = $this->channel($credentials, $handle);
        $channelId = $channel['id'] ?? null;

        if ($channelId === null) {
            return [];
        }

        $search = $this->call($client, '/search', [
            'channelId' => $channelId,
            'type' => 'video',
            'order' => 'date',
            'maxResults' => $limit,
            'part' => 'snippet',
        ]);

        $items = $search['items'] ?? [];
        $ids = collect($items)->pluck('id.videoId')->filter()->implode(',');

        $statsById = [];
        if ($ids !== '') {
            $videos = $this->call($client, '/videos', [
                'part' => 'statistics',
                'id' => $ids,
            ]);
            foreach ($videos['items'] ?? [] as $video) {
                $statsById[$video['id']] = $video['statistics'] ?? [];
            }
        }

        return collect($items)->map(function (array $item) use ($statsById) {
            $snippet = $item['snippet'] ?? [];
            $videoId = $item['id']['videoId'] ?? '';
            $stats = $statsById[$videoId] ?? [];

            return [
                'id' => 'yt:'.$videoId,
                'title' => $snippet['title'] ?? 'Untitled',
                'description' => $snippet['description'] ?? '',
                'thumbnail' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
                'url' => $videoId !== '' ? 'https://www.youtube.com/watch?v='.$videoId : null,
                'views' => isset($stats['viewCount']) ? (int) $stats['viewCount'] : null,
                'likes' => isset($stats['likeCount']) ? (int) $stats['likeCount'] : null,
                'comments' => isset($stats['commentCount']) ? (int) $stats['commentCount'] : null,
                'published_at' => $snippet['publishedAt'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    private function channel(array $credentials, string $handle): array
    {
        $client = $this->client($credentials);
        $query = ['part' => 'snippet,statistics', 'maxResults' => 1];

        $handle = ltrim($handle, '@');

        if (isset($credentials['channel_id']) && $credentials['channel_id'] !== '') {
            $query['id'] = $credentials['channel_id'];
        } else {
            $query['forHandle'] = $handle;
        }

        $response = $this->call($client, '/channels', $query);

        return $response['items'][0] ?? [];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function client(array $credentials): PendingRequest
    {
        $apiKey = $credentials['api_key'] ?? config('services.youtube.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('No YouTube API key configured.');
        }

        return HttpClientFactory::make(self::BASE_URL)
            ->withQueryParameters(['key' => $apiKey]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function call(PendingRequest $client, string $path, array $query): array
    {
        $response = $client->get($path, $query);

        if ($response->failed()) {
            throw new RuntimeException(
                'YouTube API error ('.$response->status().'): '.$response->body()
            );
        }

        return $response->json() ?? [];
    }
}
