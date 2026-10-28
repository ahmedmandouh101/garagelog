<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Garage;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WorkshopDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Demo data is restricted to local and testing environments.');
        }
        DB::transaction(function () {
            User::firstOrCreate(['email' => 'unverified@garagelog.example.test'], [
                'name' => 'حساب تجربة تأكيد البريد', 'password' => Hash::make('GarageLogDemo2026!'), 'role' => 'owner',
            ]);
            $garage = Garage::firstOrCreate(['name' => 'ورشة GarageLog التجريبية'], [
                'address' => 'عنوان تجريبي', 'city' => 'القاهرة', 'phone' => null,
            ]);
            $owner = User::firstOrCreate(['email' => 'owner@garagelog.example.test'], [
                'name' => 'عميل تجريبي', 'password' => Hash::make('GarageLogDemo2026!'), 'role' => 'owner',
            ]);
            $manager = User::firstOrCreate(['email' => 'manager@garagelog.example.test'], [
                'name' => 'مدير الورشة التجريبية', 'password' => Hash::make('GarageLogDemo2026!'),
                'role' => 'mechanic', 'garage_id' => $garage->id,
            ]);
            if ($manager->wasRecentlyCreated) {
                $manager->forceFill(['is_garage_manager' => true])->save();
            }
            // Fictional local demo identities only; real accounts must follow email verification.
            $owner->forceFill(['email_verified_at' => $owner->email_verified_at ?? now()])->save();
            $manager->forceFill(['email_verified_at' => $manager->email_verified_at ?? now()])->save();
            $car = Car::firstOrCreate(['plate_number' => 'DEMO-GL-2026'], [
                'user_id' => $owner->id, 'make' => 'Toyota', 'model' => 'Corolla',
                'year' => 2020, 'mileage' => 65000, 'color' => 'أبيض',
            ]);
            if ($car->user_id !== $owner->id) {
                throw new \RuntimeException('Demo plate is already in use by another owner.');
            }
            DB::table('car_garage_access')->insertOrIgnore([
                'car_id' => $car->id, 'garage_id' => $garage->id, 'owner_id' => $owner->id,
                'granted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $record = ServiceRecord::firstOrCreate([
                'car_id' => $car->id, 'garage_id' => $garage->id,
                'mechanic_id' => $manager->id, 'service_type' => 'تغيير زيت وفلتر — تجربة',
            ], [
                'description' => 'بيانات توضيحية لتجربة النظام، ليست صيانة فعلية.',
                'service_date' => today()->toDateString(), 'mileage_at_service' => 65000,
                'cost' => 200, 'next_service_date' => today()->addMonths(3)->toDateString(),
            ]);
            if ($record->wasRecentlyCreated) {
                $record->parts()->create(['part_name' => 'فلتر زيت تجريبي', 'quantity' => 1, 'price' => 250]);
            }
        });
    }
}
