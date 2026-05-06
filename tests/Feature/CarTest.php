<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithToken(): array
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $token = $owner->createToken('auth_token')->plainTextToken;
        return [$owner, $token];
    }

    public function test_owner_can_create_car(): void
    {
        [$owner, $token] = $this->ownerWithToken();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/cars', [
                'make'         => 'Toyota',
                'model'        => 'Corolla',
                'year'         => 2020,
                'plate_number' => 'ABC-1234',
                'color'        => 'White',
                'mileage'      => 45000,
            ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'car' => ['id', 'make', 'model', 'year', 'plate_number'],
                 ]);
    }

    public function test_mechanic_cannot_create_car(): void
    {
        $mechanic = User::factory()->create(['role' => 'mechanic']);
        $token    = $mechanic->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/cars', [
                'make'         => 'Toyota',
                'model'        => 'Corolla',
                'year'         => 2020,
                'plate_number' => 'ABC-1234',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_list_his_cars(): void
    {
        [$owner, $token] = $this->ownerWithToken();

        Car::factory()->count(3)->create(['user_id' => $owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/cars');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    public function test_owner_cannot_see_other_owners_cars(): void
    {
        [$owner, $token] = $this->ownerWithToken();
        $otherOwner      = User::factory()->create(['role' => 'owner']);

        Car::factory()->count(2)->create(['user_id' => $otherOwner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/cars');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
    }

    public function test_owner_can_delete_his_car(): void
    {
        [$owner, $token] = $this->ownerWithToken();
        $car             = Car::factory()->create(['user_id' => $owner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/cars/{$car->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cars', ['id' => $car->id]);
    }

    public function test_owner_cannot_delete_other_owners_car(): void
    {
        [$owner, $token] = $this->ownerWithToken();
        $otherOwner      = User::factory()->create(['role' => 'owner']);
        $car             = Car::factory()->create(['user_id' => $otherOwner->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/cars/{$car->id}");

        $response->assertStatus(403);
    }
}
