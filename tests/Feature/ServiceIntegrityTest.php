<?php

use App\Models\Car;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function integrityFixture(): array
{
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id, 'mileage' => 10000]);
    $garage = Garage::factory()->create();
    $mechanic = User::factory()->create(['role' => 'mechanic', 'garage_id' => $garage->id]);
    DB::table('car_garage_access')->insert(['owner_id' => $owner->id, 'car_id' => $car->id, 'garage_id' => $garage->id, 'granted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    Sanctum::actingAs($mechanic);

    return [$owner, $car, $mechanic, ['garage_id' => $garage->id, 'service_type' => 'Oil Change',
        'service_date' => today('Africa/Cairo')->toDateString(), 'mileage_at_service' => 11000, 'cost' => 150]];
}

it('rejects future dates invalid next mileage and sub-cent prices without saving', function () {
    [$owner, $car, $mechanic, $data] = integrityFixture();
    $url = "/api/cars/{$car->id}/service-records";
    $this->postJson($url, [...$data, 'service_date' => today('Africa/Cairo')->addDay()->toDateString()])->assertUnprocessable();
    $this->postJson($url, [...$data, 'next_service_mileage' => 11000])->assertUnprocessable();
    $this->postJson($url, [...$data, 'cost' => null])->assertUnprocessable();
    $this->postJson($url, [...$data, 'parts' => [['part_name' => 'Filter', 'quantity' => 1, 'price' => 1.234]]])->assertUnprocessable();
    $this->assertDatabaseCount('service_records', 0);
});

it('keeps historical mileage between earlier and later services and raises current mileage safely', function () {
    [$owner, $car, $mechanic, $data] = integrityFixture();
    $url = "/api/cars/{$car->id}/service-records";
    $this->postJson($url, [...$data, 'service_date' => today()->subDays(10)->toDateString(), 'mileage_at_service' => 12000])->assertCreated();
    $this->postJson($url, [...$data, 'mileage_at_service' => 15000])->assertCreated();
    $middle = [...$data, 'service_date' => today()->subDays(5)->toDateString()];
    $this->postJson($url, [...$middle, 'mileage_at_service' => 11999])->assertUnprocessable();
    $this->postJson($url, [...$middle, 'mileage_at_service' => 15001])->assertUnprocessable();
    $this->postJson($url, [...$middle, 'mileage_at_service' => 13000])->assertCreated();
    expect($car->fresh()->mileage)->toBe(15000);
});

it('checks retained next-service fields during partial updates', function () {
    [$owner, $car, $mechanic, $data] = integrityFixture();
    $url = "/api/cars/{$car->id}/service-records";
    $result = $this->postJson($url, [...$data, 'service_date' => today()->subDays(10)->toDateString(),
        'next_service_date' => today()->subDays(2)->toDateString(), 'next_service_mileage' => 12000])->assertCreated();
    $id = $result->json('record.id');
    $this->putJson("{$url}/{$id}", ['service_date' => today()->toDateString()])->assertUnprocessable();
    $this->putJson("{$url}/{$id}", ['mileage_at_service' => 12001])->assertUnprocessable();
    $this->putJson("{$url}/{$id}", ['mileage_at_service' => 12001, 'next_service_mileage' => null])->assertOk();
});

it('prevents owner odometer rollback and accepts legitimate increases', function () {
    [$owner, $car, $mechanic, $data] = integrityFixture();
    $this->postJson("/api/cars/{$car->id}/service-records", [...$data, 'mileage_at_service' => 20000])->assertCreated();
    Sanctum::actingAs($owner);
    $this->putJson("/api/cars/{$car->id}", ['mileage' => 19999])->assertUnprocessable();
    $this->putJson("/api/cars/{$car->id}", ['mileage' => null])->assertUnprocessable();
    $this->putJson("/api/cars/{$car->id}", ['mileage' => 21000])->assertOk();
    expect($car->fresh()->mileage)->toBe(21000);
});

it('validates edits against neighboring service records', function () {
    [$owner, $car, $mechanic, $data] = integrityFixture();
    $url = "/api/cars/{$car->id}/service-records";
    $first = $this->postJson($url, [...$data, 'service_date' => today()->subDays(5)->toDateString()])->assertCreated()->json('record.id');
    $this->postJson($url, [...$data, 'mileage_at_service' => 12000])->assertCreated();
    $this->putJson("{$url}/{$first}", ['mileage_at_service' => 13000])->assertUnprocessable();
    $this->assertDatabaseHas('service_records', ['id' => $first, 'mileage_at_service' => 11000]);
});
