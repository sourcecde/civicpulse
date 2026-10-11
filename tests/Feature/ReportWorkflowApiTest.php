<?php

    namespace Tests\Feature;

    use App\Enums\ReportStatus;
    use App\Models\Report;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class ReportWorkflowApiTest extends TestCase
    {
        use RefreshDatabase;

        public function test_guest_cannot_assign_report(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->open()->create();

            $this->postJson("/api/reports/{$report->id}/assign", [
                'operator_id' => $operator->id,
            ])->assertUnauthorized();
        }

        public function test_operator_can_assign_report(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->open()->create();

            Sanctum::actingAs($operator);

            $this->postJson("/api/reports/{$report->id}/assign", [
                'operator_id' => $operator->id,
                'reason' => 'Road maintenance',
            ])->assertOk()
                ->assertJsonPath('data.status', ReportStatus::ASSIGNED->value);

            $this->assertEquals(
                $operator->id,
                $report->fresh()->assigned_operator_id
            );

            $this->assertDatabaseCount('report_status_histories', 1);
        }

        public function test_citizen_cannot_assign_report(): void
        {
            $citizen = User::factory()->citizen()->create();
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->open()->create();

            Sanctum::actingAs($citizen);

            $this->postJson("/api/reports/{$report->id}/assign", [
                'operator_id' => $operator->id,
            ])->assertForbidden();

            $this->assertSame(ReportStatus::OPEN, $report->fresh()->status);
        }

        public function test_operator_can_start_assigned_work(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->assignedTo($operator)->create();

            Sanctum::actingAs($operator);

            $this->patchJson("/api/reports/{$report->id}/status", [
                'status' => ReportStatus::IN_PROGRESS->value,
                'reason' => 'Work started',
            ])->assertOk()
                ->assertJsonPath('data.status', ReportStatus::IN_PROGRESS->value);

            $this->assertNotNull($report->fresh()->started_at);
            $this->assertDatabaseCount('report_status_histories', 1);
        }

        public function test_unassigned_operator_cannot_change_status(): void
        {
            $assignedOperator = User::factory()->operator()->create();
            $otherOperator = User::factory()->operator()->create();

            $report = Report::factory()
                ->assignedTo($assignedOperator)
                ->create();

            Sanctum::actingAs($otherOperator);

            $this->patchJson("/api/reports/{$report->id}/status", [
                'status' => ReportStatus::IN_PROGRESS->value,
            ])->assertForbidden();

            $this->assertSame(ReportStatus::ASSIGNED, $report->fresh()->status);
        }

        public function test_invalid_status_transition_is_rejected(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->assignedTo($operator)->create();

            Sanctum::actingAs($operator);

            $this->patchJson("/api/reports/{$report->id}/status", [
                'status' => ReportStatus::RESOLVED->value,
            ])->assertUnprocessable();

            $this->assertSame(ReportStatus::ASSIGNED, $report->fresh()->status);
            $this->assertDatabaseCount('report_status_histories', 0);
        }

        public function test_operator_can_view_report_history(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->open()->create();

            Sanctum::actingAs($operator);

            $this->postJson("/api/reports/{$report->id}/assign", [
                'operator_id' => $operator->id,
            ])->assertOk();

            $this->getJson("/api/reports/{$report->id}/history")
                ->assertOk()
                ->assertJsonCount(1)
                ->assertJsonPath('0.to_status', ReportStatus::ASSIGNED->value);
        }

        public function test_guest_cannot_view_report_history(): void
        {
            $report = Report::factory()->open()->create();

            $this->getJson("/api/reports/{$report->id}/history")
                ->assertUnauthorized();
        }

        public function test_invalid_operator_id_is_rejected(): void
        {
            $admin = User::factory()->admin()->create();
            $report = Report::factory()->open()->create();

            Sanctum::actingAs($admin);

            $this->postJson("/api/reports/{$report->id}/assign", [
                'operator_id' => 999999,
            ])->assertUnprocessable();

            $this->assertSame(ReportStatus::OPEN, $report->fresh()->status);
        }
    }
