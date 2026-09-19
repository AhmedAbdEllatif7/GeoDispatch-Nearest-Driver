<?php

use App\Enums\DriverStatus;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Clear drivers table before each test
    Driver::query()->delete();
});

test('it validates required spatial coordinates for nearest driver', function () {
    $response = $this->getJson('/api/drivers/nearest');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('it validates coordinate boundaries for nearest driver', function () {
    $response = $this->getJson('/api/drivers/nearest?latitude=100&longitude=200');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

test('it validates status against allowed enum values for nearest driver', function () {
    $response = $this->getJson('/api/drivers/nearest?latitude=30.0444&longitude=31.2357&status=invalid_status');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('it returns 404 when no available drivers exist in the system', function () {
    $response = $this->getJson('/api/drivers/nearest?latitude=30.0444&longitude=31.2357');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'No available drivers found.',
        ]);
});

test('it returns the nearest available driver using PostGIS KNN ordering', function () {
    // Reference point: Tahrir Square (lat 30.0444, lng 31.2357)
    $tahrirLat = 30.0444;
    $tahrirLng = 31.2357;

    // Driver 1: Very close (~500 meters at Talaat Harb)
    $closestDriver = Driver::create([
        'name' => 'Closest Driver',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2390, 30.0470), 4326)::geography"),
    ]);

    // Driver 2: Medium distance (~2.5 km at Dokki)
    Driver::create([
        'name' => 'Medium Distance Driver',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2114, 30.0385), 4326)::geography"),
    ]);

    // Driver 3: Far away (~25 km at 6th of October)
    Driver::create([
        'name' => 'Far Distance Driver',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(30.9472, 29.9737), 4326)::geography"),
    ]);

    $response = $this->getJson("/api/drivers/nearest?latitude={$tahrirLat}&longitude={$tahrirLng}");

    $response->assertOk()
        ->assertJsonPath('data.id', $closestDriver->id)
        ->assertJsonPath('data.name', 'Closest Driver')
        ->assertJsonPath('data.status', 'available');

    // Assert distance calculations and coordinates structure
    $driverData = $response->json('data');
    expect($driverData['distance']['meters'])->toBeLessThan(1000)
        ->and($driverData['distance']['kilometers'])->toBeLessThan(1.0)
        ->and($driverData['coordinates']['latitude'])->toBeFloat()
        ->and($driverData['coordinates']['longitude'])->toBeFloat();
});

test('it ignores closer busy drivers by default and picks the nearest available one', function () {
    $tahrirLat = 30.0444;
    $tahrirLng = 31.2357;

    // Driver Busy is very close (~200 meters)
    Driver::create([
        'name' => 'Busy Driver (Very Close)',
        'status' => DriverStatus::BUSY,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2365, 30.0450), 4326)::geography"),
    ]);

    // Driver Available is further (~1.5 km)
    $availableDriver = Driver::create([
        'name' => 'Available Driver (Further)',
        'status' => DriverStatus::AVAILABLE,
        'location' => DB::raw("ST_SetSRID(ST_MakePoint(31.2250, 30.0400), 4326)::geography"),
    ]);

    // Default request (only available drivers)
    $response = $this->getJson("/api/drivers/nearest?latitude={$tahrirLat}&longitude={$tahrirLng}");

    $response->assertOk()
        ->assertJsonPath('data.id', $availableDriver->id)
        ->assertJsonPath('data.name', 'Available Driver (Further)')
        ->assertJsonPath('data.status', 'available');

    // When explicitly requesting status=busy
    $responseBusy = $this->getJson("/api/drivers/nearest?latitude={$tahrirLat}&longitude={$tahrirLng}&status=busy");
    $responseBusy->assertOk()
        ->assertJsonPath('data.name', 'Busy Driver (Very Close)')
        ->assertJsonPath('data.status', 'busy');

    // When requesting status=all (returns the absolute closest regardless of status)
    $responseAll = $this->getJson("/api/drivers/nearest?latitude={$tahrirLat}&longitude={$tahrirLng}&status=all");
    $responseAll->assertOk()
        ->assertJsonPath('data.name', 'Busy Driver (Very Close)');
});
