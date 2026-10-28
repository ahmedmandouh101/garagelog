<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'mechanic'
            && $this->user()->garage_id !== null
            && (int) $this->input('garage_id') === (int) $this->user()->garage_id
            && $this->route('car')->allowsGarage($this->user()->garage_id);
    }

    public function rules(): array
    {
        return [
            'garage_id' => 'required|exists:garages,id',
            'service_type' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'mileage_at_service' => 'required|integer|min:0|max:4294967295',
            'cost' => 'sometimes|numeric|decimal:0,2|min:0|max:99999999.99',
            'service_date' => 'required|date_format:Y-m-d|before_or_equal:'.today('Africa/Cairo')->toDateString(),
            'next_service_date' => 'nullable|date_format:Y-m-d|after:service_date',
            'next_service_mileage' => 'nullable|integer|min:0|max:4294967295',
            'parts' => 'sometimes|array',
            'parts.*' => 'array:part_name,quantity,price',
            'parts.*.part_name' => 'required_with:parts|string|max:255',
            'parts.*.quantity' => 'required_with:parts|integer|min:1|max:4294967295',
            'parts.*.price' => 'required_with:parts|numeric|decimal:0,2|min:0|max:99999999.99',
        ];
    }
}
