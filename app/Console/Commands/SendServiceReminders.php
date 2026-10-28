<?php

namespace App\Console\Commands;

use App\Mail\ServiceReminderMail;
use App\Models\ServiceRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendServiceReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send service reminders due in the next seven days, once per due date';

    public function handle(): int
    {
        $sent = 0;
        $failed = 0;
        ServiceRecord::query()
            ->whereDate('next_service_date', '>=', today('Africa/Cairo')->toDateString())
            ->whereDate('next_service_date', '<=', today('Africa/Cairo')->addDays(7)->toDateString())
            ->with(['car.owner', 'garage'])
            ->chunkById(100, function ($records) use (&$sent, &$failed) {
                foreach ($records as $record) {
                    $lock = Cache::lock('service-reminder:'.$record->id, 300);
                    if (! $lock->get()) {
                        continue;
                    }
                    try {
                        $record->refresh();
                        $due = $record->next_service_date?->toDateString();
                        if (! $due || $record->reminder_sent_for_date === $due) {
                            continue;
                        }
                        // A later service of the same type supersedes the old reminder.
                        $superseded = ServiceRecord::where('car_id', $record->car_id)
                            ->where('id', '!=', $record->id)
                            ->where('service_type', $record->service_type)
                            ->where(function ($query) use ($record) {
                                $query->whereDate('service_date', '>', $record->service_date)
                                    ->orWhere(function ($query) use ($record) {
                                        $query->whereDate('service_date', $record->service_date)
                                            ->where('id', '>', $record->id);
                                    });
                            })->exists();
                        if ($superseded || ! $record->car?->owner?->email) {
                            continue;
                        }
                        // Record success only after the mail transport accepts the message.
                        Mail::to($record->car->owner->email)->sendNow(new ServiceReminderMail($record->car, $record));
                        $record->forceFill(['reminder_sent_for_date' => $due])->save();
                        $sent++;
                    } catch (\Throwable $exception) {
                        report($exception);
                        $this->error('Reminder failed for service '.$record->id);
                        $failed++;
                    } finally {
                        $lock->release();
                    }
                }
            });

        $this->info("Reminders sent: {$sent}; failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
