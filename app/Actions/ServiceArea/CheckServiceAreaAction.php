<?php

namespace App\Actions\ServiceArea;

use App\Models\ServiceArea;

class CheckServiceAreaAction
{
    /**
     * Check if coordinates belong to any active service area.
     * Returns the matching ServiceArea if found, or null if outside coverage.
     */
    public function execute(float $latitude, float $longitude): ?ServiceArea
    {
        return ServiceArea::query()
            ->containingPoint($latitude, $longitude)
            ->first();
    }
}