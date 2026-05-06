<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
    ];

    // A garage has many mechanics
    public function mechanics()
    {
        return $this->hasMany(User::class);
    }

    // A garage has many service records
    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class);
    }


    public function scopeFilter($query, array $filters)
{
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%");
        });
    }

    if (!empty($filters['city'])) {
        $query->where('city', $filters['city']);
    }

    return $query;
}
}
