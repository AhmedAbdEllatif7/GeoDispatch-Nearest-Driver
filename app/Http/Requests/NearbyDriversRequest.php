<?php

namespace App\Http\Requests;

use App\Enums\DriverStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NearbyDriversRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'numeric', 'min:1', 'max:100000'], // Radius in meters (max 100km)
            'status' => ['sometimes', 'string', Rule::in([...DriverStatus::values(), 'all'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Custom error messages for spatial parameters.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'The latitude coordinate is required.',
            'latitude.between' => 'The latitude must be between -90 and 90 degrees.',
            'longitude.required' => 'The longitude coordinate is required.',
            'longitude.between' => 'The longitude must be between -180 and 180 degrees.',
            'radius.min' => 'The radius must be at least 1 meter.',
            'radius.max' => 'The radius cannot exceed 100,000 meters (100 km).',
        ];
    }
}
