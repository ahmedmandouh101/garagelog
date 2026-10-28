<?php

use App\Models\Car;
use App\Models\Garage;
use App\Models\ServicePart;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function serviceFixture(): array
{
    $garage = Garage::factory()->create();
    $mechanic = User::factory()->create(['role' => 'mechanic', 'garage_id' => $garage->id]);
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id]);
    $record = ServiceRecord::factory()->create([
        'car_id' => $car->id, 'garage_id' => $garage->id, 'mechanic_id' => $mechanic->id,
    ]);

    return [$owner, $car, $mechanic, $record];
}

it('allows the recording mechanic to view and update their service', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    Sanctum::actingAs($mechanic);
    $url = "/api/cars/{$car->id}/service-records/{$record->id}";
    $this->getJson($url)->assertOk()->assertJsonPath('data.id', $record->id);
    $this->putJson($url, ['description' => 'Corrected description'])->assertOk();
    expect($record->fresh()->description)->toBe('Corrected description');
});

it('rejects another mechanic even within the same garage', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    Sanctum::actingAs(User::factory()->create(['role' => 'mechanic', 'garage_id' => $mechanic->garage_id]));
    $url = "/api/cars/{$car->id}/service-records/{$record->id}";
    $this->getJson($url)->assertForbidden();
    $this->putJson($url, ['description' => 'Unauthorized'])->assertForbidden();
});

it('rejects service ids nested under the wrong car', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    $otherCar = Car::factory()->create(['user_id' => $owner->id]);
    Sanctum::actingAs($mechanic);
    $url = "/api/cars/{$otherCar->id}/service-records/{$record->id}";
    $this->getJson($url)->assertNotFound();
    $this->putJson($url, ['description' => 'Wrong car'])->assertNotFound();
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->deleteJson($url)->assertNotFound();
    $this->assertDatabaseHas('service_records', ['id' => $record->id]);
});

it('rejects reviews of another cars service using an owned car url', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    $otherOwner = User::factory()->create(['role' => 'owner']);
    $otherCar = Car::factory()->create(['user_id' => $otherOwner->id]);
    Sanctum::actingAs($otherOwner);
    $url = "/api/cars/{$otherCar->id}/service-records/{$record->id}/reviews";
    $this->postJson($url, ['rating' => 5])->assertForbidden();
    $this->getJson($url)->assertNotFound();
    $this->assertDatabaseCount('reviews', 0);
});

it('rejects creating a service for a garage the mechanic does not belong to', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    Sanctum::actingAs($mechanic);
    $this->postJson("/api/cars/{$car->id}/service-records", [
        'garage_id' => Garage::factory()->create()->id,
        'service_type' => 'Oil Change', 'mileage_at_service' => 50000,
        'service_date' => now()->toDateString(),
    ])->assertForbidden();
    $this->assertDatabaseCount('service_records', 1);
});

it('replaces parts atomically and calculates the total', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    $record->parts()->create(['part_name' => 'Old', 'quantity' => 1, 'price' => 10]);
    Sanctum::actingAs($mechanic);
    $url = "/api/cars/{$car->id}/service-records/{$record->id}";
    $this->putJson($url, ['cost' => 100, 'parts' => [
        ['part_name' => 'Filter', 'quantity' => 2, 'price' => 50],
    ]])->assertOk()->assertJsonPath('record.total_cost', 200);
    $this->assertDatabaseMissing('service_parts', ['part_name' => 'Old']);
    $this->putJson($url, ['parts' => null])->assertUnprocessable();
    $this->assertDatabaseHas('service_parts', ['part_name' => 'Filter']);
});

it('preserves car and garage history when deletion is requested', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    Sanctum::actingAs($owner);
    $this->deleteJson("/api/cars/{$car->id}")->assertConflict();
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->deleteJson("/api/garages/{$record->garage_id}")->assertConflict();
    $this->assertDatabaseHas('service_records', ['id' => $record->id]);
});

it('rolls back record changes and old parts if replacement fails', function () {
    [$owner, $car, $mechanic, $record] = serviceFixture();
    $record->parts()->create(['part_name' => 'Original', 'quantity' => 1, 'price' => 10]);
    Sanctum::actingAs($mechanic);
    ServicePart::creating(function () {
        throw new RuntimeException('Simulated storage failure');
    });
    $this->putJson("/api/cars/{$car->id}/service-records/{$record->id}", [
        'description' => 'Should roll back',
        'parts' => [['part_name' => 'Replacement', 'quantity' => 1, 'price' => 20]],
    ])->assertStatus(500);
    expect($record->fresh()->description)->toBe($record->description);
    $this->assertDatabaseHas('service_parts', ['service_record_id' => $record->id, 'part_name' => 'Original']);
});
