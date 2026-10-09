<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'description' => $this->faker->sentence(),
            'location' => 'SRID=4326;POINT(88.3639 22.5726)',
            'tracking_number' => 'CP-' . strtoupper($this->faker->unique()->bothify('??####')),
            'reporter_email' => $this->faker->safeEmail(),
        ];
    }

}
