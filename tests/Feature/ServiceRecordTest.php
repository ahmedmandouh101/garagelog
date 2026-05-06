<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Garage;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRecordTest extends TestCase
{
    use RefreshDatabase;

    private function mechanic(): array
    {
        $garage   = Garage::factory()->create();
        $mechanic = User::factory()->create([
            'role'       => 'mechanic',
            'garage_id'  => $garage->id,
        ]);
        $token = $mechanic->createToken('auth_token')->plainTextToken;
        return [$mechanic, $token, $garage];
    }

    private function ownerWithCar(): array
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $car   = Car::factory()->create(['user_id' => $owner->id]);
        $token = $owner->createToken('auth_token')->plainTextToken;
        return [$owner, $car, $token];
    }

    public function test_mechanic_can_create_service_record(): void
    {
        [$mechanic, $mechanicToken, $garage] = $this->mechanic();
        [$owner, $car, $ownerToken]          = $this->ownerWithCar();

        $response = $this->withHeader('Authorization', 'Bearer ' . $mechanicToken)
            ->postJson("/api/cars/{$car->id}/service-records", [
                'garage_id'          => $garage->id,
                'service_type'       => 'Oil Change',
                'description'        => 'Changed engine oil',
                'mileage_at_service' => 50000,
                'cost'               => 350.00,
                'service_date'       => '2026-05-06',
            ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'record' => ['id', 'service_type', 'cost', 'mechanic', 'garage'],
                 ]);
    }

    public function test_owner_cannot_create_service_record(): void
    {
        [$owner, $car, $ownerToken] = $this->ownerWithCar();
        [$mechanic, $mechanicToken, $garage] = $this->mechanic();

        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->postJson("/api/cars/{$car->id}/service-records", [
                'garage_id'          => $garage->id,
                'service_type'       => 'Oil Change',
                'mileage_at_service' => 50000,
                'cost'               => 350.00,
                'service_date'       => '2026-05-06',
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_view_his_car_service_records(): void
    {
        [$owner, $car, $ownerToken] = $this->ownerWithCar();
        [$mechanic, $mechanicToken, $garage] = $this->mechanic();

        ServiceRecord::factory()->count(3)->create([
            'car_id'      => $car->id,
            'garage_id'   => $garage->id,
            'mechanic_id' => $mechanic->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->getJson("/api/cars/{$car->id}/service-records");

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('total'));
    }

    public function test_service_record_filter_by_service_type(): void
    {
        [$owner, $car, $ownerToken] = $this->ownerWithCar();
        [$mechanic, $mechanicToken, $garage] = $this->mechanic();

        ServiceRecord::factory()->create([
            'car_id'       => $car->id,
            'garage_id'    => $garage->id,
            'mechanic_id'  => $mechanic->id,
            'service_type' => 'Oil Change',
        ]);

        ServiceRecord::factory()->create([
            'car_id'       => $car->id,
            'garage_id'    => $garage->id,
            'mechanic_id'  => $mechanic->id,
            'service_type' => 'Brake Check',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->getJson("/api/cars/{$car->id}/service-records?service_type=Oil");

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('total'));
    }
}
