<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Ahmed Test',
            'email'                 => 'ahmed@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'owner',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'token',
                     'user' => ['id', 'name', 'email', 'role'],
                 ]);
    }

    public function test_user_cannot_register_with_invalid_role(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Ahmed Test',
            'email'                 => 'ahmed@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'superadmin',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'ahmed@test.com',
            'password' => bcrypt('password123'),
            'role'     => 'owner',
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'ahmed@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'token', 'user']);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'ahmed@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'ahmed@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully']);
    }
}
