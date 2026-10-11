<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * Report totals for the operator dashboard.
     *
     * Every status is always present in `by_status`, with 0 when no report has it.
     *
     * @response array{data: array{total: int, by_status: array{OPEN: int, ASSIGNED: int, IN_PROGRESS: int, RESOLVED: int}}}
     */
    public function summary(): JsonResponse
    {
        Gate::authorize('viewAny', Report::class);

        // One grouped aggregate instead of a COUNT query per status.
        $counts = Report::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = [];

        foreach (ReportStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return response()->json([
            'data' => [
                'total' => array_sum($byStatus),
                'by_status' => $byStatus,
            ],
        ]);
    }
}
