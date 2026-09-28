<?php

namespace Database\Factories;

use App\Models\Licence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Licence>
 */
class LicenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->numerify('Licence ###'),
        ];
    }
}
