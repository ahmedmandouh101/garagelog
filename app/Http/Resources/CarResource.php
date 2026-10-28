<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'make'                 => $this->make,
            'model'                => $this->model,
            'year'                 => $this->year,
            'plate_number'         => $this->plate_number,
            'color'                => $this->color,
            'mileage'              => number_format($this->mileage) . ' km',
            'owner'                => new UserResource($this->whenLoaded('owner')),
            'latest_service'       => new ServiceRecordResource($this->whenLoaded('latestService')),
            'service_records'      => ServiceRecordResource::collection($this->whenLoaded('serviceRecords')),
            'registered_at'        => $this->created_at->format('d M Y'),
        ];
    }
}
