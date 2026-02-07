<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PretripTapApiResource extends JsonResource
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
            'rfid_point' => [
                'id' => $this->rfidPoint->id,
                'rfid_code' => $this->rfidPoint->rfid_code,
                'location' => $this->rfidPoint->location,
                'point_number' => $this->rfidPoint->point_number,
            ],
            'tapped_at' => $this->tapped_at->format('Y-m-d H:i:s'),
            'tap_sequence' => $this->tap_sequence,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
