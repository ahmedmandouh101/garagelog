<?php

namespace App\Models;

use App\Notifications\ResetAccountPassword;
use App\Notifications\VerifyAccountEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

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
            'is_garage_manager' => 'boolean',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
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

    public function managesGarage(): bool
    {
        return $this->role === 'mechanic' && $this->garage_id !== null && $this->is_garage_manager;
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyAccountEmail);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetAccountPassword($token));
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
