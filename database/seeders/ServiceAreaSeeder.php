<?php

namespace Database\Seeders;

use App\Models\ServiceArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Greater Cairo',
                // WKT Polygon: Longitude Latitude pairs forming a closed ring
                'boundary' => "POLYGON((
                    31.18 30.16,
                    31.48 30.16,
                    31.48 29.93,
                    31.18 29.93,
                    31.18 30.16
                ))",
            ],
            [
                'name' => 'Giza & 6th of October',
                'boundary' => "POLYGON((
                    30.80 30.08,
                    31.22 30.08,
                    31.22 29.88,
                    30.80 29.88,
                    30.80 30.08
                ))",
            ],
            [
                'name' => 'Alexandria',
                'boundary' => "POLYGON((
                    29.80 31.30,
                    30.12 31.30,
                    30.12 31.14,
                    29.80 31.14,
                    29.80 31.30
                ))",
            ],
        ];

        foreach ($areas as $area) {
            ServiceArea::updateOrCreate(
                ['name' => $area['name']],
                [
                    'boundary' => DB::raw("ST_GeomFromText('{$area['boundary']}', 4326)"),
                ]
            );
        }
    }
}
