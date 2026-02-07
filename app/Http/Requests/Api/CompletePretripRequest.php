<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CompletePretripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'truck_id' => 'required|exists:trucks,id',
            'driver_id' => 'nullable|exists:users,id',
            'trip_date' => 'nullable|date',
            'start_time' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'taps' => 'required|array|min:1',
            'taps.*.rfid_point_id' => 'required|exists:rfid_points,id',
            'taps.*.tapped_at' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'truck_id.required' => 'Truck ID wajib diisi',
            'truck_id.exists' => 'Truck tidak ditemukan',
            'driver_id.exists' => 'Driver tidak ditemukan',
            'trip_date.date' => 'Format tanggal tidak valid',
            'taps.required' => 'Data taps wajib diisi',
            'taps.array' => 'Data taps harus berupa array',
            'taps.min' => 'Minimal 1 tap diperlukan',
            'taps.*.rfid_point_id.required' => 'RFID Point ID wajib diisi pada setiap tap',
            'taps.*.rfid_point_id.exists' => 'RFID Point tidak ditemukan',
            'taps.*.tapped_at.date' => 'Format waktu tap tidak valid',
        ];
    }
}
