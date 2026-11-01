<?php

    namespace App\Http\Resources;

    use Illuminate\Http\Request;
    use Illuminate\Http\Resources\Json\JsonResource;

    class IncidentResource extends JsonResource
    {
        public function toArray(Request $request): array
        {
            return [
                'id' => $this->id,
                'category' => [
                    'id' => $this->category_id,
                    'name' => $this->whenLoaded(
                        'category',
                        fn () => $this->category->name
                    ),
                ],
                'status' => $this->status,
                'location' => [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                ],
                'reports_count' => $this->whenCounted('reports'),
                'created_at' => $this->created_at?->toISOString(),
            ];
        }
    }
