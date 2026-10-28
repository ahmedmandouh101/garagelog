<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($request->user()?->id === $this->id || $request->user()?->role === 'admin', $this->email),
            'role' => $this->role,
            'phone' => $this->when($request->user()?->id === $this->id || $request->user()?->role === 'admin', $this->phone),
            'garage_id' => $this->when($request->user()?->id === $this->id, $this->garage_id),
            'is_garage_manager' => $this->when($request->user()?->id === $this->id, $this->is_garage_manager),
            'garage' => $this->whenLoaded('garage', fn () => new GarageResource($this->garage)),
            'joined_at' => $this->created_at->format('d M Y'),
        ];
    }
}
