<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'safwanpaloli7@gmail.com',
            'password' => 'Safwanpaloli7@6960',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'safwanpaloli7@gmail.com',
            'password' => 'Safwanpaloli7@6960',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_protected_route_returns_401_without_token(): void
    {
        $this->getJson('/api/v1/posts/today')->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'Safwan']);

        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }
}
