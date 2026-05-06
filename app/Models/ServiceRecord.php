<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'garage_id',
        'mechanic_id',
        'service_type',
        'description',
        'mileage_at_service',
        'cost',
        'service_date',
        'next_service_date',
        'next_service_mileage',
    ];

    protected function casts(): array
    {
        return [
            'service_date'        => 'date',
            'next_service_date'   => 'date',
            'cost'                => 'decimal:2',
        ];
    }

    // A service record belongs to a car
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // A service record belongs to a garage
    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    // A service record belongs to a mechanic
    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    // A service record has many parts
    public function parts()
    {
        return $this->hasMany(ServicePart::class);
    }

    // Calculate total cost including parts
    public function getTotalCostAttribute()
    {
        $partsCost = $this->parts->sum(function ($part) {
            return $part->price * $part->quantity;
        });

        return $this->cost + $partsCost;
    }

    // A service record has one review
    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
