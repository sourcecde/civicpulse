<?php

    namespace App\Http\Controllers\Api;

    use App\Enums\ReportStatus;
    use App\Http\Controllers\Controller;
    use App\Http\Resources\ReportResource;
    use App\Models\Report;
    use App\Models\User;
    use App\Services\ReportWorkflowService;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Gate;
    use Illuminate\Validation\Rule;

    class ReportWorkflowController extends Controller
    {
        public function __construct(
            private readonly ReportWorkflowService $workflow
        ) {}

        public function assign(Request $request, Report $report)
        {
            Gate::authorize('assign', $report);

            $data = $request->validate([
                'operator_id' => ['required', 'integer', 'exists:users,id'],
                'reason' => ['nullable', 'string', 'max:2000'],
            ]);

            $operator = User::findOrFail($data['operator_id']);

            $updated = $this->workflow->assign(
                $report,
                $operator,
                $request->user(),
                $data['reason'] ?? null
            );

            return new ReportResource($updated);
        }

        public function updateStatus(Request $request, Report $report)
        {
            Gate::authorize('transition', $report);

            $data = $request->validate([
                'status' => [
                    'required',
                    Rule::in([
                        ReportStatus::IN_PROGRESS->value,
                        ReportStatus::RESOLVED->value,
                    ]),
                ],
                'reason' => ['nullable', 'string', 'max:2000'],
            ]);

            $updated = $this->workflow->transition(
                $report,
                ReportStatus::from($data['status']),
                $request->user(),
                $data['reason'] ?? null
            );

            return new ReportResource($updated);
        }

        public function history(Report $report)
        {
            Gate::authorize('view', $report);

            return $report->statusHistories()
                ->with(['actor', 'fromOperator', 'toOperator'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->map(fn ($history) => [
                    'id' => $history->id,
                    'from_status' => $history->from_status?->value,
                    'to_status' => $history->to_status->value,
                    'from_operator_id' => $history->from_operator_id,
                    'to_operator_id' => $history->to_operator_id,
                    'actor_id' => $history->actor_id,
                    'reason' => $history->reason,
                    'created_at' => $history->created_at,
                ]);
        }
    }
