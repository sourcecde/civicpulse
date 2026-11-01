<?php

    namespace Tests\Feature;

    use App\Models\Incident;
    use App\Models\Report;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class IncidentRelationshipTest extends TestCase
    {
        use RefreshDatabase;

        public function test_incident_can_have_multiple_reports(): void
        {
            $incident = Incident::factory()->create();

            $report1 = Report::factory()->create([
                'incident_id' => $incident->id,
            ]);

            $report2 = Report::factory()->create([
                'incident_id' => $incident->id,
            ]);

            $this->assertCount(2, $incident->reports);

            $this->assertTrue(
                $incident->reports->contains($report1)
            );

            $this->assertTrue(
                $incident->reports->contains($report2)
            );
        }

        public function test_report_belongs_to_an_incident(): void
        {
            $incident = Incident::factory()->create();

            $report = Report::factory()->create([
                'incident_id' => $incident->id,
            ]);

            $this->assertEquals(
                $incident->id,
                $report->incident->id
            );
        }

        public function test_report_can_exist_without_an_incident(): void
        {
            $report = Report::factory()->create([
                'incident_id' => null,
            ]);

            $this->assertNull($report->incident);
        }
    }
