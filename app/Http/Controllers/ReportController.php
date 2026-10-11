<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListReportsRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index(ListReportsRequest $request)
    {
        // Authorization (ReportPolicy::viewAny) is enforced by ListReportsRequest.
        $filters = $request->validated();
        $assignedOperatorId = $request->assignedOperatorId();
        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        $reports = Report::query()
            ->with(['category', 'assignedOperator'])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when($assignedOperatorId !== null, fn ($query) => $query->where('assigned_operator_id', $assignedOperatorId))
            ->when(isset($filters['created_from']), fn ($query) => $query->where(
                'created_at',
                '>=',
                Carbon::createFromFormat('Y-m-d', $filters['created_from'])->startOfDay()
            ))
            // `created_to` is inclusive of the whole day; compare against the next midnight
            // so the created_at column can still use a plain range scan.
            ->when(isset($filters['created_to']), fn ($query) => $query->where(
                'created_at',
                '<',
                Carbon::createFromFormat('Y-m-d', $filters['created_to'])->addDay()->startOfDay()
            ))
            // $sort and $direction are whitelisted by ListReportsRequest.
            ->orderByRaw("{$sort} {$direction} NULLS LAST")
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ReportResource::collection($reports);
    }

    public function show($id)
    {
        $report = Report::findOrFail($id);
        Gate::authorize('view', $report);
        return ReportResource::make($report);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'reporter_email' => 'required|email',
        ]);

        $location = sprintf(
            'SRID=4326;POINT(%s %s)',
            $validated['longitude'],
            $validated['latitude']
        );

        $trackingNumber = 'CP-' . Str::upper(Str::random(12));
        $guestToken = bin2hex(random_bytes(32));

        $report = Report::create([
            'category_id' => $validated['category_id'],
            'description' => $validated['description'],
            'location' => $location,
            'tracking_number' => $trackingNumber,
            'reporter_email' => $validated['reporter_email'],
            'reporter_id' => $request->user('sanctum')?->id,
            'guest_token_hash' => hash('sha256', $guestToken),
        ]);
        return (ReportResource::make($report))
            ->additional([
                'guest_access_token' => $guestToken,
            ]);
    }

    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);
        Gate::authorize('update', $report);

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'description' => 'sometimes|string',
            'latitude' => 'required_with:longitude|numeric|between:-90,90',
            'longitude' => 'required_with:latitude|numeric|between:-180,180',
            'reporter_email' => 'sometimes|email',
        ]);

        if (isset($validated['latitude'], $validated['longitude'])) {
            $validated['location'] = sprintf(
                'SRID=4326;POINT(%s %s)',
                $validated['longitude'],
                $validated['latitude']
            );
        }

            unset($validated['latitude'], $validated['longitude']);

            $report->update($validated);

            return ReportResource::make($report);
    }

    public function guestShow(Request $request, string $trackingNumber)
    {
        $validated = $request->validate([
            'access_token' => 'required|string',
        ]);

        $report = Report::where('tracking_number', $trackingNumber)
            ->first();

        $providedHash = hash('sha256', $validated['access_token']);

        if (
            !$report ||
            !$report->guest_token_hash ||
            !hash_equals($report->guest_token_hash, $providedHash)
        ) {
            abort(403, 'Invalid tracking number or access token.');
        }

        return ReportResource::make($report);
    }
}
