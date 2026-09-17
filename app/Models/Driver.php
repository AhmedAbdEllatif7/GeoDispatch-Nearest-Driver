<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'location',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DriverStatus::class,
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', DriverStatus::AVAILABLE->value);
    }

    /**
     * Scope to extract latitude and longitude as scalar columns.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->selectRaw('drivers.*, ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude');
    }

    /**
     * Scope to filter drivers within a given radius (in meters) using PostGIS ST_DWithin.
     * ST_DWithin leverages the GiST spatial index on the geography column.
     */
    public function scopeWithinDistanceTo(Builder $query, float $latitude, float $longitude, float $radiusInMeters): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$longitude, $latitude, $radiusInMeters]
        );
    }

    /**
     * Scope to calculate and select the geodesic distance in meters to a given coordinate.
     */
    public function scopeWithDistanceTo(Builder $query, float $latitude, float $longitude): Builder
    {
        return $query->selectRaw(
            'ST_Distance(location, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) as distance_in_meters',
            [$longitude, $latitude]
        );
    }

    /**
     * Scope to order results by spatial proximity using the KNN operator (<->) or calculated distance.
     */
    public function scopeOrderByDistanceTo(Builder $query, float $latitude, float $longitude, string $direction = 'asc'): Builder
    {
        $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return $query->orderByRaw(
            "location <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography {$dir}",
            [$longitude, $latitude]
        );
    }
}
