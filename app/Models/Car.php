<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'make',
        'model',
        'year',
        'plate_number',
        'color',
        'mileage',
    ];

    // A car belongs to an owner
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function allowsGarage(int $garageId): bool
    {
        return DB::table('car_garage_access')
            ->where('car_id', $this->id)->where('garage_id', $garageId)
            ->where('owner_id', $this->user_id)->whereNull('revoked_at')->exists();
    }

    // A car has many service records
    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class);
    }

    // Get the latest service record
    public function latestService()
    {
        return $this->hasOne(ServiceRecord::class)->ofMany(['service_date' => 'max', 'id' => 'max']);
    }

    // Filter scope
    public function scopeFilter($query, array $filters)
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['make'])) {
            $query->where('make', $filters['make']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        return $query;
    }
}
