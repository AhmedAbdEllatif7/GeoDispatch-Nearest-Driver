<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        // Default around Cairo coordinates (lat: 29.95 - 30.15, lng: 31.20 - 31.50)
        $latitude = fake()->latitude(29.95, 30.15);
        $longitude = fake()->longitude(31.20, 31.50);

        return [
            'name' => fake()->name(),
            'status' => fake()->randomElement(DriverStatus::cases()),
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)::geography"),
        ];
    }

    /**
     * State for available drivers.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriverStatus::AVAILABLE,
        ]);
    }

    /**
     * State for busy drivers.
     */
    public function busy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriverStatus::BUSY,
        ]);
    }

    /**
     * State for offline drivers.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriverStatus::OFFLINE,
        ]);
    }
}
