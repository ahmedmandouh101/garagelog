<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'part_name'   => $this->part_name,
            'quantity'    => $this->quantity,
            'price'       => $this->price,
            'total_price' => $this->total_price,
        ];
    }
}
