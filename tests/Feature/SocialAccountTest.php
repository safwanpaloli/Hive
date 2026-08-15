<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialAccountTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();

        return $user;
    }

    public function test_user_can_list_own_accounts(): void
    {
        $user = $this->actingUser();
        SocialAccount::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/social-accounts')
            ->assertOk()
            ->assertJsonCount(3, 'accounts');
    }

    public function test_user_can_create_account(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->postJson('/api/v1/social-accounts', [
                'platform_name' => 'Instagram',
                'handle' => '@safwan',
                'profile_url' => 'https://instagram.com/safwan',
                'account_type' => 'Creator',
                'notes' => 'Primary account',
            ])
            ->assertCreated()
            ->assertJsonPath('account.platform_name', 'Instagram');

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'handle' => '@safwan',
        ]);
    }

    public function test_creating_account_requires_platform_and_handle(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)
            ->postJson('/api/v1/social-accounts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['platform_name', 'handle']);
    }

    public function test_user_can_update_own_account(): void
    {
        $user = $this->actingUser();
        $account = SocialAccount::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson("/api/v1/social-accounts/{$account->id}", [
                'platform_name' => 'LinkedIn',
                'handle' => 'safwan-updated',
            ])
            ->assertOk()
            ->assertJsonPath('account.handle', 'safwan-updated');
    }

    public function test_user_cannot_modify_another_users_account(): void
    {
        $owner = $this->actingUser();
        $intruder = $this->actingUser();
        $account = SocialAccount::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->putJson("/api/v1/social-accounts/{$account->id}", [
                'platform_name' => 'Hacked',
                'handle' => 'hacked',
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->deleteJson("/api/v1/social-accounts/{$account->id}")
            ->assertForbidden();
    }

    public function test_user_can_delete_own_account(): void
    {
        $user = $this->actingUser();
        $account = SocialAccount::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/social-accounts/{$account->id}")
            ->assertOk();

        $this->assertDatabaseMissing('social_accounts', ['id' => $account->id]);
    }

    public function test_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');

        $user = $this->actingUser();

        $response = $this->actingAs($user)
            ->post('/api/v1/social-accounts', [
                'platform_name' => 'Instagram',
                'handle' => '@avatar-test',
                'avatar' => UploadedFile::fake()->image('avatar.png', 200, 200),
            ], ['Accept' => 'application/json']);

        $response->assertCreated();

        $avatarUrl = $response->json('account.avatar_url');
        $this->assertNotNull($avatarUrl);
        $this->assertStringContainsString('/storage/avatars/', $avatarUrl);

        $path = str_replace('/storage/', '', parse_url($avatarUrl, PHP_URL_PATH));
        Storage::disk('public')->assertExists($path);
    }

    public function test_deleting_account_removes_its_avatar_file(): void
    {
        Storage::fake('public');

        $user = $this->actingUser();
        $path = UploadedFile::fake()->image('avatar.png')->store('avatars', 'public');
        $account = SocialAccount::factory()->create([
            'user_id' => $user->id,
            'avatar_url' => Storage::disk('public')->url($path),
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/social-accounts/{$account->id}")->assertOk();

        Storage::disk('public')->assertMissing($path);
    }
}
