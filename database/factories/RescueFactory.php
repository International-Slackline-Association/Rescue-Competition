<?php

namespace Database\Factories;

use App\Models\Rescue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rescue>
 */
class RescueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'happened_on' => fake()->dateTimeBetween('-1 year'),
            'location' => fake()->city(),
            'description' => fake()->sentence(),
            'image_path' => 'rescues/example.jpg',
        ];
    }
}
