<?php

use App\Models\Car;
use App\Models\Garage;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function intakeStaff(): User
{
    return User::factory()->create(['role' => 'mechanic', 'garage_id' => Garage::factory()->create()->id]);
}

function intakeData(): array
{
    return ['name' => 'Test customer', 'phone' => '01012345678', 'plate_number' => 'TEST-123',
        'make' => 'Toyota', 'model' => 'Corolla', 'year' => 2020];
}

it('creates private intake without creating accounts cars or access grants', function () {
    $staff = intakeStaff();
    Sanctum::actingAs($staff);
    $users = User::count();
    $result = $this->postJson('/api/workshop/customers', intakeData())->assertCreated();
    expect(DB::table('workshop_customers')->value('claim_token_hash'))->toBe(hash('sha256', $result->json('claim_token')));
    $this->assertDatabaseCount('users', $users);
    $this->assertDatabaseCount('cars', 0);
    $this->assertDatabaseCount('car_garage_access', 0);
    $this->getJson('/api/workshop/customers')->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.claim_token_hash');
    Sanctum::actingAs(intakeStaff());
    $this->getJson('/api/workshop/customers?search=Test')->assertJsonCount(0, 'data');
    $this->postJson('/api/workshop/customers/'.$result->json('id').'/claim-token')->assertNotFound();
});

it('links only an owners matching vehicle and grants workshop permission once', function () {
    $staff = intakeStaff();
    Sanctum::actingAs($staff);
    $result = $this->postJson('/api/workshop/customers', intakeData())->assertCreated();
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id, 'plate_number' => 'TEST-123']);
    Sanctum::actingAs($owner);
    $payload = ['token' => $result->json('claim_token'), 'car_id' => $car->id];
    $this->postJson('/api/customer-links/claim', $payload)->assertOk();
    expect($car->allowsGarage($staff->garage_id))->toBeTrue();
    $this->postJson('/api/customer-links/claim', $payload)->assertUnprocessable();
    $this->assertDatabaseHas('workshop_customers', ['claimed_by' => $owner->id, 'claimed_car_id' => $car->id]);
});

it('does not link another owners car or a mismatched plate', function () {
    $staff = intakeStaff();
    Sanctum::actingAs($staff);
    $token = $this->postJson('/api/workshop/customers', intakeData())->json('claim_token');
    $otherCar = Car::factory()->create(['plate_number' => 'TEST-123']);
    $owner = User::factory()->create(['role' => 'owner']);
    $ownCar = Car::factory()->create(['user_id' => $owner->id, 'plate_number' => 'OTHER-123']);
    Sanctum::actingAs($owner);
    $this->postJson('/api/customer-links/claim', ['token' => $token, 'car_id' => $otherCar->id])->assertNotFound();
    $this->postJson('/api/customer-links/claim', ['token' => $token, 'car_id' => $ownCar->id])->assertUnprocessable();
    $this->assertDatabaseCount('car_garage_access', 0);
});

it('expires and rotates customer linking tokens', function () {
    $staff = intakeStaff();
    Sanctum::actingAs($staff);
    $result = $this->postJson('/api/workshop/customers', intakeData());
    $oldToken = $result->json('claim_token');
    DB::table('workshop_customers')->update(['claim_expires_at' => now()->subMinute()]);
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id, 'plate_number' => 'TEST-123']);
    Sanctum::actingAs($owner);
    $this->postJson('/api/customer-links/claim', ['token' => $oldToken, 'car_id' => $car->id])->assertUnprocessable();
    Sanctum::actingAs($staff);
    $newToken = $this->postJson('/api/workshop/customers/'.$result->json('id').'/claim-token')->assertOk()->json('claim_token');
    Sanctum::actingAs($owner);
    $this->postJson('/api/customer-links/claim', ['token' => $oldToken, 'car_id' => $car->id])->assertUnprocessable();
    $this->postJson('/api/customer-links/claim', ['token' => $newToken, 'car_id' => $car->id])->assertOk();
    Sanctum::actingAs($staff);
    $this->postJson('/api/workshop/customers/'.$result->json('id').'/claim-token')->assertConflict();
});

