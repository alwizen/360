<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PretripApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'truck' => [
                'id' => $this->truck->id,
                'truck_id' => $this->truck->truck_id,
                'capacity' => $this->truck->capacity,
                'merk' => $this->truck->merk,
            ],
            'driver' => $this->when($this->driver, [
                'id' => $this->driver?->id,
                'name' => $this->driver?->name,
                'email' => $this->driver?->email,
            ]),
            'trip_date' => $this->trip_date->format('Y-m-d'),
            'start_time' => $this->start_time?->format('H:i:s'),
            'end_time' => $this->end_time?->format('H:i:s'),
            'status' => $this->status,
            'notes' => $this->notes,
            'completion_percentage' => $this->completion_percentage,
            'remaining_points' => $this->remaining_points,
            'is_complete' => $this->isComplete(),
            'taps' => PretripTapApiResource::collection($this->whenLoaded('taps')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
