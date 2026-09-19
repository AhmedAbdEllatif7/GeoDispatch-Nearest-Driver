<?php

namespace App\Actions\Drivers;

use App\Enums\DriverStatus;
use App\Models\Driver;

class FindNearestDriverAction
{
    /**
     * Execute KNN proximity search to find the nearest driver.
     */
    public function execute(float $latitude, float $longitude, DriverStatus|string|null $status = DriverStatus::AVAILABLE): ? Driver
    {
        $query = Driver::query()
            ->withCoordinates()
            ->withDistanceTo($latitude, $longitude)
            ->orderByDistanceTo($latitude, $longitude);

        $statusValue = $status instanceof DriverStatus ? $status->value : $status;

        if ($statusValue && $statusValue !== 'all') {
            $query->where('status', $statusValue);
        }

        return $query->first();
    }
}
