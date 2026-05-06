<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'profile_photo',
        'garage_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // A owner has many cars
    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    // A mechanic belongs to a garage
    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    // A mechanic has many service records
    public function serviceRecords()
    {
        return $this->hasMany(ServiceRecord::class, 'mechanic_id');
    }

    // A mechanic has many reviews
    public function reviews()
    {
        return $this->hasMany(Review::class, 'mechanic_id');
    }

// Get mechanic's average rating
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1);
    }
}
