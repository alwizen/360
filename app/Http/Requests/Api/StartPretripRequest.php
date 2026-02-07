<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StartPretripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Set sesuai logic authorization kamu
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
            'notes' => 'nullable|string|max:1000',
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
        ];
    }
}
