<?php

    namespace App\Services;

    use App\Enums\ReportStatus;
    use App\Models\Report;
    use App\Models\ReportStatusHistory;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Validation\ValidationException;
    use Illuminate\Support\Facades\Gate;

    class ReportWorkflowService
    {
        public function assign(
            Report $report,
            User $operator,
            User $actor,
            ?string $reason = null
        ): Report {
            return DB::transaction(function () use ($report, $operator, $actor, $reason) {


                $lockedReport = Report::query()
                    ->whereKey($report->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::forUser($actor)->authorize('assign', $lockedReport);

                if ($operator->role !== \App\Enums\UserRole::OPERATOR) {
                    throw ValidationException::withMessages([
                        'operator_id' => 'The selected user is not an operator.',
                    ]);
                }

                if ($lockedReport->status !== ReportStatus::OPEN) {
                    throw ValidationException::withMessages([
                        'status' => 'Only OPEN reports can be assigned.',
                    ]);
                }

                $previousStatus = $lockedReport->status;
                $previousOperatorId = $lockedReport->assigned_operator_id;

                $lockedReport->status = ReportStatus::ASSIGNED;
                $lockedReport->assigned_operator_id = $operator->id;
                $lockedReport->assigned_at = now();
                $lockedReport->version++;
                $lockedReport->save();

                ReportStatusHistory::create([
                    'report_id' => $lockedReport->id,
                    'actor_id' => $actor->id,
                    'from_status' => $previousStatus,
                    'to_status' => ReportStatus::ASSIGNED,
                    'from_operator_id' => $previousOperatorId,
                    'to_operator_id' => $operator->id,
                    'reason' => $reason,
                ]);

                return $lockedReport;
            }, 3);
        }

        public function transition(
            Report $report,
            ReportStatus $newStatus,
            User $actor,
            ?string $reason = null
        ): Report {
            return DB::transaction(function () use ($report, $newStatus, $actor, $reason) {

                $lockedReport = Report::query()
                    ->whereKey($report->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::forUser($actor)->authorize('transition', $lockedReport);

                $allowedTransitions = [
                    ReportStatus::ASSIGNED->value => ReportStatus::IN_PROGRESS,
                    ReportStatus::IN_PROGRESS->value => ReportStatus::RESOLVED,
                ];

                $expectedStatus = $allowedTransitions[$lockedReport->status->value] ?? null;

                if ($expectedStatus !== $newStatus) {
                    throw ValidationException::withMessages([
                        'status' => 'Invalid report status transition.',
                    ]);
                }

                $previousStatus = $lockedReport->status;

                $lockedReport->status = $newStatus;

                if ($newStatus === ReportStatus::IN_PROGRESS) {
                    $lockedReport->started_at = now();
                }

                if ($newStatus === ReportStatus::RESOLVED) {
                    $lockedReport->resolved_at = now();
                }

                $lockedReport->version++;
                $lockedReport->save();

                ReportStatusHistory::create([
                    'report_id' => $lockedReport->id,
                    'actor_id' => $actor->id,
                    'from_status' => $previousStatus,
                    'to_status' => $newStatus,
                    'from_operator_id' => $lockedReport->assigned_operator_id,
                    'to_operator_id' => $lockedReport->assigned_operator_id,
                    'reason' => $reason,
                ]);

                return $lockedReport;
            }, 3);
        }
    }
