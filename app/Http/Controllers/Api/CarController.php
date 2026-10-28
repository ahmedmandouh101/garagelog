<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $cars = Car::where('user_id', auth()->id())
            ->filter($request->only(['search', 'make', 'year']))
            ->with('latestService')
            ->paginate(10);

        return CarResource::collection($cars);
    }

    public function store(StoreCarRequest $request)
    {
        $car = Car::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Car registered successfully',
            'car' => new CarResource($car),
        ], 201);
    }

    public function show(Car $car)
    {
        if ($car->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $car->load('serviceRecords.parts', 'serviceRecords.garage', 'serviceRecords.mechanic', 'owner');

        return new CarResource($car);
    }

    public function update(UpdateCarRequest $request, Car $car)
    {
        DB::transaction(function () use ($request, $car) {
            $car = Car::whereKey($car->id)->lockForUpdate()->firstOrFail();
            if ($request->has('mileage') && $request->integer('mileage') < max($car->mileage, $car->serviceRecords()->max('mileage_at_service') ?? 0)) {
                throw ValidationException::withMessages(['mileage' => 'قراءة العداد الجديدة لا يمكن أن تقل عن آخر قراءة محفوظة.']);
            }
            $car->update($request->validated());
        });
        $car->refresh();

        return response()->json([
            'message' => 'Car updated successfully',
            'car' => new CarResource($car),
        ]);
    }

    public function destroy(Car $car)
    {
        if ($car->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($car->serviceRecords()->exists()) {
            return response()->json(['message' => 'Cars with service history cannot be deleted'], 409);
        }

        $car->delete();

        return response()->json([
            'message' => 'Car deleted successfully',
        ]);
    }
}
