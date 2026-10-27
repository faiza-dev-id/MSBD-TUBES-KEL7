<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanPerubahanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_periode_update' => ['required', 'integer', 'exists:periode_update,id_periode_update'],
            'alasan' => ['required', 'string', 'max:2000'],
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.nama_field' => ['required', 'string', 'in:nama_lengkap,jenis_kelamin,tempat_lahir,tanggal_lahir,email,no_telepon,nis,nisn,nik'],
            'detail.*.nilai_lama' => ['nullable'],
            'detail.*.nilai_baru' => ['required'],
            'detail.*.bukti_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
