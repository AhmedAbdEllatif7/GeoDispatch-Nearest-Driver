<?php

namespace App\Http\Resources;

use App\Enums\DriverStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status instanceof DriverStatus ? $this->status->value : $this->status,
            'coordinates' => [
                'latitude' => isset($this->latitude) ? (float) $this->latitude : null,
                'longitude' => isset($this->longitude) ? (float) $this->longitude : null,
            ],
            'distance' => isset($this->distance_in_meters) ? [
                'meters' => round((float) $this->distance_in_meters, 2),
                'kilometers' => round((float) $this->distance_in_meters / 1000, 2),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
