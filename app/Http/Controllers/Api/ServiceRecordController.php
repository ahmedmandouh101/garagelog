<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRecordRequest;
use App\Http\Requests\UpdateServiceRecordRequest;
use App\Models\Car;
use App\Models\ServiceRecord;

class ServiceRecordController extends Controller
{
    public function index(Car $car)
    {
        if ($car->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $records = $car->serviceRecords()
            ->with('parts', 'mechanic', 'garage')
            ->latest()
            ->paginate(10);

        return response()->json($records);
    }

    public function store(StoreServiceRecordRequest $request, Car $car)
    {
        $record = $car->serviceRecords()->create([
            'garage_id'            => $request->garage_id,
            'mechanic_id'          => auth()->id(),
            'service_type'         => $request->service_type,
            'description'          => $request->description,
            'mileage_at_service'   => $request->mileage_at_service,
            'cost'                 => $request->cost ?? 0,
            'service_date'         => $request->service_date,
            'next_service_date'    => $request->next_service_date,
            'next_service_mileage' => $request->next_service_mileage,
        ]);

        // Create parts if provided
        if ($request->has('parts')) {
            foreach ($request->parts as $part) {
                $record->parts()->create($part);
            }
        }

        $record->load('parts', 'mechanic', 'garage');

        return response()->json([
            'message' => 'Service record created successfully',
            'record'  => $record,
        ], 201);
    }

    public function show(Car $car, ServiceRecord $serviceRecord)
    {
        if ($car->user_id !== auth()->id() && auth()->user()->role !== 'mechanic') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $serviceRecord->load('parts', 'mechanic', 'garage');

        return response()->json($serviceRecord);
    }

    public function update(UpdateServiceRecordRequest $request, Car $car, ServiceRecord $serviceRecord)
    {
        $serviceRecord->update($request->validated());

        // If parts are provided, delete old ones and create new ones
        if ($request->has('parts')) {
            $serviceRecord->parts()->delete();
            foreach ($request->parts as $part) {
                $serviceRecord->parts()->create($part);
            }
        }

        $serviceRecord->load('parts', 'mechanic', 'garage');

        return response()->json([
            'message' => 'Service record updated successfully',
            'record'  => $serviceRecord,
        ]);
    }

    public function destroy(Car $car, ServiceRecord $serviceRecord)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $serviceRecord->parts()->delete();
        $serviceRecord->delete();

        return response()->json([
            'message' => 'Service record deleted successfully',
        ]);
    }
}
