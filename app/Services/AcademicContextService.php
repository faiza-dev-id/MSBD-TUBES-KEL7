<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicContextService
{
    public function forHeader(): array
    {
        $resolve = function (): array {
            $semester = null;
            $tahunAjaran = null;

            if (Schema::hasTable('semester') && Schema::hasColumn('semester', 'is_aktif')) {
                $query = DB::table('semester')->where('is_aktif', true);

                if (Schema::hasColumn('semester', 'tanggal_mulai')) {
                    $query->orderByDesc('tanggal_mulai');
                }

                if (Schema::hasColumn('semester', 'id_semester')) {
                    $query->orderByDesc('id_semester');
                }

                $kolom = [];
                if (Schema::hasColumn('semester', 'nama_semester')) {
                    $kolom[] = 'nama_semester';
                }

                if (Schema::hasColumn('semester', 'id_tahun_ajaran')) {
                    $kolom[] = 'id_tahun_ajaran';
                }

                $konteks = $kolom !== [] ? $query->first($kolom) : null;
                $semester = $konteks->nama_semester ?? null;

                if (Schema::hasColumn('semester', 'id_tahun_ajaran')
                    && ($konteks->id_tahun_ajaran ?? null)
                    && Schema::hasTable('tahun_ajaran')) {
                    $tahunAjaran = DB::table('tahun_ajaran')
                        ->where('id_tahun_ajaran', $konteks->id_tahun_ajaran)
                        ->value('nama_tahun_ajaran');
                }
            }

            if (! $tahunAjaran
                && Schema::hasTable('tahun_ajaran')
                && Schema::hasColumn('tahun_ajaran', 'is_aktif')
                && Schema::hasColumn('tahun_ajaran', 'nama_tahun_ajaran')) {
                $query = DB::table('tahun_ajaran')->where('is_aktif', true);

                if (Schema::hasColumn('tahun_ajaran', 'tanggal_mulai')) {
                    $query->orderByDesc('tanggal_mulai');
                }

                $tahunAjaran = $query->value('nama_tahun_ajaran');
            }

            return [
                'semester' => $semester,
                'tahun_ajaran' => $tahunAjaran,
            ];
        };

        return app()->environment('testing')
            ? $resolve()
            : Cache::remember('academic-header-context', now()->addMinutes(5), $resolve);
    }
}
