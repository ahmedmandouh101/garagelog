<?php

use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('serves the Arabic workspace without a frontend build', function () {
    $this->get('/app')->assertOk()->assertSee('dir="rtl"', false)->assertSee('GarageLog');
});

it('shows the product and an isolated illustrative demo without authentication', function () {
    $this->get('/')->assertOk()->assertSee('العميل يرجع.')->assertSee('/demo', false);
    $this->get('/demo')->assertOk()->assertSee('بيانات توضيحية لا تُحفظ')->assertDontSee('owner@garagelog.example.test');
});

it('returns private workshop membership only to the signed in user', function () {
    $user = User::factory()->create(['role' => 'mechanic', 'garage_id' => Garage::factory()->create()->id]);
    $user->forceFill(['is_garage_manager' => true])->save();
    $this->getJson('/api/me')->assertUnauthorized();
    Sanctum::actingAs($user);
    $this->getJson('/api/me')->assertOk()->assertJsonPath('data.garage_id', $user->garage_id)
        ->assertJsonPath('data.is_garage_manager', true)->assertJsonPath('data.email', $user->email);
});

it('does not publish staff email or phone in public workshop profiles', function () {
    $garage = Garage::factory()->create();
    $user = User::factory()->create(['role' => 'mechanic', 'garage_id' => $garage->id, 'phone' => '01000001234']);
    $this->getJson('/api/garages/'.$garage->id)->assertOk()
        ->assertJsonMissingPath('data.mechanics.0.email')->assertJsonMissingPath('data.mechanics.0.phone');
});
