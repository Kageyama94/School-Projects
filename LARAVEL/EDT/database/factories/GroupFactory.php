<?php

namespace Database\Factories;

use App\Enums\Level;
use App\Models\Group;
use App\Models\Licence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'licence_id' => Licence::factory(),
            'level' => fake()->randomElement(Level::cases()),
            'name' => fake()->unique()->numerify('Groupe ##'),
        ];
    }
}
