<?php

    namespace Database\Seeders;

    use App\Enums\ReportStatus;
    use App\Models\Category;
    use App\Models\Report;
    use App\Models\User;
    use App\Services\ReportWorkflowService;
    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Hash;
    use RuntimeException;

    class CivicPulseDemoSeeder extends Seeder
    {
        public function run(): void
        {
            if (app()->environment('production')) {
                throw new RuntimeException('Demo seeding is disabled in production.');
            }

            DB::transaction(function () {
                $workflow = app(ReportWorkflowService::class);

                $categories = collect([
                    'Road Damage' => 'Potholes and damaged road surfaces',
                    'Street Lighting' => 'Broken or malfunctioning public lights',
                    'Waste Management' => 'Waste collection and illegal dumping',
                    'Water Supply' => 'Water leaks and supply problems',
                    'Public Infrastructure' => 'Damaged public facilities',
                ])->mapWithKeys(function ($description, $name) {
                    $category = Category::firstOrCreate(
                        ['name' => $name],
                        ['description' => $description, 'is_active' => true]
                    );

                    return [$name => $category];
                })->values();

                $admin = $this->demoUser('admin', 1, 'admin');

                $operators = collect(range(1, 3))
                    ->map(fn ($i) => $this->demoUser('operator', $i, 'operator'));

                $citizens = collect(range(1, 5))
                    ->map(fn ($i) => $this->demoUser('citizen', $i, 'citizen'));

                $descriptions = [
                    'Large pothole near the main road',
                    'Streetlight not working after sunset',
                    'Overflowing public waste container',
                    'Water leaking from a roadside pipe',
                    'Damaged pedestrian walkway',
                    'Broken public bench near the park',
                ];

                for ($i = 1; $i <= 30; $i++) {
                    $trackingNumber = sprintf('CP-DEMO-%04d', $i);

                    // Existing demo reports are left unchanged on reruns.
                    if (Report::where('tracking_number', $trackingNumber)->exists()) {
                        continue;
                    }

                    $citizen = $citizens[($i - 1) % $citizens->count()];
                    $category = $categories[($i - 1) % $categories->count()];
                    $operator = $operators[($i - 1) % $operators->count()];

                    $report = Report::factory()->open()->create([
                        'category_id' => $category->id,
                        'description' => $descriptions[($i - 1) % count($descriptions)],
                        'tracking_number' => $trackingNumber,
                        'reporter_id' => $citizen->id,
                        'reporter_email' => $citizen->email,
                    ]);

                    // 1–8 OPEN, 9–16 ASSIGNED,
                    // 17–23 IN_PROGRESS, 24–30 RESOLVED.
                    if ($i <= 8) {
                        continue;
                    }

                    $report = $workflow->assign(
                        $report,
                        $operator,
                        $admin,
                        'Demo operator assignment'
                    );

                    if ($i <= 16) {
                        continue;
                    }

                    $report = $workflow->transition(
                        $report,
                        ReportStatus::IN_PROGRESS,
                        $operator,
                        'Demo work started'
                    );

                    if ($i <= 23) {
                        continue;
                    }

                    $workflow->transition(
                        $report,
                        ReportStatus::RESOLVED,
                        $operator,
                        'Demo issue resolved'
                    );
                }
            });

            $this->command?->info('CivicPulse demo data is ready.');
        }

        private function demoUser(string $role, int $number, string $factoryState): User
        {
            $email = sprintf('%s%d@civicpulse.test', $role, $number);

            $existing = User::where('email', $email)->first();

            if ($existing) {
                return $existing;
            }

            $factory = User::factory();

            $factory = match ($factoryState) {
                'admin' => $factory->admin(),
                'operator' => $factory->operator(),
                'citizen' => $factory->citizen(),
            };

            return $factory->create([
                'name' => ucfirst($role) . ' ' . $number,
                'email' => $email,
                'password' => Hash::make('DemoOnly123!'),
            ]);
        }
    }
