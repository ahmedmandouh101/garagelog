<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkshopCustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->requireStaff($request);
        $data = $request->validate(['search' => 'nullable|string|max:100']);
        $customers = DB::table('workshop_customers')->where('garage_id', $request->user()->garage_id)
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('plate_number', 'like', '%'.$search.'%');
                });
            })->orderByDesc('id')->paginate(10, [
                'id', 'name', 'phone', 'plate_number', 'make', 'model', 'year',
                'notes', 'claimed_at', 'claimed_car_id', 'claim_expires_at', 'created_at',
            ]);

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $this->requireStaff($request);
        $data = $request->validate([
            'name' => 'required|string|max:255', 'phone' => 'required|string|max:30',
            'plate_number' => 'required|string|max:100', 'make' => 'required|string|max:255',
            'model' => 'required|string|max:255', 'year' => 'required|integer|min:1900|max:'.date('Y'),
            'notes' => 'nullable|string|max:2000',
        ]);
        $token = Str::random(64);
        $expiresAt = now()->addDays(7);
        $id = DB::table('workshop_customers')->insertGetId([
            ...$data, 'garage_id' => $request->user()->garage_id, 'created_by' => $request->user()->id,
            'claim_token_hash' => hash('sha256', $token), 'claim_expires_at' => $expiresAt,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['id' => $id, 'claim_token' => $token, 'expires_at' => $expiresAt->toIso8601String()], 201);
    }

    public function renewToken(Request $request, int $customer)
    {
        $this->requireStaff($request);
        $token = Str::random(64);
        DB::transaction(function () use ($request, $customer, $token) {
            $row = DB::table('workshop_customers')->where('garage_id', $request->user()->garage_id)
                ->where('id', $customer)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_if($row->claimed_at, 409, 'Customer has already linked their vehicle');
            DB::table('workshop_customers')->where('id', $row->id)->update([
                'claim_token_hash' => hash('sha256', $token), 'claim_expires_at' => now()->addDays(7), 'updated_at' => now(),
            ]);
        });

        return response()->json(['claim_token' => $token, 'expires_at' => now()->addDays(7)->toIso8601String()]);
    }

    public function claim(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);
        $data = $request->validate(['token' => 'required|string|max:128', 'car_id' => 'required|integer']);
        DB::transaction(function () use ($request, $data) {
            $customer = DB::table('workshop_customers')->where('claim_token_hash', hash('sha256', $data['token']))->lockForUpdate()->first();
            abort_unless($customer && ! $customer->claimed_at && now()->lt($customer->claim_expires_at), 422, 'Invalid or expired linking code');
            $car = Car::whereKey($data['car_id'])->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless(mb_strtoupper(trim($car->plate_number)) === mb_strtoupper(trim($customer->plate_number)), 422, 'Vehicle plate must match the workshop intake');
            DB::table('car_garage_access')->updateOrInsert(['car_id' => $car->id, 'garage_id' => $customer->garage_id], [
                'owner_id' => $request->user()->id, 'granted_at' => now(), 'revoked_at' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('workshop_customers')->where('id', $customer->id)->update([
                'claimed_car_id' => $car->id, 'claimed_by' => $request->user()->id, 'claimed_at' => now(), 'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Vehicle linked and workshop access granted']);
    }

    public function preview(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);
        $data = $request->validate(['token' => 'required|string|max:128', 'car_id' => 'required|integer']);
        $customer = DB::table('workshop_customers')->where('claim_token_hash', hash('sha256', $data['token']))->first();
        abort_unless($customer && ! $customer->claimed_at && now()->lt($customer->claim_expires_at), 422, 'Invalid or expired linking code');
        $car = Car::whereKey($data['car_id'])->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless(mb_strtoupper(trim($car->plate_number)) === mb_strtoupper(trim($customer->plate_number)), 422, 'Vehicle plate must match the workshop intake');
        $garage = DB::table('garages')->where('id', $customer->garage_id)->first();

        return response()->json(['garage' => ['name' => $garage->name, 'city' => $garage->city], 'plate_number' => $car->plate_number]);
    }

    private function requireStaff(Request $request): void
    {
        abort_unless($request->user()->role === 'mechanic' && $request->user()->garage_id !== null, 403);
    }
}
