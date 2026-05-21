<?php

namespace App\Console\Commands;

use App\Mail\ServiceReminderMail;
use App\Models\Car;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendServiceReminders extends Command
{
    protected $signature   = 'reminders:send';
    protected $description = 'Send service reminder emails to car owners whose service is due in 7 days';

    public function handle()
    {
        $targetDate = now()->addDays(7)->toDateString();

        $cars = Car::whereHas('serviceRecords', function ($query) use ($targetDate) {
            $query->whereDate('next_service_date', $targetDate);
        })->with(['owner', 'serviceRecords.garage'])->get();

        if ($cars->isEmpty()) {
            $this->info('No reminders to send today.');
            return;
        }

        foreach ($cars as $car) {
            Mail::to($car->owner->email)->send(new ServiceReminderMail($car));
            $this->info("Reminder sent to: {$car->owner->email} for {$car->make} {$car->model}");
        }

        $this->info("Total reminders sent: {$cars->count()}");
    }
}
