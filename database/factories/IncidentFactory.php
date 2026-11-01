<?php

    namespace Database\Factories;

    use App\Models\Category;
    use App\Models\Incident;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * @extends Factory<Incident>
     */
    class IncidentFactory extends Factory
    {
        protected $model = Incident::class;

        public function definition(): array
        {
            return [
                'category_id' => Category::factory(),
                'location' => 'SRID=4326;POINT(88.3639 22.5726)',
                'status' => 'OPEN',
            ];
        }
    }