it('prevents owners and unassigned mechanics from reading workshop customers', function () {
    Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
    $this->getJson('/api/workshop/customers')->assertForbidden();
    $this->postJson('/api/workshop/customers', intakeData())->assertForbidden();
    Sanctum::actingAs(User::factory()->create(['role' => 'mechanic', 'garage_id' => null]));
    $this->getJson('/api/workshop/customers')->assertForbidden();
});

it('lists current and revoked grants only for the vehicle owner', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id]);
    $garage = Garage::factory()->create();
    Sanctum::actingAs($owner);
    $this->postJson("/api/cars/{$car->id}/garage-access/{$garage->id}")->assertOk();
    $this->getJson("/api/cars/{$car->id}/garage-access")->assertJsonPath('data.0.revoked_at', null);
    $this->deleteJson("/api/cars/{$car->id}/garage-access/{$garage->id}")->assertOk();
    expect($this->getJson("/api/cars/{$car->id}/garage-access")->json('data.0.revoked_at'))->not->toBeNull();
    Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
    $this->getJson("/api/cars/{$car->id}/garage-access")->assertForbidden();
});

it('renders escaped receipt details with correct totals for owner and same workshop staff', function () {
    $staff = intakeStaff();
    $car = Car::factory()->create();
    $record = ServiceRecord::factory()->create(['car_id' => $car->id, 'garage_id' => $staff->garage_id,
        'mechanic_id' => $staff->id, 'cost' => 100, 'description' => '<script>alert(1)</script>']);
    $record->parts()->create(['part_name' => 'Oil filter', 'quantity' => 2, 'price' => 75]);
    Sanctum::actingAs($staff);
    $html = $this->getJson("/api/service-records/{$record->id}/receipt")->assertOk()->json('html');
    expect($html)->toContain('250.00')->toContain('Oil filter')->toContain('&lt;script&gt;')->not->toContain('<script>alert');
    Sanctum::actingAs($car->owner);
    $this->getJson("/api/service-records/{$record->id}/receipt")->assertOk();
    Sanctum::actingAs(intakeStaff());
    $this->getJson("/api/service-records/{$record->id}/receipt")->assertForbidden();
    Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
    $this->getJson("/api/service-records/{$record->id}/receipt")->assertForbidden();
});

it('isolates workshop service lists', function () {
    $staff = intakeStaff();
    ServiceRecord::factory()->create(['garage_id' => $staff->garage_id, 'mechanic_id' => $staff->id]);
    ServiceRecord::factory()->create();
    Sanctum::actingAs($staff);
    $this->getJson('/api/workshop/services')->assertJsonPath('meta.total', 1)->assertJsonStructure(['data' => [['vehicle' => ['plate_number']]]]);
});

it('previews the target workshop without granting access or exposing customer contact data', function () {
    $staff = intakeStaff();
    Sanctum::actingAs($staff);
    $result = $this->postJson('/api/workshop/customers', intakeData())->assertCreated();
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id, 'plate_number' => 'TEST-123']);
    Sanctum::actingAs($owner);
    $this->postJson('/api/customer-links/preview', ['token' => $result->json('claim_token'), 'car_id' => $car->id])
        ->assertOk()->assertJsonPath('garage.name', $staff->garage->name)
        ->assertJsonPath('plate_number', 'TEST-123')->assertJsonMissingPath('phone')->assertJsonMissingPath('name');
    $this->assertDatabaseCount('car_garage_access', 0);
    $this->assertDatabaseHas('workshop_customers', ['id' => $result->json('id'), 'claimed_at' => null]);
});

it('rejects linking previews for another owners car and invalid tokens', function () {
    $staff = intakeStaff();
    Sanctum::actingAs($staff);
    $token = $this->postJson('/api/workshop/customers', intakeData())->json('claim_token');
    $car = Car::factory()->create(['plate_number' => 'TEST-123']);
    Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
    $this->postJson('/api/customer-links/preview', ['token' => $token, 'car_id' => $car->id])->assertNotFound();
    $this->postJson('/api/customer-links/preview', ['token' => 'invalid', 'car_id' => $car->id])->assertUnprocessable();
});
