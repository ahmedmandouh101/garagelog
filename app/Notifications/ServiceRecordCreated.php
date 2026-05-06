<?php

namespace App\Notifications;

use App\Models\ServiceRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ServiceRecordCreated extends Notification
{
    use Queueable;

    public function __construct(public ServiceRecord $serviceRecord) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message'      => 'Your car has been serviced.',
            'service_type' => $this->serviceRecord->service_type,
            'service_date' => $this->serviceRecord->service_date,
            'cost'         => $this->serviceRecord->cost,
            'garage'       => $this->serviceRecord->garage->name,
            'mechanic'     => $this->serviceRecord->mechanic->name,
            'car_id'       => $this->serviceRecord->car_id,
            'record_id'    => $this->serviceRecord->id,
        ];
    }
}
