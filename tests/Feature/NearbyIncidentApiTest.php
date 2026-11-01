<?php

    namespace Tests\Feature;

    use App\Models\Category;
    use App\Models\Incident;
    use App\Models\Report;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class NearbyIncidentApiTest extends TestCase
    {
        use RefreshDatabase;

        private function createIncident(
            float $latitude,
            float $longitude
        ): Incident {
            return Incident::factory()->create([
                'category_id' => Category::factory(),
                'location' => sprintf(
                    'SRID=4326;POINT(%s %s)',
                    $longitude,
                    $latitude
                ),
            ]);
        }

        public function test_returns_incidents_within_radius(): void
        {
            $nearby = $this->createIncident(22.5730, 88.3640);
            $this->createIncident(22.6000, 88.4000);

            $this->getJson($this->nearbyUrl())
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $nearby->id);
        }

        public function test_excludes_incidents_outside_radius(): void
        {
            $this->createIncident(22.6000, 88.4000);

            $this->getJson($this->nearbyUrl())
                ->assertOk()
                ->assertJsonCount(0, 'data');
        }

        public function test_orders_incidents_by_nearest_distance(): void
        {
            $farther = $this->createIncident(22.5800, 88.3639);
            $nearest = $this->createIncident(22.5727, 88.3639);
            $middle = $this->createIncident(22.5750, 88.3639);

            $this->getJson($this->nearbyUrl())
                ->assertOk()
                ->assertJsonPath('data.0.id', $nearest->id)
                ->assertJsonPath('data.1.id', $middle->id)
                ->assertJsonPath('data.2.id', $farther->id);
        }

        public function test_returns_distance_in_metres(): void
        {
            $this->createIncident(22.5726, 88.3639);

            $response = $this->getJson($this->nearbyUrl());

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        ['id', 'location', 'distance_meters'],
                    ],
                ]);

            $this->assertEqualsWithDelta(
                0,
                $response->json('data.0.distance_meters'),
                1
            );
        }

        public function test_rejects_missing_coordinates(): void
        {
            $this->getJson('/api/incidents/nearby')
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'latitude',
                    'longitude',
                ]);
        }

        public function test_rejects_invalid_coordinates(): void
        {
            $this->getJson(
                '/api/incidents/nearby?latitude=91&longitude=181'
            )
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'latitude',
                    'longitude',
                ]);
        }

        public function test_rejects_invalid_radius(): void
        {
            foreach ([-1, 0, 10001, 'invalid'] as $radius) {
                $this->getJson(
                    $this->nearbyUrl() . '&radius=' . $radius
                )
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors('radius');
            }
        }

        public function test_uses_default_radius_of_1000_metres(): void
        {
            $nearby = $this->createIncident(22.5770, 88.3639);
            $this->createIncident(22.5900, 88.3639);

            $this->getJson(
                '/api/incidents/nearby?latitude=22.5726&longitude=88.3639'
            )
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $nearby->id);
        }

        public function test_endpoint_is_public(): void
        {
            $this->getJson($this->nearbyUrl())
                ->assertOk();
        }

        public function test_does_not_expose_sensitive_report_data(): void
        {
            $incident = $this->createIncident(22.5726, 88.3639);

            $response = $this->getJson($this->nearbyUrl());

            $response->assertOk()
                ->assertJsonPath('data.0.id', $incident->id)
                ->assertJsonMissingPath('data.0.reporter_email')
                ->assertJsonMissingPath('data.0.guest_token')
                ->assertJsonMissingPath('data.0.reports');
        }

        public function test_returns_empty_list_when_no_incidents_match(): void
        {
            $this->getJson($this->nearbyUrl())
                ->assertOk()
                ->assertJsonCount(0, 'data');
        }

        private function nearbyUrl(): string
        {
            return '/api/incidents/nearby'
                . '?latitude=22.5726'
                . '&longitude=88.3639'
                . '&radius=1000';
        }
    }
