<?php

namespace App\Services\Analytics\Contracts;

/**
 * Contract for fetching public performance data from a social platform.
 */
interface PlatformConnector
{
    /**
     * Fetch the account overview (followers, title, avatar).
     *
     * @param  array<string, mixed>  $credentials
     * @return array{followers: int, title: string, avatar: ?string, url: ?string}
     */
    public function overview(array $credentials, string $handle): array;

    /**
     * Fetch recent posts with engagement stats.
     *
     * @param  array<string, mixed>  $credentials
     * @return list<array{
     *     id: string, title: string, description: string, thumbnail: ?string,
     *     url: ?string, views: ?int, likes: ?int, comments: ?int, published_at: ?string
     * }>
     */
    public function posts(array $credentials, string $handle, int $limit = 8): array;
}
