<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::all();
        return ReportResource::collection($reports);
    }

    public function show($id)
    {
        $report = Report::findOrFail($id);
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

        $report = Report::create([
            'category_id' => $validated['category_id'],
            'description' => $validated['description'],
            'location' => $location,
            'tracking_number' => $trackingNumber,
            'reporter_email' => $validated['reporter_email'],
        ]);
        return ReportResource::make($report);
    }

    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);

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

}
