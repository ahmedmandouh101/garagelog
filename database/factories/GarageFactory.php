<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GarageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'    => fake()->company() . ' Garage',
            'address' => fake()->streetAddress(),
            'city'    => fake()->randomElement(['Cairo', 'Alexandria', 'Giza', 'Mansoura']),
            'phone'   => fake()->phoneNumber(),
        ];
    }
}
