<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'owner';
    }

    public function rules(): array
    {
        return [
            'make'         => 'required|string|max:255',
            'model'        => 'required|string|max:255',
            'year'         => 'required|integer|min:1900|max:' . date('Y'),
            'plate_number' => 'required|string|unique:cars,plate_number',
            'color'        => 'nullable|string|max:100',
            'mileage'      => 'nullable|integer|min:0',
        ];
    }
}
