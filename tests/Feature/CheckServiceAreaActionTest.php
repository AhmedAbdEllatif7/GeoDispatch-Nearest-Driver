<?php

use App\Actions\ServiceArea\CheckServiceAreaAction;
use App\Models\ServiceArea;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    ServiceArea::query()->delete();
});

test('it returns the matching service area when coordinates are inside the boundary', function () {
    // A square boundary around Cairo Center:
    // (lng: 31.20 to 31.30, lat: 30.00 to 30.10)
    $area = ServiceArea::create([
        'name' => 'Cairo Center',
        'boundary' => DB::raw("ST_GeomFromText('POLYGON((31.20 30.00, 31.30 30.00, 31.30 30.10, 31.20 30.10, 31.20 30.00))', 4326)"),
    ]);

    $action = app(CheckServiceAreaAction::class);

    // Point inside: lat 30.05, lng 31.25
    $result = $action->execute(latitude: 30.05, longitude: 31.25);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($area->id)
        ->and($result->name)->toBe('Cairo Center');
});

test('it returns null when coordinates are outside any service area boundary', function () {
    ServiceArea::create([
        'name' => 'Cairo Center',
        'boundary' => DB::raw("ST_GeomFromText('POLYGON((31.20 30.00, 31.30 30.00, 31.30 30.10, 31.20 30.10, 31.20 30.00))', 4326)"),
    ]);

    $action = app(CheckServiceAreaAction::class);

    // Point outside: Alexandria coordinates (lat 31.20, lng 29.91)
    $result = $action->execute(latitude: 31.20, longitude: 29.91);

    expect($result)->toBeNull();
});
