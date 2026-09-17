<?php

namespace App\Http\Controllers;

use App\Actions\Drivers\FindNearbyDriversAction;
use App\Http\Requests\NearbyDriversRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DriverController extends Controller
{
    /**
     * UC-01: Find nearby drivers within a specified radius.
     */
    public function nearby(NearbyDriversRequest $request, FindNearbyDriversAction $action): AnonymousResourceCollection
    {
        $drivers = $action->execute(
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            radiusInMeters: (float) ($request->validated('radius') ?? 5000.0),
            status: $request->validated('status', 'available'),
            limit: (int) ($request->validated('limit') ?? 20)
        );

        return DriverResource::collection($drivers);
    }

    /**
     * Display a paginated listing of drivers.
     */
    public function index(): AnonymousResourceCollection
    {
        $drivers = Driver::query()
            ->withCoordinates()
            ->latest()
            ->paginate(20);

        return DriverResource::collection($drivers);
    }

    /**
     * Display the specified driver with coordinates.
     */
    public function show(Driver $driver): DriverResource
    {
        $driverWithCoords = Driver::query()
            ->withCoordinates()
            ->findOrFail($driver->id);

        return new DriverResource($driverWithCoords);
    }
}
