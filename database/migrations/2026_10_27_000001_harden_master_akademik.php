<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambahan di level database (hanya PostgreSQL) agar aturan DFD 2.1 tetap
 * terjaga walau data diubah lewat DBeaver / SQL langsung.
 * Tabel sudah ada di dump SQL; migration ini hanya menambah constraint.
 * Jika data lama sudah tumpang tindih, migration akan gagal: rapikan datanya dulu.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        // Tahun ajaran tidak boleh tumpang tindih
        DB::statement("ALTER TABLE tahun_ajaran ADD CONSTRAINT ex_tahun_ajaran_tidak_tumpang_tindih
            EXCLUDE USING gist (daterange(tanggal_mulai, tanggal_selesai, '[]') WITH &&)");

        // Rentang predikat tidak boleh tumpang tindih
        DB::statement("ALTER TABLE skala_predikat ADD CONSTRAINT ex_skala_predikat_tidak_tumpang_tindih
            EXCLUDE USING gist (numrange(nilai_min, nilai_maks, '[]') WITH &&)");

        // Total bobot komponen <= 100 dijaga di service (butuh agregat lintas baris).
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;

        DB::statement('ALTER TABLE skala_predikat DROP CONSTRAINT IF EXISTS ex_skala_predikat_tidak_tumpang_tindih');
        DB::statement('ALTER TABLE tahun_ajaran DROP CONSTRAINT IF EXISTS ex_tahun_ajaran_tidak_tumpang_tindih');
    }
};
