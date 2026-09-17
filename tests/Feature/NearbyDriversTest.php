<?php

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Clear drivers table before each test
    Driver::query()->delete();
});

test('it validates required spatial coordinates', function () {
    $response = $this->getJson('/api/drivers/nearby');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('it validates coordinate boundaries', function () {
    $response = $this->getJson('/api/drivers/nearby?latitude=100&longitude=200');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('it validates radius boundaries', function () {
    $response = $this->getJson('/api/drivers/nearby?latitude=30.0444&longitude=31.2357&radius=-10');
    $response->assertStatus(422)->assertJsonValidationErrors(['radius']);

    $responseTooLarge = $this->getJson('/api/drivers/nearby?latitude=30.0444&longitude=31.2357&radius=150000');
    $responseTooLarge->assertStatus(422)->assertJsonValidationErrors(['radius']);
});

test('it validates status against allowed enum values', function () {
    $response = $this->getJson('/api/drivers/nearby?latitude=30.0444&longitude=31.2357&status=invalid_status');
    $response->assertStatus(422)->assertJsonValidationErrors(['status']);
});

test('it returns nearby available drivers within radius sorted by distance', function () {
    // Tahrir Square center: lat 30.0444, lng 31.2357
    $tahrirLat = 30.0444;
    $tahrirLng = 31.2357;

    // Driver 1: Very close (~500 meters away at Talaat Harb)
    $driver1 = Driver::create([
        'name' => 'Driver Close',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2390, 30.0470), 4326)::geography"),
    ]);

    // Driver 2: Medium (~2.5 km away at Dokki)
    $driver2 = Driver::create([
        'name' => 'Driver Medium',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2114, 30.0385), 4326)::geography"),
    ]);

    // Driver 3: Far away (~25 km away at 6th of October) - Should be excluded with 5km radius
    $driver3 = Driver::create([
        'name' => 'Driver Far',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(30.9472, 29.9737), 4326)::geography"),
    ]);

    // Driver 4: Close (~500m) but BUSY - Should be excluded by default status filter
    $driver4 = Driver::create([
        'name' => 'Driver Busy Close',
        'status' => DriverStatus::BUSY,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2392, 30.0472), 4326)::geography"),
    ]);

    $response = $this->getJson("/api/drivers/nearby?latitude={$tahrirLat}&longitude={$tahrirLng}&radius=5000");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Driver Close')
        ->assertJsonPath('data.1.name', 'Driver Medium');

    // Assert that distance in meters and coordinates are returned
    $firstDriver = $response->json('data.0');
    expect($firstDriver['distance']['meters'])->toBeLessThan(1000)
        ->and($firstDriver['distance']['kilometers'])->toBeLessThan(1.0)
        ->and($firstDriver['coordinates']['latitude'])->toBeFloat()
        ->and($firstDriver['coordinates']['longitude'])->toBeFloat();
});

test('it can filter nearby drivers by specific status or all', function () {
    $tahrirLat = 30.0444;
    $tahrirLng = 31.2357;

    Driver::create([
        'name' => 'Driver Available',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2390, 30.0470), 4326)::geography"),
    ]);

    Driver::create([
        'name' => 'Driver Busy',
        'status' => DriverStatus::BUSY,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2392, 30.0472), 4326)::geography"),
    ]);

    // Request with status=all
    $responseAll = $this->getJson("/api/drivers/nearby?latitude={$tahrirLat}&longitude={$tahrirLng}&radius=5000&status=all");
    $responseAll->assertOk()->assertJsonCount(2, 'data');

    // Request with status=busy
    $responseBusy = $this->getJson("/api/drivers/nearby?latitude={$tahrirLat}&longitude={$tahrirLng}&radius=5000&status=busy");
    $responseBusy->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Driver Busy');
});

test('it respects the limit parameter', function () {
    $tahrirLat = 30.0444;
    $tahrirLng = 31.2357;

    for ($i = 1; $i <= 5; $i++) {
        Driver::create([
            'name' => "Driver {$i}",
            'status' => DriverStatus::AVAILABLE,
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2357 + {$i} * 0.001, 30.0444), 4326)::geography"),
        ]);
    }

    $response = $this->getJson("/api/drivers/nearby?latitude={$tahrirLat}&longitude={$tahrirLng}&radius=10000&limit=2");
    $response->assertOk()->assertJsonCount(2, 'data');
});
