<?php

    namespace Tests\Feature;

    use App\Models\Category;
    use App\Models\Incident;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class ReportIncidentLinkingTest extends TestCase
    {
        use RefreshDatabase;

        private function reportPayload(int $categoryId, array $overrides = []): array
        {
            return array_merge([
                'category_id' => $categoryId,
                'description' => 'Streetlight is not working',
                'latitude' => 22.5726,
                'longitude' => 88.3639,
                'reporter_email' => 'citizen@example.com',
            ], $overrides);
        }

        public function test_links_report_to_existing_open_incident(): void
        {
            $category = Category::factory()->create();

            $incident = Incident::factory()->create([
                'category_id' => $category->id,
                'location' => 'SRID=4326;POINT(88.3639 22.5726)',
                'status' => 'OPEN',
            ]);

            $response = $this->postJson('/api/reports', $this->reportPayload(
                $category->id,
                ['incident_id' => $incident->id]
            ));

            $response->assertSuccessful();

            $this->assertDatabaseCount('incidents', 1);
            $this->assertDatabaseCount('reports', 1);

            $this->assertDatabaseHas('reports', [
                'incident_id' => $incident->id,
                'category_id' => $category->id,
            ]);
        }

        public function test_rejects_incident_with_different_category(): void
        {
            $reportCategory = Category::factory()->create();
            $incidentCategory = Category::factory()->create();

            $incident = Incident::factory()->create([
                'category_id' => $incidentCategory->id,
                'location' => 'SRID=4326;POINT(88.3639 22.5726)',
                'status' => 'OPEN',
            ]);

            $response = $this->postJson('/api/reports', $this->reportPayload(
                $reportCategory->id,
                ['incident_id' => $incident->id]
            ));

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['incident_id']);

            $this->assertDatabaseCount('reports', 0);
            $this->assertDatabaseCount('incidents', 1);
        }

        public function test_creates_new_incident_when_incident_id_is_null(): void
        {
            $category = Category::factory()->create();

            $response = $this->postJson('/api/reports', $this->reportPayload(
                $category->id,
                ['incident_id' => null]
            ));

            $response->assertSuccessful();

            $this->assertDatabaseCount('incidents', 1);
            $this->assertDatabaseCount('reports', 1);

            $this->assertNotNull(
                $response->json('data.incident_id')
                ?? \App\Models\Report::firstOrFail()->incident_id
            );
        }

        public function test_rejects_invalid_incident_id(): void
        {
            $category = Category::factory()->create();

            $response = $this->postJson('/api/reports', $this->reportPayload(
                $category->id,
                ['incident_id' => 'invalid-incident-id']
            ));

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['incident_id']);

            $this->assertDatabaseCount('incidents', 0);
            $this->assertDatabaseCount('reports', 0);
        }

        public function test_creates_new_incident_when_incident_id_is_missing(): void
        {
            $category = Category::factory()->create();

            $response = $this->postJson(
                '/api/reports',
                $this->reportPayload($category->id)
            );

            $response->assertSuccessful();

            $this->assertDatabaseCount('incidents', 1);
            $this->assertDatabaseCount('reports', 1);
        }
    }
