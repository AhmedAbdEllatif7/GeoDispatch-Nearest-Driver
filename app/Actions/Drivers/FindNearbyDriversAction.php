<?php

namespace App\Actions\Drivers;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Collection;

class FindNearbyDriversAction
{
    public function execute(
        float $latitude,
        float $longitude,
        float $radiusInMeters = 5000.0,
        DriverStatus|string|null $status = DriverStatus::AVAILABLE,
        int $limit = 20
    ): Collection {
        $query = Driver::query()
            ->withCoordinates()
            ->withDistanceTo($latitude, $longitude)
            ->withinDistanceTo($latitude, $longitude, $radiusInMeters)
            ->orderByDistanceTo($latitude, $longitude);

        $statusValue = $status instanceof DriverStatus ? $status->value : $status;

        if ($statusValue && $statusValue !== 'all') {
            $query->where('status', $statusValue);
        }

        return $query->limit($limit)->get();
    }
}
