<?php

namespace App\Http\Controllers;

use App\Actions\ServiceArea\CheckServiceAreaAction;
use App\Http\Requests\CheckServiceAreaRequest;
use App\Http\Resources\ServiceAreaResource;
use App\Models\ServiceArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceAreaController extends Controller
{
    /**
     * UC-03: Check if a point belongs to any Service Area (Containment check).
     */
    public function check(CheckServiceAreaRequest $request, CheckServiceAreaAction $action): JsonResponse
    {
        $serviceArea = $action->execute(
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude')
        );

        if (! $serviceArea) {
            return response()->json([
                'serviceable' => false,
                'message' => 'No service area found for the given coordinates.',
            ], 404);
        }

        return response()->json([
            'serviceable' => true,
            'service_area' => new ServiceAreaResource($serviceArea),
        ]);
    }





    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceArea $serviceArea)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceArea $serviceArea)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceArea $serviceArea)
    {
        //
    }
}
