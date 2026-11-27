<?php

namespace App\Support\Referensi;

final class OpsiPpdb
{
    public const KEBUTUHAN_KHUSUS = [
        '00' => 'Tidak',
        '01' => 'Tunanetra',
        '02' => 'Tunarungu',
        '03' => 'Tunawicara',
        '04' => 'Tunagrahita',
        '05' => 'Tunadaksa',
        '06' => 'Tunalaras',
        '07' => 'Tunaganda',
        '08' => 'Autisme',
    ];

    public static function pilihan(array $agama = []): array
    {
        return [
            'hobi' => ['A' => 'Olahraga', 'B' => 'Kesenian', 'C' => 'Membaca', 'D' => 'Menulis', 'E' => 'Traveling', 'F' => 'Lainnya'],
            'cita_cita' => ['A' => 'PNS', 'B' => 'TNI/POLRI', 'C' => 'Wali Kelas/Dosen', 'D' => 'Dokter', 'E' => 'Politikus', 'F' => 'Wiraswasta', 'G' => 'Seniman', 'H' => 'Lainnya'],
            'jenis_kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'],
            'status_keluarga' => ['Anak kandung', 'Anak yatim', 'Piatu', 'Yatim Piatu'],
            'penerima_kps' => ['YA' => 'Ya', 'TIDAK' => 'Tidak'],
            'layak_pip' => ['YA' => 'Ya', 'TIDAK' => 'Tidak'],
            'agama' => $agama,
            'kebutuhan' => self::KEBUTUHAN_KHUSUS,
            'berkebutuhan_khusus' => self::KEBUTUHAN_KHUSUS,
            'tempat_tinggal' => ['1' => 'Bersama orang tua', '2' => 'Wali', '3' => 'Kos', '4' => 'Asrama', '5' => 'Panti asuhan', '6' => 'Lainnya'],
            'moda_transportasi' => ['01' => 'Jalan kaki', '02' => 'Kendaraan pribadi', '03' => 'Kendaraan umum', '04' => 'Jemputan sekolah', '05' => 'Kereta api', '06' => 'Ojek', '07' => 'Beca', '08' => 'Perahu penyeberangan', '09' => 'Lainnya'],
            'alasan_pip' => ['1' => 'Pemegang PKH/KPS/KIP', '2' => 'Penerima BSM 2014', '3' => 'Yatim Piatu/Panti Asuhan', '4' => 'Dampak bencana alam', '5' => 'Pernah drop out', '6' => 'Siswa miskin/rentan', '7' => 'Daerah konflik', '8' => 'Keluarga terpidana/berada di LAPAS', '9' => 'Kelainan fisik'],
            'pendidikan' => ['01' => 'Tidak sekolah', '02' => 'Putus SD', '03' => 'SD Sederajat', '04' => 'SMP Sederajat', '05' => 'SMA Sederajat', '06' => 'D1', '07' => 'D2', '08' => 'D3', '09' => 'D4/S1', '10' => 'S2', '11' => 'S3'],
            'pekerjaan' => ['01' => 'Tidak bekerja', '02' => 'Nelayan', '03' => 'Petani', '04' => 'Peternak', '05' => 'PNS/TNI/Polri', '06' => 'Karyawan swasta', '07' => 'Pedagang kecil', '08' => 'Pedagang besar', '09' => 'Wiraswasta', '10' => 'Wirausaha', '11' => 'Buruh', '12' => 'Pensiunan', '13' => 'Lain-lain'],
            'penghasilan' => ['1' => 'Kurang dari 500.000', '2' => '500.000 - 999.999', '3' => '1 juta - 1.999.999', '4' => '2 juta - 4.999.999', '5' => '5 juta - 20 juta', '6' => 'Lebih dari 20 juta'],
            'prestasi_jenis' => ['1' => 'Sains', '2' => 'Seni', '3' => 'Olahraga', '4' => 'Lain-lain'],
            'prestasi_tingkat' => ['1' => 'Sekolah', '2' => 'Kecamatan', '3' => 'Kabupaten', '4' => 'Provinsi', '5' => 'Nasional', '6' => 'Internasional'],
            'beasiswa_jenis' => ['01' => 'Anak berprestasi', '02' => 'Anak miskin', '03' => 'Pendidikan', '04' => 'Unggulan', '05' => 'Lain-lain'],
            'gol_darah' => ['O', 'A', 'B', 'AB'],
        ];
    }

    public static function label(string $field, mixed $value, array $agama = []): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $options = self::pilihan($agama);
        $label = $options[$field][(string) $value] ?? null;

        if ($field === 'status_keluarga' && in_array($value, $options[$field], true)) {
            return (string) $value;
        }

        return $label ?? (is_scalar($value) ? (string) $value : '-');
    }
}
