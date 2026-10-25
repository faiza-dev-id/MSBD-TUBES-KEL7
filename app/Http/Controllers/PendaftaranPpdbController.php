<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PendaftaranPpdbController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nisn' => 'nullable|string|size:10',
            'tanggal_lahir' => 'nullable|date',
            'sekolah_asal' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $nomor = 'PPDB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        DB::table('pendaftaran_ppdb')->insert([
            'nomor_pendaftaran' => $nomor,
            'nama_calon_siswa' => $data['nama_lengkap'],
            'nisn' => $data['nisn'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'sekolah_asal' => $data['sekolah_asal'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'no_telepon' => $data['no_hp'] ?? null,
            'status_pendaftaran' => 'menunggu',
        ]);

        return redirect()
            ->route('ppdb.status')
            ->with('success', 'Pendaftaran berhasil dikirim. Nomor pendaftaran: ' . $nomor);
    }
}
