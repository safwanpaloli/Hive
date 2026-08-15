<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        return User::factory()->create();
    }

    public function test_user_can_create_post_with_platforms(): void
    {
        $user = $this->actingUser();
        $account = SocialAccount::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Launch teaser',
                'script_body' => 'Big news coming tomorrow!',
                'status' => 'scheduled',
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'platform_ids' => [$account->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('post.title', 'Launch teaser')
            ->assertJsonPath('post.platforms.0.id', $account->id);

        $this->assertDatabaseHas('post_platform', [
            'post_id' => $response->json('post.id'),
            'social_account_id' => $account->id,
        ]);
    }

    public function test_today_returns_scheduled_posts_only(): void
    {
        $user = $this->actingUser();

        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->setTime(10, 0),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
            'scheduled_at' => now()->setTime(11, 0),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/posts/today')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('pending', 1);
    }

    public function test_user_can_update_post_status_to_posted(): void
    {
        $user = $this->actingUser();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/posts/{$post->id}/status", ['status' => 'posted'])
            ->assertOk()
            ->assertJsonPath('post.status', 'posted')
            ->assertJsonPath('post.posted_at', now()->setMicroseconds(0)->toISOString());
    }

    public function test_history_returns_posted_counts_per_date(): void
    {
        $user = $this->actingUser();

        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'posted',
            'scheduled_at' => now()->subDays(2),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'posted',
            'scheduled_at' => now()->subDays(2)->setTime(18, 0),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'posted',
            'scheduled_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/posts/history')
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_posts_can_be_filtered_by_status(): void
    {
        $user = $this->actingUser();

        Post::factory()->create(['user_id' => $user->id, 'status' => 'posted']);
        Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $this->actingAs($user)
            ->getJson('/api/v1/posts?status=posted')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_user_cannot_touch_another_users_post(): void
    {
        $owner = $this->actingUser();
        $intruder = $this->actingUser();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->patchJson("/api/v1/posts/{$post->id}/status", ['status' => 'posted'])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->deleteJson("/api/v1/posts/{$post->id}")
            ->assertForbidden();
    }
}
