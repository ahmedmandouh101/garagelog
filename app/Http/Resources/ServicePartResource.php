<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'service_type'         => $this->service_type,
            'description'          => $this->description,
            'mileage_at_service'   => number_format($this->mileage_at_service) . ' km',
            'cost'                 => $this->cost,
            'total_cost'           => $this->total_cost,
            'service_date'         => $this->service_date->format('d M Y'),
            'next_service_date'    => $this->next_service_date?->format('d M Y'),
            'next_service_mileage' => $this->next_service_mileage
                                        ? number_format($this->next_service_mileage) . ' km'
                                        : null,
            'garage'               => new GarageResource($this->whenLoaded('garage')),
            'mechanic'             => new UserResource($this->whenLoaded('mechanic')),
            'parts'                => ServicePartResource::collection($this->whenLoaded('parts')),
            'review'               => new ReviewResource($this->whenLoaded('review')),
            'service_date_raw'     => $this->service_date,
            'created_at'           => $this->created_at->format('d M Y'),
        ];
    }
}
