<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGarageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'    => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'city'    => 'sometimes|string|max:255',
            'phone'   => 'nullable|string|max:20',
        ];
    }
}
