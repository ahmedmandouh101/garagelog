<?php

use App\Models\ServiceRecord;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('rejects unexpected production hosts', function () {
    app()->instance('env', 'production');
    config(['app.url' => 'https://garage.test']);
    $this->get('https://untrusted.test/')->assertStatus(400);
});

it('sets hsts on the canonical secure production origin', function () {
    app()->instance('env', 'production');
    config(['app.url' => 'https://garage.test']);
    $this->get('https://garage.test/')->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('sets browser security and private API response headers', function () {
    $this->get('/')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    Sanctum::actingAs(User::factory()->create());
    $response = $this->getJson('/api/me')->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    expect($response->headers->get('Content-Security-Policy'))->toContain("script-src 'self'");
});

it('refuses default admin seeding in production', function () {
    app()->instance('env', 'production');
    expect(fn () => (new AdminSeeder)->run())->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('users', 0);
});

it('reports an unsafe deployment without displaying secrets', function () {
    config(['app.debug' => true, 'app.key' => 'never-print-this-secret']);
    $this->artisan('app:production-check')->expectsOutput('FAIL Debug disabled')->assertFailed();
});

it('requires interactive creation of production admin accounts', function () {
    $this->artisan('app:create-admin --no-interaction')->assertFailed();
    $this->assertDatabaseCount('users', 0);
});

it('keeps recorded services when an administrator requests deletion', function () {
    $record = ServiceRecord::factory()->create();
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->deleteJson("/api/cars/{$record->car_id}/service-records/{$record->id}")->assertConflict();
    $this->assertDatabaseHas('service_records', ['id' => $record->id]);
});

it('records revisions including before and after part details in the same transaction', function () {
    $record = ServiceRecord::factory()->create();
    $mechanic = $record->mechanic;
    $mechanic->update(['garage_id' => $record->garage_id]);
    DB::table('car_garage_access')->insert(['car_id' => $record->car_id, 'garage_id' => $record->garage_id,
        'owner_id' => $record->car->user_id, 'granted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    $record->parts()->create(['part_name' => 'Original', 'quantity' => 1, 'price' => 20]);
    Sanctum::actingAs($mechanic);
    $this->putJson("/api/cars/{$record->car_id}/service-records/{$record->id}", ['description' => 'Corrected',
        'parts' => [['part_name' => 'Replacement', 'quantity' => 2, 'price' => 30]]])->assertOk();
    $audit = DB::table('service_audits')->first();
    expect($audit->actor_id)->toBe($mechanic->id);
    expect(json_decode($audit->before_data, true)['parts'][0]['part_name'])->toBe('Original');
    expect(json_decode($audit->after_data, true)['parts'][0]['part_name'])->toBe('Replacement');
});
