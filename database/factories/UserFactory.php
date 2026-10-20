<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        $username = (string) Str::uuid();

        return [
            'isa_id' => User::isaIdFromUsername($username),
            'cognito_sub' => $username,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['is_admin' => true]);
    }

    /**
     * Added to a rescue by someone else, never logged in yet.
     */
    public function placeholder(): static
    {
        return $this->state(fn () => ['cognito_sub' => null]);
    }
}
