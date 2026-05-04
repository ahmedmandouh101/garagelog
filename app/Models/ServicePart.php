<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_record_id',
        'part_name',
        'quantity',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    // A part belongs to a service record
    public function serviceRecord()
    {
        return $this->belongsTo(ServiceRecord::class);
    }

    // Calculate total price for this part
    public function getTotalPriceAttribute()
    {
        return $this->price * $this->quantity;
    }
}
