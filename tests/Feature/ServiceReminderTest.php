<?php

use App\Mail\ServiceReminderMail;
use App\Models\ServiceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends the matching service once and catches missed days within the window', function () {
    Mail::fake();
    $record = ServiceRecord::factory()->create(['next_service_date' => today('Africa/Cairo')->addDays(3)]);
    $this->artisan('reminders:send')->assertSuccessful();
    $this->artisan('reminders:send')->assertSuccessful();
    Mail::assertSent(ServiceReminderMail::class, fn ($mail) => $mail->dueService->id === $record->id);
    Mail::assertSentCount(1);
    expect($record->fresh()->reminder_sent_for_date)->toBe($record->next_service_date->toDateString());
});

it('does not send reminders outside the window or for superseded services', function () {
    Mail::fake();
    $old = ServiceRecord::factory()->create([
        'service_type' => 'Oil Change', 'service_date' => today()->subMonths(2),
        'next_service_date' => today('Africa/Cairo')->addDays(3),
    ]);
    ServiceRecord::factory()->create([
        'car_id' => $old->car_id, 'service_type' => 'Oil Change',
        'service_date' => today()->subDay(), 'next_service_date' => today()->addMonths(3),
    ]);
    ServiceRecord::factory()->create(['next_service_date' => today()->addDays(9)]);
    $this->artisan('reminders:send')->assertSuccessful();
    Mail::assertNothingSent();
});

it('sends again when the due date is changed', function () {
    Mail::fake();
    $record = ServiceRecord::factory()->create(['next_service_date' => today('Africa/Cairo')->addDays(3)]);
    $this->artisan('reminders:send')->assertSuccessful();
    $record->update(['next_service_date' => today('Africa/Cairo')->addDays(6)]);
    $this->artisan('reminders:send')->assertSuccessful();
    Mail::assertSentCount(2);
});
