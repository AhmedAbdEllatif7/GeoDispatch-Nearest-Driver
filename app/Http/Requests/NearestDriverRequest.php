<?php

namespace App\Http\Requests;

use App\Enums\DriverStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NearestDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', 'string', Rule::in([...DriverStatus::values(), 'all'])],
        ];
    }

    /**
     * Custom error messages for spatial parameters.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'The latitude coordinate is required.',
            'latitude.between' => 'The latitude must be between -90 and 90 degrees.',
            'longitude.required' => 'The longitude coordinate is required.',
            'longitude.between' => 'The longitude must be between -180 and 180 degrees.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}

