<?php

namespace Database\Seeders;

use App\Models\KomponenPenilaian;
use App\Models\SkalaPredikat;
use Illuminate\Database\Seeder;

class MasterPenilaianSeeder extends Seeder
{
    public function run(): void
    {
        if (KomponenPenilaian::query()->doesntExist()) {
            foreach ([
                ['nama_komponen' => 'Tugas', 'bobot_persen' => 20],
                ['nama_komponen' => 'UH', 'bobot_persen' => 30],
                ['nama_komponen' => 'UTS', 'bobot_persen' => 25],
                ['nama_komponen' => 'UAS', 'bobot_persen' => 25],
            ] as $komponen) {
                KomponenPenilaian::create($komponen);
            }
        }

        if (SkalaPredikat::query()->doesntExist()) {
            foreach ([
                ['huruf' => 'D', 'nilai_min' => 0, 'nilai_maks' => 59.99, 'deskripsi' => 'Perlu bimbingan'],
                ['huruf' => 'C', 'nilai_min' => 60, 'nilai_maks' => 74.99, 'deskripsi' => 'Cukup'],
                ['huruf' => 'B', 'nilai_min' => 75, 'nilai_maks' => 89.99, 'deskripsi' => 'Baik'],
                ['huruf' => 'A', 'nilai_min' => 90, 'nilai_maks' => 100, 'deskripsi' => 'Sangat baik'],
            ] as $predikat) {
                SkalaPredikat::create($predikat);
            }
        }
    }
}
