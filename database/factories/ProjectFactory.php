<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_name' => 'Projekt '.$this->faker->unique()->numberBetween(1000, 9999),
            'auftragsnummer_zf' => $this->faker->numberBetween(20000, 49999),
        ];
    }
}
