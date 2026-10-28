<?php

namespace App\Services;

use App\Models\Car;
use App\Models\ServiceRecord;
use Illuminate\Validation\ValidationException;

class ServiceRecordIntegrity
{
    public function validate(Car $car, array $data, ?ServiceRecord $record = null): void
    {
        $date = $data['service_date'] ?? $record->service_date->toDateString();
        $mileage = $data['mileage_at_service'] ?? $record->mileage_at_service;
        $nextDate = array_key_exists('next_service_date', $data) ? $data['next_service_date'] : $record?->next_service_date?->toDateString();
        $nextMileage = array_key_exists('next_service_mileage', $data) ? $data['next_service_mileage'] : $record?->next_service_mileage;
        if ($nextDate !== null && $nextDate <= $date) {
            throw ValidationException::withMessages(['next_service_date' => 'موعد الصيانة القادمة لازم يكون بعد تاريخ الخدمة.']);
        }
        if ($nextMileage !== null && $nextMileage <= $mileage) {
            throw ValidationException::withMessages(['next_service_mileage' => 'عداد الصيانة القادمة لازم يكون أكبر من عداد الخدمة.']);
        }
        if ($record && ! array_key_exists('service_date', $data) && ! array_key_exists('mileage_at_service', $data)) {
            return;
        }
        $records = $car->serviceRecords()->when($record, fn ($query) => $query->where('id', '!=', $record->id));
        $id = $record?->id ?? PHP_INT_MAX;
        $before = (clone $records)->where(function ($query) use ($date, $id) {
            $query->whereDate('service_date', '<', $date)->orWhere(function ($query) use ($date, $id) {
                $query->whereDate('service_date', $date)->where('id', '<', $id);
            });
        })->max('mileage_at_service');
        $after = (clone $records)->where(function ($query) use ($date, $id) {
            $query->whereDate('service_date', '>', $date)->orWhere(function ($query) use ($date, $id) {
                $query->whereDate('service_date', $date)->where('id', '>', $id);
            });
        })->min('mileage_at_service');
        if (($before !== null && $mileage < $before) || ($after !== null && $mileage > $after)) {
            throw ValidationException::withMessages(['mileage_at_service' => 'قراءة العداد مش متوافقة مع ترتيب الصيانات السابقة واللاحقة.']);
        }
    }
}
