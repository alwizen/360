<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TapPointRequest extends FormRequest
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
            'rfid_point_id' => 'required|exists:rfid_points,id',
            'tapped_at' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rfid_point_id.required' => 'RFID Point ID wajib diisi',
            'rfid_point_id.exists' => 'RFID Point tidak ditemukan',
            'tapped_at.date' => 'Format waktu tidak valid',
        ];
    }
}
