<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id'               => Car::factory(),
            'garage_id'            => Garage::factory(),
            'mechanic_id'          => User::factory()->create(['role' => 'mechanic'])->id,
            'service_type'         => fake()->randomElement(['Oil Change', 'Brake Check', 'Tire Rotation', 'Battery Check']),
            'description'          => fake()->sentence(),
            'mileage_at_service'   => fake()->numberBetween(1000, 200000),
            'cost'                 => fake()->randomFloat(2, 100, 2000),
            'service_date'         => fake()->dateTimeBetween('-1 year', 'now'),
            'next_service_date'    => fake()->dateTimeBetween('now', '+1 year'),
            'next_service_mileage' => fake()->numberBetween(200000, 300000),
        ];
    }
}
