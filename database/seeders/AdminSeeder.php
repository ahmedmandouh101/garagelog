<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Demo admin seeding is disabled outside local/testing. Use app:create-admin.');
        }
        User::firstOrCreate(['email' => 'admin@garagelog.com'], [
            'name' => 'Super Admin',
            'email' => 'admin@garagelog.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '01000000000',
        ]);
    }
}
