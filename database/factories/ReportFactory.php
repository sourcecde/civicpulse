<?php

    namespace Database\Factories;

    use App\Enums\ReportStatus;
    use App\Models\Category;
    use App\Models\Report;
    use App\Models\User;
    use Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * @extends Factory<Report>
     */
    class ReportFactory extends Factory
    {
        public function definition(): array
        {
            return [
                'category_id' => Category::factory(),
                'description' => $this->faker->sentence(),
                'location' => 'SRID=4326;POINT(88.3639 22.5726)',
                'tracking_number' => 'CP-' . strtoupper($this->faker->unique()->bothify('??####')),
                'reporter_email' => $this->faker->safeEmail(),
                'status' => ReportStatus::OPEN,
                'version' => 0,
            ];
        }

        public function open(): static
        {
            return $this->state([
                'status' => ReportStatus::OPEN,
                'assigned_operator_id' => null,
                'assigned_at' => null,
                'started_at' => null,
                'resolved_at' => null,
                'version' => 0,
            ]);
        }

        public function assignedTo(User $operator): static
        {
            return $this->state([
                'status' => ReportStatus::ASSIGNED,
                'assigned_operator_id' => $operator->id,
                'assigned_at' => now(),
                'started_at' => null,
                'resolved_at' => null,
                'version' => 1,
            ]);
        }

        public function inProgressWith(User $operator): static
        {
            return $this->state([
                'status' => ReportStatus::IN_PROGRESS,
                'assigned_operator_id' => $operator->id,
                'assigned_at' => now()->subDay(),
                'started_at' => now(),
                'resolved_at' => null,
                'version' => 2,
            ]);
        }

        public function resolvedWith(User $operator): static
        {
            return $this->state([
                'status' => ReportStatus::RESOLVED,
                'assigned_operator_id' => $operator->id,
                'assigned_at' => now()->subDays(2),
                'started_at' => now()->subDay(),
                'resolved_at' => now(),
                'version' => 3,
            ]);
        }
    }
