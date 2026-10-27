<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPpdbRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status_pendaftaran' => ['required', 'in:diverifikasi,diterima,ditolak,perlu_perbaikan'],
            'catatan_verifikasi' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
