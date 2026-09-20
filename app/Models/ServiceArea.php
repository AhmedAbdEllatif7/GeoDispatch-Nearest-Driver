<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'boundary',
    ];

    /**
     * Scope to filter service areas that contain the given coordinate point.
     * Leverages the GiST spatial index on boundary.
     */
    public function scopeContainingPoint(Builder $query, float $latitude, float $longitude): Builder
    {
        return $query->whereRaw('ST_Contains(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326))', [$longitude, $latitude]);
    }
}
