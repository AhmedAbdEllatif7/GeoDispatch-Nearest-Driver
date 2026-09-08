<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default around Cairo coordinates (lat: 29.95 - 30.15, lng: 31.20 - 31.50)
        $latitude = fake()->latitude(29.95, 30.15);
        $longitude = fake()->longitude(31.20, 31.50);

        return [
            'name' => fake()->name(),
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)::geography"),
        ];
    }
}
