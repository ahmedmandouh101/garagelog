<?php

use App\Models\Car;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function workshopManager(): User
{
    $manager = User::factory()->create(['role' => 'mechanic', 'garage_id' => Garage::factory()->create()->id]);
    $manager->forceFill(['is_garage_manager' => true])->save();

    return $manager;
}

it('lets only admins appoint managers and prevents cross-workshop moves', function () {
    $garage = Garage::factory()->create();
    $mechanic = User::factory()->create(['role' => 'mechanic']);
    Sanctum::actingAs($mechanic);
    $this->putJson("/api/garages/{$garage->id}/manager", ['user_id' => $mechanic->id])->assertForbidden();
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->putJson("/api/garages/{$garage->id}/manager", ['user_id' => $mechanic->id])->assertOk();
    expect($mechanic->fresh()->managesGarage())->toBeTrue();
    $other = Garage::factory()->create();
    $this->putJson("/api/garages/{$other->id}/manager", ['user_id' => $mechanic->id])->assertConflict();
});

it('accepts a targeted invitation only once and stores only its hash', function () {
    $manager = workshopManager();
    $staff = User::factory()->create(['role' => 'mechanic']);
    Sanctum::actingAs($manager);
    $token = $this->postJson('/api/workshop/invitations', ['email' => $staff->email])->assertCreated()->json('invitation_token');
    expect(DB::table('garage_invitations')->value('token_hash'))->toBe(hash('sha256', $token));
    Sanctum::actingAs(User::factory()->create(['role' => 'mechanic']));
    $this->postJson('/api/workshop/invitations/accept', ['token' => $token])->assertForbidden();
    Sanctum::actingAs($staff);
    $this->postJson('/api/workshop/invitations/accept', ['token' => $token])->assertOk();
    $this->postJson('/api/workshop/invitations/accept', ['token' => $token])->assertUnprocessable();
    expect($staff->fresh()->garage_id)->toBe($manager->garage_id);
    expect($staff->fresh()->is_garage_manager)->toBeFalse();
});

it('rejects expired invitations and employee access to manager actions', function () {
    $manager = workshopManager();
    $staff = User::factory()->create(['role' => 'mechanic']);
    Sanctum::actingAs($manager);
    $token = $this->postJson('/api/workshop/invitations', ['email' => $staff->email])->json('invitation_token');
    DB::table('garage_invitations')->update(['expires_at' => now()->subMinute()]);
    Sanctum::actingAs($staff);
    $this->postJson('/api/workshop/invitations/accept', ['token' => $token])->assertUnprocessable();
    $this->getJson('/api/workshop/dashboard')->assertForbidden();
    $this->getJson('/api/workshop/staff')->assertForbidden();
    $this->postJson('/api/workshop/invitations', ['email' => 'test@example.com'])->assertForbidden();
});

it('requires owner consent before recording services and honors revocation', function () {
    $manager = workshopManager();
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id]);
    $url = "/api/cars/{$car->id}/garage-access/{$manager->garage_id}";
    $serviceUrl = "/api/cars/{$car->id}/service-records";
    $payload = ['garage_id' => $manager->garage_id, 'service_type' => 'Oil Change',
        'service_date' => today()->toDateString(), 'mileage_at_service' => 50000];
    Sanctum::actingAs($manager);
    $this->postJson($serviceUrl, $payload)->assertForbidden();
    $this->postJson($url)->assertForbidden();
    $this->getJson('/api/workshop/cars')->assertJsonPath('meta.total', 0);
    Sanctum::actingAs($owner);
    $this->postJson($url)->assertOk();
    Sanctum::actingAs($manager);
    $this->getJson('/api/workshop/cars')->assertJsonPath('meta.total', 1);
    $record = $this->postJson($serviceUrl, $payload)->assertCreated()->json('record.id');
    $this->getJson('/api/workshop/dashboard')->assertJsonPath('services_today', 1);
    Sanctum::actingAs($owner);
    $this->deleteJson($url)->assertOk();
    Sanctum::actingAs($manager);
    $this->postJson($serviceUrl, $payload)->assertForbidden();
    $this->putJson("{$serviceUrl}/{$record}", ['description' => 'revoked'])->assertForbidden();
    $this->getJson('/api/workshop/cars')->assertJsonPath('meta.total', 0);
});

it('isolates workshop vehicles and staff removal', function () {
    $manager = workshopManager();
    $other = workshopManager();
    $staff = User::factory()->create(['role' => 'mechanic', 'garage_id' => $other->garage_id]);
    Sanctum::actingAs($manager);
    $this->deleteJson("/api/workshop/staff/{$staff->id}")->assertNotFound();
    $this->deleteJson("/api/workshop/staff/{$manager->id}")->assertConflict();
    $this->getJson('/api/workshop/staff')->assertJsonCount(1, 'data');
    Sanctum::actingAs($other);
    $this->deleteJson("/api/workshop/staff/{$staff->id}")->assertOk();
    Sanctum::actingAs($staff->fresh());
    $this->getJson('/api/workshop/cars')->assertForbidden();
});

it('does not allow extra part attributes to attach parts to another service', function () {
    $manager = workshopManager();
    $owner = User::factory()->create(['role' => 'owner']);
    $car = Car::factory()->create(['user_id' => $owner->id]);
    Sanctum::actingAs($owner);
    $this->postJson("/api/cars/{$car->id}/garage-access/{$manager->garage_id}")->assertOk();
    Sanctum::actingAs($manager);
    $this->postJson("/api/cars/{$car->id}/service-records", [
        'garage_id' => $manager->garage_id, 'service_type' => 'Oil',
        'service_date' => today()->toDateString(), 'mileage_at_service' => 100,
        'parts' => [['part_name' => 'Filter', 'quantity' => 1, 'price' => 20, 'service_record_id' => 999]],
    ])->assertUnprocessable();
    $this->assertDatabaseCount('service_records', 0);
});
