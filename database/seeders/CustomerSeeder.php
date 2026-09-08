<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{

    public function run(): void
    {
        $landmarkCustomers = [
            // Cairo Tower (Zamalek)
            ['name' => 'Sara (Cairo Tower)', 'lat' => 30.0459, 'lng' => 31.2243],
            // City Stars (Heliopolis/Nasr City)
            ['name' => 'Mona (City Stars)', 'lat' => 30.0732, 'lng' => 31.3468],
            // Mall of Arabia (6th of October)
            ['name' => 'Ali (Mall of Arabia)', 'lat' => 30.0074, 'lng' => 30.9734],
            // Alexandria (Bibliotheca Alexandrina)
            ['name' => 'Nour (Alexandria Library)', 'lat' => 31.2089, 'lng' => 29.9092],
            // Outside coverage area (e.g., Tanta / Delta)
            ['name' => 'Khaled (Outside Area - Tanta)', 'lat' => 30.7865, 'lng' => 31.0004],
        ];

        foreach ($landmarkCustomers as $customer) {
            Customer::create([
                'name' => $customer['name'],
                'location' => DB::raw("ST_SetSRID(ST_MakePoint({$customer['lng']}, {$customer['lat']}), 4326)::geography"),
            ]);
        }

        // 2. Additional batch of 20 customers using factory
        Customer::factory()->count(20)->create();
    }
}
