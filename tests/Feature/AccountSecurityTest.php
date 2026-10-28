<?php

use App\Models\User;
use App\Notifications\ResetAccountPassword;
use App\Notifications\VerifyAccountEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('gates workspace data while allowing unverified users to inspect their account and sign out', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $token = $user->createToken('test')->plainTextToken;
    $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('data.email_verified', false);
    $this->withToken($token)->getJson('/api/cars')->assertForbidden();
    $this->withToken($token)->postJson('/api/logout')->assertOk();
});

it('sends a signed verification link and verifies the intended account only', function () {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user);
    $this->postJson('/api/email/verification-notification')->assertOk();
    Notification::assertSentTo($user, VerifyAccountEmail::class, function ($notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;
        $this->get($url)->assertOk()->assertSee('بريدك الإلكتروني اتأكد');

        return true;
    });
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Sanctum::actingAs($user->fresh());
    $this->getJson('/api/cars')->assertOk();
});

it('rejects modified and expired verification links', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)], absolute: false);
    $this->get($url.'&changed=1')->assertForbidden();
    $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)], absolute: false);
    $this->get($expired)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects a signed verification URL whose email hash does not match', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.test')], absolute: false);
    $this->get($url)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('limits verification resends and does not send again for verified accounts', function () {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user);
    $this->postJson('/api/email/verification-notification')->assertOk();
    $this->postJson('/api/email/verification-notification')->assertStatus(429);
    Notification::assertSentToTimes($user, VerifyAccountEmail::class, 1);
});

it('uses the same recovery response for known and unknown emails without exposing tokens', function () {
    Notification::fake();
    $user = User::factory()->create();
    $known = $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk()->json();
    $unknown = $this->postJson('/api/forgot-password', ['email' => 'missing@example.test'])->assertOk()->json();
    expect($known)->toBe($unknown)->not->toHaveKey('token');
    Notification::assertSentTo($user, ResetAccountPassword::class, function ($notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;
        expect(parse_url($url, PHP_URL_QUERY))->toBeNull();
        expect(parse_url($url, PHP_URL_FRAGMENT))->toContain('reset_token=');

        return true;
    });
});

it('resets the password once and revokes all api tokens and database sessions', function () {
    $user = User::factory()->create();
    $user->createToken('first');
    $user->createToken('second');
    DB::table('sessions')->insert(['id' => 'test-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    $token = Password::createToken($user);
    $data = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'];
    $this->postJson('/api/reset-password', $data)->assertOk();
    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
    $this->assertDatabaseMissing('sessions', ['id' => 'test-session']);
    $this->postJson('/api/reset-password', $data)->assertUnprocessable();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertUnprocessable();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'NewPassword123!'])->assertOk();
});

it('rejects expired recovery tokens and mismatched confirmation without changing the password', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $data = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123!', 'password_confirmation' => 'Mismatch123!'];
    $this->postJson('/api/reset-password', $data)->assertUnprocessable();
    DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subHours(2)]);
    $data['password_confirmation'] = $data['password'];
    $this->postJson('/api/reset-password', $data)->assertUnprocessable();
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('does not verify an email as a side effect of password recovery', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $token = Password::createToken($user);
    $this->postJson('/api/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!'])->assertOk();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('issues login tokens with a finite lifetime', function () {
    $user = User::factory()->create();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    expect($user->tokens()->first()->expires_at)->not->toBeNull();
});

it('rejects expired bearer tokens', function () {
    $user = User::factory()->create();
    $token = $user->createToken('expired', ['*'], now()->subMinute())->plainTextToken;
    $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
});

it('keeps newly registered accounts unverified until they follow the email link', function () {
    $response = $this->postJson('/api/register', ['name' => 'New account', 'email' => 'new@example.test',
        'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!', 'role' => 'owner',
        'email_verified_at' => now(), 'is_garage_manager' => true]);
    $response->assertCreated()->assertJsonPath('user.email_verified', false);
    $user = User::where('email', 'new@example.test')->firstOrFail();
    expect($user->hasVerifiedEmail())->toBeFalse();
    expect($user->is_garage_manager)->toBeFalse();
});

it('uses the configured application origin in verification links instead of the request host', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    URL::forceRootUrl('https://untrusted.example.test');
    $url = (new VerifyAccountEmail)->toMail($user)->actionUrl;
    expect($url)->toStartWith(rtrim(config('app.url'), '/').'/verify-email/');
    expect($url)->not->toContain('untrusted.example.test');
});
