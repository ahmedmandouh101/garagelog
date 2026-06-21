<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'rating'     => $this->rating,
            'comment'    => $this->comment,
            'owner'      => new UserResource($this->whenLoaded('owner')),
            'mechanic'   => new UserResource($this->whenLoaded('mechanic')),
            'created_at' => $this->created_at->format('d M Y'),
        ];
    }
}
