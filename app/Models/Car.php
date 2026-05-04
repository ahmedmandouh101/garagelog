<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    // A car has many service records
    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class);
    }

    // Get the latest service record
    public function latestService()
    {
        return $this->hasOne(ServiceRecord::class)->latestOfMany();
    }
}
