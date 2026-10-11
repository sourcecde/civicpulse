<?php

    namespace Tests\Feature;

    use App\Enums\ReportStatus;
    use App\Models\Report;
    use App\Models\User;
    use App\Services\ReportWorkflowService;
    use Illuminate\Auth\Access\AuthorizationException;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Validation\ValidationException;
    use Tests\TestCase;

    class ReportWorkflowTest extends TestCase
    {
        use RefreshDatabase;

        private function workflow(): ReportWorkflowService
        {
            return app(ReportWorkflowService::class);
        }

        public function test_operator_can_assign_open_report(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->open()->create();

            $updated = $this->workflow()->assign(
                $report,
                $operator,
                $operator,
                'Taking ownership'
            );

            $this->assertSame(ReportStatus::ASSIGNED, $updated->status);
            $this->assertEquals($operator->id, $updated->assigned_operator_id);
            $this->assertNotNull($updated->assigned_at);
            $this->assertEquals(1, $updated->version);

            $this->assertDatabaseHas('report_status_histories', [
                'report_id' => $report->id,
                'actor_id' => $operator->id,
                'from_status' => ReportStatus::OPEN->value,
                'to_status' => ReportStatus::ASSIGNED->value,
                'reason' => 'Taking ownership',
            ]);
        }

        public function test_citizen_cannot_assign_report(): void
        {
            $citizen = User::factory()->citizen()->create();
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->open()->create();

            try {
                $this->workflow()->assign($report, $operator, $citizen);
                $this->fail('Expected authorization exception.');
            } catch (AuthorizationException $e) {
                // Expected.
            }

            $this->assertSame(ReportStatus::OPEN, $report->fresh()->status);
            $this->assertNull($report->fresh()->assigned_operator_id);
            $this->assertDatabaseCount('report_status_histories', 0);
        }

        public function test_assigned_operator_can_start_work(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->assignedTo($operator)->create();

            $updated = $this->workflow()->transition(
                $report,
                ReportStatus::IN_PROGRESS,
                $operator,
                'Work started'
            );

            $this->assertSame(ReportStatus::IN_PROGRESS, $updated->status);
            $this->assertNotNull($updated->started_at);
            $this->assertEquals(2, $updated->version);

            $this->assertDatabaseHas('report_status_histories', [
                'report_id' => $report->id,
                'actor_id' => $operator->id,
                'from_status' => ReportStatus::ASSIGNED->value,
                'to_status' => ReportStatus::IN_PROGRESS->value,
                'reason' => 'Work started',
            ]);
        }

        public function test_assigned_operator_can_resolve_report(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->inProgressWith($operator)->create();

            $updated = $this->workflow()->transition(
                $report,
                ReportStatus::RESOLVED,
                $operator,
                'Issue fixed'
            );

            $this->assertSame(ReportStatus::RESOLVED, $updated->status);
            $this->assertNotNull($updated->resolved_at);
            $this->assertEquals(3, $updated->version);

            $this->assertDatabaseHas('report_status_histories', [
                'report_id' => $report->id,
                'from_status' => ReportStatus::IN_PROGRESS->value,
                'to_status' => ReportStatus::RESOLVED->value,
                'reason' => 'Issue fixed',
            ]);
        }

        public function test_report_cannot_skip_workflow_statuses(): void
        {
            $operator = User::factory()->operator()->create();
            $report = Report::factory()->assignedTo($operator)->create();

            try {
                $this->workflow()->transition(
                    $report,
                    ReportStatus::RESOLVED,
                    $operator
                );

                $this->fail('Expected validation exception.');
            } catch (ValidationException $e) {
                // Expected.
            }

            $this->assertSame(ReportStatus::ASSIGNED, $report->fresh()->status);
            $this->assertEquals(1, $report->fresh()->version);
            $this->assertDatabaseCount('report_status_histories', 0);
        }

        public function test_unassigned_operator_cannot_change_report_status(): void
        {
            $assignedOperator = User::factory()->operator()->create();
            $otherOperator = User::factory()->operator()->create();

            $report = Report::factory()
                ->assignedTo($assignedOperator)
                ->create();

            try {
                $this->workflow()->transition(
                    $report,
                    ReportStatus::IN_PROGRESS,
                    $otherOperator
                );

                $this->fail('Expected authorization exception.');
            } catch (AuthorizationException $e) {
                // Expected.
            }

            $this->assertSame(ReportStatus::ASSIGNED, $report->fresh()->status);
            $this->assertDatabaseCount('report_status_histories', 0);
        }

        public function test_report_cannot_be_assigned_twice(): void
        {
            $firstOperator = User::factory()->operator()->create();
            $secondOperator = User::factory()->operator()->create();

            $report = Report::factory()->open()->create();

            $this->workflow()->assign(
                $report,
                $firstOperator,
                $firstOperator
            );

            try {
                $this->workflow()->assign(
                    $report,
                    $secondOperator,
                    $secondOperator
                );

                $this->fail('Expected validation exception.');
            } catch (ValidationException $e) {
                // Expected.
            }

            $this->assertEquals(
                $firstOperator->id,
                $report->fresh()->assigned_operator_id
            );

            $this->assertDatabaseCount('report_status_histories', 1);
        }

        public function test_citizen_cannot_change_report_status(): void
        {
            $operator = User::factory()->operator()->create();
            $citizen = User::factory()->citizen()->create();

            $report = Report::factory()->assignedTo($operator)->create();

            try {
                $this->workflow()->transition(
                    $report,
                    ReportStatus::IN_PROGRESS,
                    $citizen
                );

                $this->fail('Expected authorization exception.');
            } catch (AuthorizationException $e) {
                // Expected.
            }

            $this->assertSame(ReportStatus::ASSIGNED, $report->fresh()->status);
            $this->assertDatabaseCount('report_status_histories', 0);
        }

        public function test_non_operator_cannot_be_assigned(): void
        {
            $admin = User::factory()->admin()->create();
            $citizen = User::factory()->citizen()->create();
            $report = Report::factory()->open()->create();

            try {
                $this->workflow()->assign($report, $citizen, $admin);
                $this->fail('Expected validation exception.');
            } catch (ValidationException $e) {
                // Expected.
            }

            $this->assertSame(ReportStatus::OPEN, $report->fresh()->status);
            $this->assertNull($report->fresh()->assigned_operator_id);
            $this->assertDatabaseCount('report_status_histories', 0);
        }
    }
