<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GarageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'address'         => $this->address,
            'city'            => $this->city,
            'phone'           => $this->phone,
            'mechanics_count' => $this->whenCounted('mechanics'),
            'mechanics'       => UserResource::collection($this->whenLoaded('mechanics')),
            'created_at'      => $this->created_at->format('d M Y'),
        ];
    }
}
