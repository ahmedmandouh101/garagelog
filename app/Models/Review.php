<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'mechanic_id',
        'service_record_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    // A review belongs to an owner
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // A review belongs to a mechanic
    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    // A review belongs to a service record
    public function serviceRecord()
    {
        return $this->belongsTo(ServiceRecord::class);
    }
}
