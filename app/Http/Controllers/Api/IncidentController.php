<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Resources\IncidentResource;
    use App\Models\Incident;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
    use Illuminate\Http\Request;
    use Illuminate\Validation\ValidationException;

    class IncidentController extends Controller
    {
        public function index(): AnonymousResourceCollection
        {
            $incidents = Incident::query()
                ->select('incidents.*')
                ->selectRaw('ST_Y(location::geometry) AS latitude')
                ->selectRaw('ST_X(location::geometry) AS longitude')
                ->with('category')
                ->withCount('reports')
                ->orderByDesc('created_at')
                ->paginate(15);

            return IncidentResource::collection($incidents);
        }

        public function show(string $id): IncidentResource
        {
            $incident = Incident::query()
                ->select('incidents.*')
                ->selectRaw('ST_Y(location::geometry) AS latitude')
                ->selectRaw('ST_X(location::geometry) AS longitude')
                ->with('category')
                ->withCount('reports')
                ->findOrFail($id);

            return new IncidentResource($incident);
        }


        public function nearby(Request $request): AnonymousResourceCollection
        {
            $validated = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'sometimes|numeric|min:1|max:10000',
            ]);

            $latitude = (float) $validated['latitude'];
            $longitude = (float) $validated['longitude'];
            $radius = (float) ($validated['radius'] ?? 1000);

            $point = sprintf(
                'SRID=4326;POINT(%s %s)',
                $longitude,
                $latitude
            );

            $incidents = Incident::query()
                ->select('incidents.*')
                ->selectRaw('ST_Y(location::geometry) AS latitude')
                ->selectRaw('ST_X(location::geometry) AS longitude')
                ->selectRaw(
                    'ST_Distance(location, ?::geography) AS distance_meters',
                    [$point]
                )
                ->whereRaw(
                    'ST_DWithin(location, ?::geography, ?)',
                    [$point, $radius]
                )
                ->with('category')
                ->withCount('reports')
                ->orderBy('distance_meters')
                ->orderBy('id')
                ->paginate(15);

            return IncidentResource::collection($incidents);
        }

        public function potentialMatches(Request $request)
        {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            $point = sprintf(
                'SRID=4326;POINT(%s %s)',
                (float) $validated['longitude'],
                (float) $validated['latitude']
            );

            $incidents = Incident::query()
                ->select('incidents.*')
                ->selectRaw('ST_Y(location::geometry) AS latitude')
                ->selectRaw('ST_X(location::geometry) AS longitude')
                ->selectRaw(
                    'ST_Distance(location, ?::geography) AS distance_meters',
                    [$point]
                )
                ->where('category_id', $validated['category_id'])
                ->where('status', 'OPEN')
                ->whereRaw(
                    'ST_DWithin(location, ?::geography, ?)',
                    [$point, 50]
                )
                ->with('category')
                ->withCount('reports')
                ->orderBy('distance_meters')
                ->orderBy('id')
                ->limit(10)
                ->get();

            return IncidentResource::collection($incidents);
        }

    }
