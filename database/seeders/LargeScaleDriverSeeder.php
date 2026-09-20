<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LargeScaleDriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates 100,000 spatial drivers directly inside PostgreSQL engine.
     */
    public function run(int $count = 100000): void
    {
        $this->command?->info("Starting high-speed generation of {$count} spatial drivers in PostgreSQL...");

        $startTime = microtime(true);

        // Native PostgreSQL set-based bulk insert using generate_series
        // Geographically bound to Greater Cairo region (lat: 29.90 - 30.20, lng: 31.10 - 31.50)
        // Realistic status distribution: 70% available, 20% busy, 10% offline
        DB::statement("
            INSERT INTO drivers (name, status, location, created_at, updated_at)
            SELECT
                'Driver #' || id,
                CASE
                    WHEN random() < 0.70 THEN 'available'
                    WHEN random() < 0.90 THEN 'busy'
                    ELSE 'offline'
                END,
                ST_SetSRID(
                    ST_MakePoint(
                        31.10 + (random() * (31.50 - 31.10)), -- Longitude (X)
                        29.90 + (random() * (30.20 - 29.90))  -- Latitude (Y)
                    ),
                    4326
                )::geography,
                NOW(),
                NOW()
            FROM generate_series(1, ?) AS id;
        ", [$count]);

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->command?->info("Successfully seeded {$count} spatial drivers in {$elapsed} seconds!");
    }
}
