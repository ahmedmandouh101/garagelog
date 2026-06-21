<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageRequest;
use App\Http\Requests\UpdateGarageRequest;
use App\Http\Resources\GarageResource;
use App\Models\Garage;
use Illuminate\Http\Request;

class GarageController extends Controller
{
    public function index(Request $request)
    {
        $garages = Garage::filter($request->only(['search', 'city']))
            ->withCount('mechanics')
            ->paginate(10);

        return GarageResource::collection($garages);
    }

    public function store(StoreGarageRequest $request)
    {
        $garage = Garage::create($request->validated());

        return response()->json([
            'message' => 'Garage created successfully',
            'garage'  => new GarageResource($garage),
        ], 201);
    }

    public function show(Garage $garage)
    {
        $garage->load('mechanics', 'serviceRecords');

        return new GarageResource($garage);
    }

    public function update(UpdateGarageRequest $request, Garage $garage)
    {
        $garage->update($request->validated());

        return response()->json([
            'message' => 'Garage updated successfully',
            'garage'  => new GarageResource($garage),
        ]);
    }

    public function destroy(Garage $garage)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $garage->delete();

        return response()->json([
            'message' => 'Garage deleted successfully',
        ]);
    }
}
