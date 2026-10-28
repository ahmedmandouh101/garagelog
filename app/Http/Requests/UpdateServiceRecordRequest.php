<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('serviceRecord');

        return $this->user()->role === 'mechanic'
            && $record->mechanic_id === $this->user()->id
            && $record->garage_id === $this->user()->garage_id
            && $this->route('car')->allowsGarage($record->garage_id);
    }

    public function rules(): array
    {
        return [
            'service_type' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'mileage_at_service' => 'sometimes|integer|min:0|max:4294967295',
            'cost' => 'sometimes|numeric|decimal:0,2|min:0|max:99999999.99',
            'service_date' => 'sometimes|date_format:Y-m-d|before_or_equal:'.today('Africa/Cairo')->toDateString(),
            'next_service_date' => 'nullable|date_format:Y-m-d|after:'.($this->input('service_date') ?? $this->route('serviceRecord')->service_date->toDateString()),
            'next_service_mileage' => 'nullable|integer|min:0|max:4294967295',
            'parts' => 'sometimes|array',
            'parts.*' => 'array:part_name,quantity,price',
            'parts.*.part_name' => 'required_with:parts|string|max:255',
            'parts.*.quantity' => 'required_with:parts|integer|min:1|max:4294967295',
            'parts.*.price' => 'required_with:parts|numeric|decimal:0,2|min:0|max:99999999.99',
        ];
    }
}
