<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    // public function index()
    // {
    //     $cars = Car::where('user_id', auth()->id())
    //         ->with('latestService')
    //         ->paginate(10);

    //     return response()->json($cars);
    // }

    public function index(Request $request)
    {
        $cars = Car::where('user_id', auth()->id())
            ->filter($request->only(['search', 'make', 'year']))
            ->with('latestService')
            ->paginate(10);

        return response()->json($cars);
    }

    public function store(StoreCarRequest $request)
    {
        $car = Car::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Car registered successfully',
            'car'     => $car,
        ], 201);
    }

    public function show(Car $car)
    {
        if ($car->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $car->load('serviceRecords.parts', 'serviceRecords.garage', 'serviceRecords.mechanic');

        return response()->json($car);
    }

    public function update(UpdateCarRequest $request, Car $car)
    {
        $car->update($request->validated());

        return response()->json([
            'message' => 'Car updated successfully',
            'car'     => $car,
        ]);
    }

    public function destroy(Car $car)
    {
        if ($car->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $car->delete();

        return response()->json([
            'message' => 'Car deleted successfully',
        ]);
    }
}
