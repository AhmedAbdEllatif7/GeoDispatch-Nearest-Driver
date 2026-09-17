<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Specific landmark drivers for clear manual testing and verification
        $landmarkDrivers = [
            // Cairo Downtown (Tahrir Square)
            ['name' => 'Ahmed (Tahrir)', 'status' => DriverStatus::AVAILABLE, 'lat' => 30.0444, 'lng' => 31.2357],
            // Heliopolis (Korba)
            ['name' => 'Mahmoud (Korba)', 'status' => DriverStatus::AVAILABLE, 'lat' => 30.0911, 'lng' => 31.3256],
            // Nasr City (Abbas El-Akkad)
            ['name' => 'Omar (Nasr City)', 'status' => DriverStatus::AVAILABLE, 'lat' => 30.0558, 'lng' => 31.3417],
            // New Cairo (90th Street)
            ['name' => 'Kareem (New Cairo)', 'status' => DriverStatus::AVAILABLE, 'lat' => 30.0167, 'lng' => 31.4286],
            // Dokki / Giza
            ['name' => 'Tarek (Dokki)', 'status' => DriverStatus::AVAILABLE, 'lat' => 30.0385, 'lng' => 31.2114],
            // 6th of October (Hosary Mosque)
            ['name' => 'Mostafa (October)', 'status' => DriverStatus::AVAILABLE, 'lat' => 29.9737, 'lng' => 30.9472],
            // Alexandria (Sidi Gaber)
            ['name' => 'Youssef (Alexandria)', 'status' => DriverStatus::AVAILABLE, 'lat' => 31.2185, 'lng' => 29.9430],
            // Busy driver in Cairo
            ['name' => 'Hassan (Busy Cairo)', 'status' => DriverStatus::BUSY, 'lat' => 30.0450, 'lng' => 31.2360],
            // Offline driver in Cairo
            ['name' => 'Ibrahim (Offline Cairo)', 'status' => DriverStatus::OFFLINE, 'lat' => 30.0460, 'lng' => 31.2370],
        ];

        foreach ($landmarkDrivers as $driver) {
            Driver::create([
                'name' => $driver['name'],
                'status' => $driver['status'],
                'location' => DB::raw("ST_SetSRID(ST_MakePoint({$driver['lng']}, {$driver['lat']}), 4326)::geography"),
            ]);
        }

        Driver::factory()->count(40)->available()->create();
        Driver::factory()->count(10)->busy()->create();
        Driver::factory()->count(5)->offline()->create();
    }
}
