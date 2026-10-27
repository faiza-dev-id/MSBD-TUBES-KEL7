<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePpdbRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nomor_pendaftaran' => ['required', 'string', 'max:40', 'unique:pendaftaran_ppdb,nomor_pendaftaran'],
            'nama_calon_siswa' => ['required', 'string', 'max:150'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'tanggal_lahir' => ['nullable', 'date'],
            'sekolah_asal' => ['nullable', 'string', 'max:200'],
            'alamat' => ['nullable', 'string'],
            'no_telepon' => ['nullable', 'string', 'max:30'],
        ];
    }
}
