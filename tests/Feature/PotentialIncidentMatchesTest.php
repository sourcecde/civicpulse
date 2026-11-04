<?php

    namespace Tests\Feature;

    use App\Models\Category;
    use App\Models\Incident;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class PotentialIncidentMatchesTest extends TestCase
    {
        use RefreshDatabase;

        public function test_returns_matching_incident_within_50_metres(): void
        {
            $category = Category::factory()->create();

            $incident = Incident::factory()->create([
                'category_id' => $category->id,
                'location' => 'SRID=4326;POINT(88.3639 22.5726)',
                'status' => 'OPEN',
            ]);

            $this->postJson('/api/incidents/potential-matches', [
                'category_id' => $category->id,
                'latitude' => 22.5727,
                'longitude' => 88.3639,
            ])
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $incident->id);
        }

        public function test_excludes_incidents_outside_50_metres(): void
        {
            $category = Category::factory()->create();

            Incident::factory()->create([
                'category_id' => $category->id,
                'location' => 'SRID=4326;POINT(88.3639 22.5735)',
                'status' => 'OPEN',
            ]);

            $this->postJson('/api/incidents/potential-matches', [
                'category_id' => $category->id,
                'latitude' => 22.5726,
                'longitude' => 88.3639,
            ])
                ->assertOk()
                ->assertJsonCount(0, 'data');
        }

        public function test_rejects_null_required_fields(): void
        {
            $this->postJson('/api/incidents/potential-matches', [
                'category_id' => null,
                'latitude' => null,
                'longitude' => null,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'category_id',
                    'latitude',
                    'longitude',
                ]);
        }

        public function test_rejects_invalid_input_values(): void
        {
            $this->postJson('/api/incidents/potential-matches', [
                'category_id' => -1,
                'latitude' => 100,
                'longitude' => 200,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'category_id',
                    'latitude',
                    'longitude',
                ]);
        }

        public function test_returns_empty_list_when_no_incidents_exist(): void
        {
            $category = Category::factory()->create();

            $this->postJson('/api/incidents/potential-matches', [
                'category_id' => $category->id,
                'latitude' => 22.5726,
                'longitude' => 88.3639,
            ])
                ->assertOk()
                ->assertJsonCount(0, 'data');
        }
    }
