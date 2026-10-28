<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarResource;
use App\Http\Resources\ServiceRecordResource;
use App\Models\Car;
use App\Models\Garage;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkshopController extends Controller
{
    public function assignManager(Request $request, Garage $garage)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $manager = DB::transaction(function () use ($data, $garage) {
            $user = User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            abort_unless($user->role === 'mechanic', 422, 'Manager must be a mechanic account');
            abort_if($user->garage_id !== null && $user->garage_id !== $garage->id, 409, 'Already assigned to another workshop');
            $user->forceFill(['garage_id' => $garage->id, 'is_garage_manager' => true])->save();

            return $user;
        });

        return response()->json(['manager' => $manager->only('id', 'name', 'garage_id', 'is_garage_manager')]);
    }

    public function staff(Request $request)
    {
        $this->requireManager($request);

        return response()->json(['data' => User::where('garage_id', $request->user()->garage_id)
            ->where('role', 'mechanic')->get(['id', 'name', 'email', 'is_garage_manager'])]);
    }

    public function invite(Request $request)
    {
        $this->requireManager($request);
        $data = $request->validate(['email' => 'required|email|max:255']);
        $token = Str::random(64);
        DB::table('garage_invitations')->insert([
            'garage_id' => $request->user()->garage_id, 'invited_by' => $request->user()->id,
            'email' => strtolower($data['email']), 'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(2), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['invitation_token' => $token, 'expires_at' => now()->addDays(2)->toIso8601String()], 201);
    }

    public function accept(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:128']);
        DB::transaction(function () use ($request, $data) {
            $invitation = DB::table('garage_invitations')->where('token_hash', hash('sha256', $data['token']))->lockForUpdate()->first();
            abort_unless($invitation && ! $invitation->accepted_at && now()->lt($invitation->expires_at), 422, 'Invalid or expired invitation');
            $inviter = User::find($invitation->invited_by);
            abort_unless($inviter?->managesGarage() && $inviter->garage_id === $invitation->garage_id, 422, 'Invitation is no longer valid');
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->role === 'mechanic' && strtolower($user->email) === $invitation->email, 403);
            abort_if($user->garage_id !== null, 409, 'Already assigned to a workshop');
            $user->forceFill(['garage_id' => $invitation->garage_id, 'is_garage_manager' => false])->save();
            DB::table('garage_invitations')->where('id', $invitation->id)->update(['accepted_at' => now(), 'updated_at' => now()]);
        });

        return response()->json(['message' => 'Workshop invitation accepted']);
    }

    public function removeStaff(Request $request, User $user)
    {
        $this->requireManager($request);
        abort_unless($user->garage_id === $request->user()->garage_id && $user->role === 'mechanic', 404);
        abort_if($user->is_garage_manager, 409, 'Managers cannot be removed through this endpoint');
        $user->forceFill(['garage_id' => null])->save();

        return response()->json(['message' => 'Workshop access removed']);
    }

    public function grant(Request $request, Car $car, Garage $garage)
    {
        abort_unless($request->user()->role === 'owner' && $car->user_id === $request->user()->id, 403);
        DB::table('car_garage_access')->updateOrInsert(['car_id' => $car->id, 'garage_id' => $garage->id], [
            'owner_id' => $request->user()->id, 'granted_at' => now(), 'revoked_at' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Workshop access granted']);
    }

    public function revoke(Request $request, Car $car, Garage $garage)
    {
        abort_unless($car->user_id === $request->user()->id, 403);
        DB::table('car_garage_access')->where('car_id', $car->id)->where('garage_id', $garage->id)
            ->update(['revoked_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'Workshop access revoked']);
    }

    public function cars(Request $request)
    {
        $this->requireStaff($request);

        return CarResource::collection(Car::whereExists(function ($query) use ($request) {
            $query->selectRaw('1')->from('car_garage_access')->whereColumn('car_id', 'cars.id')
                ->whereColumn('owner_id', 'cars.user_id')->where('garage_id', $request->user()->garage_id)
                ->whereNull('revoked_at');
        })->filter($request->only('search', 'make', 'year'))->paginate(10));
    }

    public function access(Request $request, Car $car)
    {
        abort_unless($car->user_id === $request->user()->id, 403);

        return response()->json(['data' => DB::table('car_garage_access')->join('garages', 'garages.id', '=', 'car_garage_access.garage_id')
            ->where('car_id', $car->id)->where('owner_id', $request->user()->id)
            ->get(['garages.id', 'garages.name', 'garages.city', 'granted_at', 'revoked_at'])]);
    }

    public function services(Request $request)
    {
        $this->requireStaff($request);

        return ServiceRecordResource::collection(ServiceRecord::where('garage_id', $request->user()->garage_id)
            ->with('parts', 'garage', 'mechanic', 'car')->orderByDesc('service_date')->orderByDesc('id')->paginate(10));
    }

    public function dashboard(Request $request)
    {
        $this->requireManager($request);
        $records = ServiceRecord::where('garage_id', $request->user()->garage_id);

        return response()->json([
            'services_today' => (clone $records)->whereDate('service_date', today('Africa/Cairo'))->count(),
            'labor_total_this_month' => (clone $records)->whereBetween('service_date', [now('Africa/Cairo')->startOfMonth()->toDateString(), now('Africa/Cairo')->endOfMonth()->toDateString()])->sum('cost'),
            'staff_count' => User::where('garage_id', $request->user()->garage_id)->where('role', 'mechanic')->count(),
        ]);
    }

    private function requireManager(Request $request): void
    {
        abort_unless($request->user()->managesGarage(), 403);
    }

    private function requireStaff(Request $request): void
    {
        abort_unless($request->user()->role === 'mechanic' && $request->user()->garage_id !== null, 403);
    }
}
