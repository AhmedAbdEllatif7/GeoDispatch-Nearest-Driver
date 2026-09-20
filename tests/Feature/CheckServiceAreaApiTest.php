<?php

use App\Models\ServiceArea;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    ServiceArea::query()->delete();
});

test('it validates required spatial coordinates for checking service area', function () {
    $response = $this->getJson('/api/service-areas/check');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('it validates coordinate boundaries for service area check', function () {
    $response = $this->getJson('/api/service-areas/check?latitude=100&longitude=200');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('it returns 404 and serviceable false when coordinates are outside any service area', function () {
    ServiceArea::create([
        'name' => 'Cairo Center',
        'boundary' => DB::raw("ST_GeomFromText('POLYGON((31.20 30.00, 31.30 30.00, 31.30 30.10, 31.20 30.10, 31.20 30.00))', 4326)"),
    ]);

    // Alexandria coordinates
    $response = $this->getJson('/api/service-areas/check?latitude=31.20&longitude=29.91');

    $response->assertStatus(404)
        ->assertJson([
            'serviceable' => false,
            'message' => 'No service area found for the given coordinates.',
        ]);
});

test('it returns 200 with service area details when coordinates fall within a service area', function () {
    $area = ServiceArea::create([
        'name' => 'Cairo Center',
        'boundary' => DB::raw("ST_GeomFromText('POLYGON((31.20 30.00, 31.30 30.00, 31.30 30.10, 31.20 30.10, 31.20 30.00))', 4326)"),
    ]);

    // Inside point (lat 30.05, lng 31.25)
    $response = $this->getJson('/api/service-areas/check?latitude=30.05&longitude=31.25');

    $response->assertStatus(200)
        ->assertJson([
            'serviceable' => true,
            'service_area' => [
                'id' => $area->id,
                'name' => 'Cairo Center',
            ],
        ]);
});
