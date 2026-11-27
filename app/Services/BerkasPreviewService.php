<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/** Menyajikan berkas privat sebagai pratinjau inline; unduh hanya lewat ?download=1. */
class BerkasPreviewService
{
    private const MIME = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function response(Request $request, string $path, string $namaFile, string $disk = 'private'): Response
    {
        $path = trim($path);
        $namaFile = $this->namaAman($namaFile !== '' ? $namaFile : basename($path));

        $absolut = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1;
        if ($path === '' || $absolut || str_contains($path, '..')) {
            return $this->tidakTersedia($namaFile);
        }

        $ekstensi = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::MIME[$ekstensi] ?? null;
        if (! $mime) {
            return $this->tidakTersedia($namaFile);
        }

        $lokasi = null;

        try {
            foreach (array_values(array_unique([$disk, 'private', 'public'])) as $candidate) {
                $storage = Storage::disk($candidate);
                if (! $storage->exists($path)) {
                    continue;
                }

                $lokasi = $storage->path($path);
                break;
            }
        } catch (\Throwable $e) {
            return $this->tidakTersedia($namaFile);
        }

        if (! $lokasi) {
            return $this->tidakTersedia($namaFile);
        }

        if (! is_file($lokasi) || ! is_readable($lokasi)) {
            return $this->tidakTersedia($namaFile);
        }

        if ($request->query('download') === '1') {
            return response()->download($lokasi, $namaFile, [
                'Content-Type' => $mime,
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $namaAscii = preg_replace('/[^\x20-\x7E]/', '_', $namaFile) ?: 'berkas';
        $namaAscii = str_replace(['"', '\\'], '', $namaAscii);

        return response()->file($lokasi, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$namaAscii.'"; filename*=UTF-8\'\''.rawurlencode($namaFile),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function tipe(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf' ? 'pdf' : 'gambar';
    }

    public function namaAman(?string $nama): string
    {
        $nama = trim((string) $nama);
        $nama = str_replace(["\r", "\n", "\0", '"', '\\'], '', $nama);

        return $nama !== '' ? $nama : 'berkas';
    }

    public function tidakTersedia(?string $namaFile = null): Response
    {
        return response()->view('berkas.belum-tersedia', [
            'nama' => $this->namaAman($namaFile),
        ], 404);
    }
}
