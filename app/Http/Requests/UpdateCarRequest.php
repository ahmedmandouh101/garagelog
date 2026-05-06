<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $car = $this->route('car');
        return $this->user()->role === 'owner' && $car->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'make'         => 'sometimes|string|max:255',
            'model'        => 'sometimes|string|max:255',
            'year'         => 'sometimes|integer|min:1900|max:' . date('Y'),
            'plate_number' => 'sometimes|string|unique:cars,plate_number,' . $this->route('car')->id,
            'color'        => 'nullable|string|max:100',
            'mileage'      => 'nullable|integer|min:0',
        ];
    }
}
