<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Controllers\Controller;
    use App\Http\Resources\IncidentResource;
    use App\Models\Incident;
    use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    }
