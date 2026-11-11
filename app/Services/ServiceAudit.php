<?php

namespace App\Services;

use App\Models\ServiceRecord;
use Illuminate\Support\Facades\DB;

class ServiceAudit
{
    public static function snapshot(ServiceRecord $record): array
    {
        return ['service' => $record->only($record->getFillable()),
            'parts' => $record->parts()->get(['part_name', 'quantity', 'price'])->toArray()];
    }

    public static function write(ServiceRecord $record, string $action, ?array $before = null): void
    {
        DB::table('service_audits')->insert([
            'service_record_id' => $record->id, 'actor_id' => auth()->id(), 'action' => $action,
            'before_data' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_data' => json_encode(self::snapshot($record), JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
