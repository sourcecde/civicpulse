<?php

    namespace Tests\Feature;

    use App\Models\Category;
    use App\Models\Incident;
    use App\Models\Report;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class ReportIncidentCreationTest extends TestCase
    {
        use RefreshDatabase;

        public function test_submitting_report_creates_linked_incident(): void
        {
            $category = Category::factory()->create();

            $response = $this->postJson('/api/reports', [
                'category_id' => $category->id,
                'description' => 'Large pothole near the bus stop',
                'latitude' => 22.5726,
                'longitude' => 88.3639,
                'reporter_email' => 'citizen@example.com',
            ]);

            $response->assertSuccessful();

            $this->assertDatabaseCount('reports', 1);
            $this->assertDatabaseCount('incidents', 1);

            $report = Report::firstOrFail();
            $incident = Incident::firstOrFail();

            $this->assertEquals($incident->id, $report->incident_id);
            $this->assertEquals($category->id, $incident->category_id);
            $this->assertEquals('OPEN', $incident->status);

            $this->assertNotEmpty($report->tracking_number);
            $this->assertNotEmpty(
                $response->json('guest_access_token')
            );
        }

        public function test_invalid_report_does_not_create_incident(): void
        {
            $response = $this->postJson('/api/reports', [
                'description' => 'Missing category and coordinates',
                'reporter_email' => 'citizen@example.com',
            ]);

            $response->assertUnprocessable();

            $this->assertDatabaseCount('reports', 0);
            $this->assertDatabaseCount('incidents', 0);
        }

        public function test_multiple_submissions_create_separate_incidents_for_now(): void
        {
            $category = Category::factory()->create();

            $payload = [
                'category_id' => $category->id,
                'description' => 'Pothole on the main road',
                'latitude' => 22.5726,
                'longitude' => 88.3639,
                'reporter_email' => 'citizen@example.com',
            ];

            $this->postJson('/api/reports', $payload)->assertSuccessful();
            $this->postJson('/api/reports', $payload)->assertSuccessful();

            $this->assertDatabaseCount('reports', 2);
            $this->assertDatabaseCount('incidents', 2);

            $this->assertEquals(
                2,
                Report::distinct()->count('incident_id')
            );
        }
    }
