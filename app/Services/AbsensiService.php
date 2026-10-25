<?php
namespace App\Services;
use Illuminate\Support\Facades\DB; use RuntimeException;
/** Target module: dump currently has no sesi_absensi/detail_absensi/status_kehadiran tables. */
class AbsensiService { public function assertSchemaAvailable(): void { foreach(['sesi_absensi','detail_absensi','status_kehadiran'] as $t) if(!DB::getSchemaBuilder()->hasTable($t)) throw new RuntimeException("Tabel target {$t} belum ada; lihat docs/SCHEMA_AUDIT.md"); } public function rekap(int $semesterId) { $this->assertSchemaAvailable(); return DB::table('detail_absensi as da')->join('sesi_absensi as sa','sa.id_sesi','=','da.id_sesi')->join('jadwal_pelajaran as j','j.id_jadwal','=','sa.id_jadwal')->where('j.id_semester',$semesterId)->select('da.id_siswa','da.id_status_hadir',DB::raw('COUNT(*) AS jumlah'))->groupBy('da.id_siswa','da.id_status_hadir')->get(); } }
