<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_user_can_create_post_with_uploaded_media(): void
    {
        Storage::fake('public');
        $user = $this->actingUser();

        $response = $this->actingAs($user)
            ->post('/api/v1/posts', [
                'title' => 'Launch teaser',
                'status' => 'draft',
                'media_files' => [
                    UploadedFile::fake()->image('hero.jpg', 200, 200),
                    UploadedFile::fake()->createWithContent('brief.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n"),
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('post.title', 'Launch teaser');

        $post = Post::find($response->json('post.id'));
        $this->assertCount(2, $post->media_files);

        foreach ($post->media_files as $media) {
            Storage::disk('public')->assertExists('post-media/'.basename($media['url']));
        }
    }

    public function test_user_can_update_post_and_removed_media_is_deleted(): void
    {
        Storage::fake('public');
        $user = $this->actingUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $keep = 'post-media/keep.pdf';
        $drop = 'post-media/drop.pdf';
        Storage::disk('public')->put($keep, 'keep');
        Storage::disk('public')->put($drop, 'drop');

        $post->update([
            'media_files' => [
                ['url' => Storage::disk('public')->url($keep), 'name' => 'keep.pdf', 'size' => 4, 'mime' => 'application/pdf'],
                ['url' => Storage::disk('public')->url($drop), 'name' => 'drop.pdf', 'size' => 4, 'mime' => 'application/pdf'],
            ],
        ]);

        $this->actingAs($user)
            ->post("/api/v1/posts/{$post->id}", [
                '_method' => 'PUT',
                'title' => 'Updated title',
                'status' => 'draft',
                'existing_media_files' => json_encode([
                    'url' => Storage::disk('public')->url($keep),
                    'name' => 'keep.pdf',
                    'size' => 4,
                    'mime' => 'application/pdf',
                ]),
            ])
            ->assertOk()
            ->assertJsonPath('post.title', 'Updated title');

        Storage::disk('public')->assertExists($keep);
        Storage::disk('public')->assertMissing($drop);
        $this->assertCount(1, $post->fresh()->media_files);
    }

    public function test_deleting_post_deletes_stored_media(): void
    {
        Storage::fake('public');
        $user = $this->actingUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $path = 'post-media/hero.jpg';
        Storage::disk('public')->put($path, 'image');
        $post->update([
            'media_files' => [
                ['url' => Storage::disk('public')->url($path), 'name' => 'hero.jpg', 'size' => 5, 'mime' => 'image/jpeg'],
            ],
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/posts/{$post->id}")
            ->assertOk();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
