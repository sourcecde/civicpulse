<?php

    namespace Tests\Feature;

    use App\Models\Category;
    use App\Models\Incident;
    use App\Models\Report;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Str;
    use Tests\TestCase;

    class IncidentApiTest extends TestCase
    {
        use RefreshDatabase;

        private function createIncident(
            ?Category $category = null,
            float $latitude = 22.5726,
            float $longitude = 88.3639
        ): Incident {
            $category ??= Category::factory()->create();

            return Incident::factory()->create([
                'category_id' => $category->id,
                'location' => sprintf(
                    'SRID=4326;POINT(%s %s)',
                    $longitude,
                    $latitude
                ),
            ]);
        }

        public function test_public_can_list_incidents(): void
        {
            $incident = $this->createIncident();

            $response = $this->getJson('/api/incidents');

            $response->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $incident->id)
                ->assertJsonPath('data.0.status', 'OPEN')
                ->assertJsonPath('data.0.reports_count', 0)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'category',
                            'status',
                            'location',
                            'reports_count',
                            'created_at',
                        ],
                    ],
                    'links',
                    'meta',
                ]);
        }

        public function test_public_can_view_incident_details(): void
        {
            $incident = $this->createIncident();

            $response = $this->getJson(
                "/api/incidents/{$incident->id}"
            );

            $response->assertOk()
                ->assertJsonPath('data.id', $incident->id)
                ->assertJsonPath(
                    'data.category.id',
                    $incident->category_id
                )
                ->assertJsonPath('data.status', 'OPEN')
                ->assertJsonPath('data.reports_count', 0);
        }

        public function test_incident_returns_correct_gps_coordinates(): void
        {
            $incident = $this->createIncident(
                latitude: 22.5726,
                longitude: 88.3639
            );

            $response = $this->getJson(
                "/api/incidents/{$incident->id}"
            );

            $response->assertOk();

            $this->assertEqualsWithDelta(
                22.5726,
                $response->json('data.location.latitude'),
                0.000001
            );

            $this->assertEqualsWithDelta(
                88.3639,
                $response->json('data.location.longitude'),
                0.000001
            );
        }

        public function test_incident_counts_linked_reports(): void
        {
            $incident = $this->createIncident();

            Report::factory()->count(3)->create([
                'incident_id' => $incident->id,
                'category_id' => $incident->category_id,
            ]);

            $response = $this->getJson(
                "/api/incidents/{$incident->id}"
            );

            $response->assertOk()
                ->assertJsonPath('data.reports_count', 3);

            $this->assertDatabaseCount('reports', 3);
        }

        public function test_incident_list_is_paginated(): void
        {
            Incident::factory()->count(18)->create();

            $response = $this->getJson('/api/incidents');

            $response->assertOk()
                ->assertJsonCount(15, 'data')
                ->assertJsonPath('meta.total', 18)
                ->assertJsonPath('meta.per_page', 15)
                ->assertJsonPath('meta.current_page', 1)
                ->assertJsonPath('meta.last_page', 2);

            $secondPage = $this->getJson(
                '/api/incidents?page=2'
            );

            $secondPage->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonPath('meta.current_page', 2);
        }

        public function test_incident_api_does_not_expose_private_report_data(): void
        {
            $incident = $this->createIncident();

            Report::factory()->create([
                'incident_id' => $incident->id,
                'category_id' => $incident->category_id,
                'reporter_email' => 'private-citizen@example.com',
                'guest_token_hash' => hash(
                    'sha256',
                    'private-test-token'
                ),
            ]);

            $response = $this->getJson(
                "/api/incidents/{$incident->id}"
            );

            $response->assertOk()
                ->assertJsonMissingPath('data.reporter_email')
                ->assertJsonMissingPath('data.guest_token_hash')
                ->assertJsonMissingPath('data.reports')
                ->assertDontSee('private-citizen@example.com')
                ->assertDontSee('private-test-token');
        }

        public function test_incident_list_does_not_expose_private_report_data(): void
        {
            $incident = $this->createIncident();

            Report::factory()->create([
                'incident_id' => $incident->id,
                'category_id' => $incident->category_id,
                'reporter_email' => 'hidden@example.com',
            ]);

            $response = $this->getJson('/api/incidents');

            $response->assertOk()
                ->assertDontSee('hidden@example.com')
                ->assertJsonMissingPath('data.0.reporter_email')
                ->assertJsonMissingPath('data.0.guest_token_hash');
        }

        public function test_nonexistent_incident_returns_404(): void
        {
            $response = $this->getJson(
                '/api/incidents/' . Str::uuid()
            );

            $response->assertNotFound();
        }

        public function test_incident_list_can_be_empty(): void
        {
            $response = $this->getJson('/api/incidents');

            $response->assertOk()
                ->assertJsonCount(0, 'data')
                ->assertJsonPath('meta.total', 0);
        }

        public function test_incident_category_is_returned(): void
        {
            $category = Category::factory()->create();
            $incident = $this->createIncident($category);

            $response = $this->getJson(
                "/api/incidents/{$incident->id}"
            );

            $response->assertOk()
                ->assertJsonPath(
                    'data.category.id',
                    $category->id
                )
                ->assertJsonPath(
                    'data.category.name',
                    $category->name
                );
        }
    }
