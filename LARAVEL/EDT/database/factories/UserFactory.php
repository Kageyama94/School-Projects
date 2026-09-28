<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifiant' => fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'role' => UserRole::Student,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function teacher(): static
    {
        return $this->state(['role' => UserRole::Teacher]);
    }

    public function student(): static
    {
        return $this->state(['role' => UserRole::Student]);
    }
}
