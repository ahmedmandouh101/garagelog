<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'make'         => fake()->randomElement(['Toyota', 'Honda', 'Hyundai', 'Kia', 'Chevrolet']),
            'model'        => fake()->word(),
            'year'         => fake()->numberBetween(2000, 2024),
            'plate_number' => fake()->unique()->bothify('???-####'),
            'color'        => fake()->colorName(),
            'mileage'      => fake()->numberBetween(0, 200000),
        ];
    }
}
