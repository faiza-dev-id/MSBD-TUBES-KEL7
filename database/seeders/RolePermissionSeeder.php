<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $hak = [
            'dashboard.view' => 'Melihat dashboard',
            'siswa.view' => 'Melihat data siswa',
            'siswa.update.request' => 'Mengajukan perubahan data siswa',
            'siswa.update.approve' => 'Menyetujui perubahan data siswa',
            'ppdb.verify' => 'Memverifikasi pendaftaran PPDB',
            'akademik.manage' => 'Mengelola data akademik',
            'user.manage' => 'Mengelola pengguna',
            'audit.view' => 'Melihat audit trail',
            'loginlog.view' => 'Melihat log login',
        ];
        foreach ($hak as $kode => $nama) {
            DB::table('hak_akses')->updateOrInsert(['kode_hak_akses' => $kode], ['nama_hak_akses' => $nama]);
        }
        foreach (['siswa', 'guru', 'tu', 'kepsek'] as $role) {
            DB::table('role')->updateOrInsert(['nama_role' => $role], ['deskripsi' => $role]);
        }

        // DIPERBAIKI: seeder lama tidak mengisi role_hak_akses, sehingga middleware
        // permission: menolak semua orang. Pemetaan mengikuti DFD (aktor tiap proses).
        $peta = [
            'tu'     => ['dashboard.view', 'siswa.view', 'siswa.update.approve', 'ppdb.verify', 'akademik.manage'],
            'kepsek' => ['dashboard.view', 'siswa.view', 'user.manage', 'audit.view', 'loginlog.view'],
            'guru'   => ['dashboard.view', 'siswa.view'],
            'siswa'  => ['siswa.update.request'],
        ];
        foreach ($peta as $role => $kodeList) {
            $idRole = DB::table('role')->where('nama_role', $role)->value('id_role');
            foreach ($kodeList as $kode) {
                $idHak = DB::table('hak_akses')->where('kode_hak_akses', $kode)->value('id_hak_akses');
                DB::table('role_hak_akses')->updateOrInsert(['id_role' => $idRole, 'id_hak_akses' => $idHak], []);
            }
        }
    }
}
