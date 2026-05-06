<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'mechanic';
    }

    public function rules(): array
    {
        return [
            'garage_id'             => 'required|exists:garages,id',
            'service_type'          => 'required|string|max:255',
            'description'           => 'nullable|string',
            'mileage_at_service'    => 'required|integer|min:0',
            'cost'                  => 'nullable|numeric|min:0',
            'service_date'          => 'required|date',
            'next_service_date'     => 'nullable|date|after:service_date',
            'next_service_mileage'  => 'nullable|integer|min:0',
            'parts'                 => 'nullable|array',
            'parts.*.part_name'     => 'required_with:parts|string|max:255',
            'parts.*.quantity'      => 'required_with:parts|integer|min:1',
            'parts.*.price'         => 'required_with:parts|numeric|min:0',
        ];
    }
}
