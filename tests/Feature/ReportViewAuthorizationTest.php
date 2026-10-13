<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * ReportPolicy::view guards GET /api/reports/{id} and GET /api/reports/{report}/history.
 */
class ReportViewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_report(): void
    {
        $report = Report::factory()->create();

        $this->getJson("/api/reports/{$report->id}")->assertUnauthorized();
    }

    public function test_operator_can_view_any_report(): void
    {
        $report = Report::factory()->create([
            'reporter_id' => User::factory()->citizen()->create()->id,
        ]);

        Sanctum::actingAs(User::factory()->operator()->create());

        $this->getJson("/api/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id);
        $this->getJson("/api/reports/{$report->id}/history")->assertOk();
    }

    public function test_admin_can_view_any_report(): void
    {
        $report = Report::factory()->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson("/api/reports/{$report->id}")->assertOk();
        $this->getJson("/api/reports/{$report->id}/history")->assertOk();
    }

    public function test_citizen_can_view_own_report_and_history(): void
    {
        $citizen = User::factory()->citizen()->create();
        $report = Report::factory()->create(['reporter_id' => $citizen->id]);

        Sanctum::actingAs($citizen);

        $this->getJson("/api/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id);
        $this->getJson("/api/reports/{$report->id}/history")->assertOk();
    }

    public function test_citizen_cannot_view_another_citizens_report_or_history(): void
    {
        $owner = User::factory()->citizen()->create();
        $report = Report::factory()->create(['reporter_id' => $owner->id]);

        Sanctum::actingAs(User::factory()->citizen()->create());

        $this->getJson("/api/reports/{$report->id}")->assertForbidden();
        $this->getJson("/api/reports/{$report->id}/history")->assertForbidden();
    }

    public function test_citizen_cannot_view_guest_submitted_report(): void
    {
        // Guest reports have no reporter_id; only the tracking-number lookup can reach them.
        $report = Report::factory()->create(['reporter_id' => null]);

        Sanctum::actingAs(User::factory()->citizen()->create());

        $this->getJson("/api/reports/{$report->id}")->assertForbidden();
    }
}
