<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ServiceRecord;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $serviceRecord = $this->route('serviceRecord');
        $car = $this->route('car');

        // Only the car owner can leave a review
        return $car->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }
}
