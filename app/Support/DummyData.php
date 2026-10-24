<?php

namespace App\Support;

/**
 * DATA SEMENTARA untuk tahap frontend.
 * Nanti DBA/backend mengganti pemanggilan DummyData::xxx() di controller
 * dengan query Eloquent ke tabel sesuai ERD. Komentar "tabel:" menunjukkan asalnya.
 */
class DummyData
{
    private static function t(string $judul, array $kolom, array $baris): array
    {
        return compact('judul', 'kolom', 'baris');
    }

    public static function roles(): array
    {
        return [
            'siswa'  => ['label' => 'Siswa',             'nama' => 'Contoh Siswa'],
            'guru'   => ['label' => 'Guru / Wali Kelas', 'nama' => 'Bu Contoh Guru'],
            'tu'     => ['label' => 'Tata Usaha',        'nama' => 'Pak Contoh TU'],
            'kepsek' => ['label' => 'Kepala Sekolah',    'nama' => 'Bapak Kepala Sekolah'],
        ];
    }

    // tabel: siswa + alamat_siswa + orang_tua_wali
    public static function siswa(): array
    {
        return [
            'nama_lengkap' => 'Contoh Siswa', 'nisn' => '0012345678', 'nik' => '1271010101100001',
            'no_kk' => '1271010101100002', 'jenis_kelamin' => 'Perempuan', 'tempat_lahir' => 'Medan',
            'tanggal_lahir' => '14 Mei 2010', 'agama' => 'Kristen Protestan', 'no_hp' => '0812-3456-7890',
            'sekolah_asal' => 'SMP Contoh Medan', 'kelas' => 'X IPA 1', 'status' => 'Aktif',
            'alamat' => 'Jl. Contoh No. 12, RT 02 / RW 03, Kel. Contoh, Kec. Medan Kota, Medan, Sumatera Utara',
            'ayah' => 'Bapak Contoh', 'ibu' => 'Ibu Contoh',
        ];
    }

    // tabel: pengajuan_perubahan + detail_pengajuan
    public static function pengajuanSiswa(): array
    {
        return [
            ['id' => 'PP-014', 'field' => 'Alamat', 'lama' => 'Jl. Lama No. 3', 'baru' => 'Jl. Contoh No. 12', 'tgl' => '27 Sep 2026', 'status' => 'menunggu'],
            ['id' => 'PP-009', 'field' => 'No. HP', 'lama' => '0811-111-111', 'baru' => '0812-3456-7890', 'tgl' => '02 Agu 2026', 'status' => 'disetujui'],
        ];
    }

    // view: vw_antrean_verifikasi
    public static function antrean(): array
    {
        return [
            ['id' => 1, 'nama' => 'Anggi Sitorus', 'nisn' => '0023456789', 'jenis' => 'PPDB baru', 'tgl' => '20 Okt 2026', 'status' => 'menunggu'],
            ['id' => 2, 'nama' => 'Bima Nainggolan', 'nisn' => '0034567890', 'jenis' => 'PPDB baru', 'tgl' => '20 Okt 2026', 'status' => 'menunggu'],
            ['id' => 3, 'nama' => 'Citra Simanjuntak', 'nisn' => '0045678901', 'jenis' => 'PPDB baru', 'tgl' => '19 Okt 2026', 'status' => 'dikembalikan'],
            ['id' => 4, 'nama' => 'Dewi Lubis', 'nisn' => '0056789012', 'jenis' => 'PPDB baru', 'tgl' => '18 Okt 2026', 'status' => 'disahkan'],
        ];
    }

    // tabel: ref_checklist_item (per jenis_dokumen)
    public static function checklist(): array
    {
        return [
            'Kartu Keluarga' => ['Foto terbaca jelas', 'No. KK sesuai formulir', 'Nama siswa tercantum'],
            'Akta Kelahiran' => ['Foto terbaca jelas', 'Nama & tanggal lahir sesuai formulir'],
            'Ijazah / SKL'   => ['Foto terbaca jelas', 'Nama sekolah asal sesuai formulir'],
            'Pas Foto'       => ['Latar & ukuran sesuai ketentuan'],
        ];
    }

    // tabel: ref_alasan_pengembalian
    public static function alasanPengembalian(): array
    {
        return ['Berkas buram / tidak terbaca', 'Data tidak sesuai dokumen', 'Berkas kurang lengkap', 'Lainnya'];
    }

    // view: vw_kelengkapan_data_kelas
    public static function kelengkapanKelas(): array
    {
        return [
            ['kelas' => 'X IPA 1', 'total' => 36, 'konfirmasi' => 30, 'ubah' => 3, 'belum' => 3],
            ['kelas' => 'X IPS 1', 'total' => 34, 'konfirmasi' => 22, 'ubah' => 2, 'belum' => 10],
            ['kelas' => 'XI IPA 1', 'total' => 35, 'konfirmasi' => 33, 'ubah' => 1, 'belum' => 1],
        ];
    }

    // view: kelengkapan data per siswa (DFD 4.3, dilihat wali kelas)
    public static function kelengkapanSiswa(): array
    {
        return [
            ['nama' => 'Contoh Siswa', 'nisn' => '0012345678', 'status' => 'konfirmasi', 'tgl' => '22 Okt 2026'],
            ['nama' => 'Dewi Lubis', 'nisn' => '0056789012', 'status' => 'ubah', 'tgl' => '23 Okt 2026'],
            ['nama' => 'Eka Manurung', 'nisn' => '0067890123', 'status' => 'konfirmasi', 'tgl' => '21 Okt 2026'],
            ['nama' => 'Gita Silalahi', 'nisn' => '0089012345', 'status' => 'belum', 'tgl' => '-'],
            ['nama' => 'Hendra Panjaitan', 'nisn' => '0090123456', 'status' => 'belum', 'tgl' => '-'],
        ];
    }

    // tabel: pengguna + role
    public static function pengguna(): array
    {
        return [
            ['nama' => 'Bapak Kepala Sekolah', 'username' => 'kepsek01', 'role' => 'Kepala Sekolah', 'aktif' => true, 'login' => '24 Okt 2026 07:30'],
            ['nama' => 'Pak Contoh TU', 'username' => 'tu01', 'role' => 'Tata Usaha', 'aktif' => true, 'login' => '23 Okt 2026 08:12'],
            ['nama' => 'Bu Contoh Guru', 'username' => 'guru01', 'role' => 'Guru / Wali Kelas', 'aktif' => true, 'login' => '22 Okt 2026 14:40'],
            ['nama' => 'Contoh Siswa', 'username' => '0012345678', 'role' => 'Siswa', 'aktif' => true, 'login' => '21 Okt 2026 19:03'],
            ['nama' => 'Mantan Staf', 'username' => 'tu02', 'role' => 'Tata Usaha', 'aktif' => false, 'login' => '03 Mar 2026 10:00'],
        ];
    }

    // tabel: pendaftaran_ppdb + verifikasi_pendaftaran
    public static function statusPendaftaran(): array
    {
        return [
            ['judul' => 'Formulir terkirim', 'ket' => '10 Okt 2026', 'state' => 'selesai'],
            ['judul' => 'Diperiksa Tata Usaha', 'ket' => '12 Okt 2026', 'state' => 'selesai'],
            ['judul' => 'Berkas perlu diperbaiki', 'ket' => 'Kartu Keluarga buram / tidak terbaca', 'state' => 'perhatian'],
            ['judul' => 'Pengesahan', 'ket' => 'Menunggu', 'state' => 'belum'],
            ['judul' => 'Akun siswa dibuat', 'ket' => 'Menunggu', 'state' => 'belum'],
        ];
    }

    // tabel: siswa (data induk)
    public static function daftarSiswa(): array
    {
        return [
            ['id' => 1, 'nama' => 'Contoh Siswa', 'nisn' => '0012345678', 'kelas' => 'X IPA 1', 'status' => 'Aktif'],
            ['id' => 2, 'nama' => 'Dewi Lubis', 'nisn' => '0056789012', 'kelas' => 'X IPS 1', 'status' => 'Aktif'],
            ['id' => 3, 'nama' => 'Eka Manurung', 'nisn' => '0067890123', 'kelas' => 'XI IPA 1', 'status' => 'Aktif'],
            ['id' => 4, 'nama' => 'Fajar Harahap', 'nisn' => '0078901234', 'kelas' => '-', 'status' => 'Mutasi keluar'],
        ];
    }

    // tabel: dokumen_siswa + jenis_dokumen
    public static function dokumenSiswa(): array
    {
        return [
            ['jenis' => 'Kartu Keluarga', 'nomor' => '1271010101100002', 'format' => 'PDF', 'ukuran' => '420 KB', 'fisik' => true, 'tgl' => '10 Okt 2026'],
            ['jenis' => 'Akta Kelahiran', 'nomor' => '12345/2010', 'format' => 'JPG', 'ukuran' => '610 KB', 'fisik' => true, 'tgl' => '10 Okt 2026'],
            ['jenis' => 'Ijazah / SKL', 'nomor' => '-', 'format' => 'PDF', 'ukuran' => '980 KB', 'fisik' => false, 'tgl' => '10 Okt 2026'],
            ['jenis' => 'Pas Foto 3x4', 'nomor' => '-', 'format' => 'JPG', 'ukuran' => '150 KB', 'fisik' => false, 'tgl' => '10 Okt 2026'],
        ];
    }

    // tabel: periode_update
    public static function periodeUpdate(): array
    {
        return [
            ['tahun' => '2026/2027', 'buka' => '15 Okt 2026', 'tutup' => '31 Okt 2026', 'status' => 'Dibuka'],
            ['tahun' => '2025/2026', 'buka' => '01 Agu 2025', 'tutup' => '15 Agu 2025', 'status' => 'Ditutup'],
        ];
    }

    // tabel: log_login
    public static function logLogin(): array
    {
        return [
            ['waktu' => '24 Okt 2026 08:12', 'user' => 'tu01', 'ok' => true, 'ip' => '192.168.1.12', 'perangkat' => 'Chrome · Windows'],
            ['waktu' => '24 Okt 2026 07:58', 'user' => 'tu01', 'ok' => false, 'ip' => '192.168.1.12', 'perangkat' => 'Chrome · Windows'],
            ['waktu' => '23 Okt 2026 19:03', 'user' => '0012345678', 'ok' => true, 'ip' => '114.122.10.4', 'perangkat' => 'Chrome · Android'],
            ['waktu' => '23 Okt 2026 02:41', 'user' => 'kepsek01', 'ok' => false, 'ip' => '103.55.20.9', 'perangkat' => 'Firefox · Linux'],
        ];
    }

    // tabel: log_audit
    public static function audit(): array
    {
        return [
            ['waktu' => '24 Okt 2026 09:30', 'user' => 'tu01', 'aksi' => 'UPDATE', 'tabel' => 'siswa', 'rec' => '#1', 'lama' => 'alamat: Jl. Lama No. 3', 'baru' => 'alamat: Jl. Contoh No. 12'],
            ['waktu' => '24 Okt 2026 09:12', 'user' => 'tu01', 'aksi' => 'UPDATE', 'tabel' => 'pengajuan_perubahan', 'rec' => '#PP-009', 'lama' => 'status: menunggu', 'baru' => 'status: disetujui'],
            ['waktu' => '23 Okt 2026 15:02', 'user' => 'kepsek01', 'aksi' => 'INSERT', 'tabel' => 'pengguna', 'rec' => '#12', 'lama' => '-', 'baru' => 'username: guru01'],
        ];
    }

    // tabel: role, hak_akses, role_hak_akses
    public static function hakAkses(): array
    {
        $roles = ['Siswa', 'Guru', 'TU', 'Kepsek'];
        $hak = [
            'Konfirmasi / ajukan perubahan data' => [1, 0, 0, 0],
            'Catatan wali kelas'                 => [0, 1, 0, 0],
            'Pantau kelengkapan data kelas'      => [0, 1, 1, 0],
            'Verifikasi berkas PPDB'             => [0, 0, 1, 0],
            'Setujui perubahan data'             => [0, 0, 1, 0],
            'Kelola data induk siswa'            => [0, 0, 1, 0],
            'Kelola data master & referensi'     => [0, 0, 1, 0],
            'Kelola kelas, wali, dan jadwal'     => [0, 0, 1, 0],
            'Buka/tutup periode update'          => [0, 0, 1, 0],
            'Lihat dashboard ringkasan'          => [0, 0, 0, 1],
            'Kelola pengguna & peran'            => [0, 0, 0, 1],
            'Pengaturan sistem'                  => [0, 0, 0, 1],
            'Lihat log login & jejak audit'      => [0, 0, 0, 1],
        ];

        return compact('roles', 'hak');
    }

    // B. tabel referensi & wilayah
    public static function referensi(): array
    {
        return [
            'jenjang'       => self::t('Jenjang', ['Nama jenjang'], [['SMP'], ['SMA']]),
            'tingkat'       => self::t('Tingkat', ['Tingkat', 'Jenjang'], [['X', 'SMA'], ['XI', 'SMA'], ['XII', 'SMA']]),
            'agama'         => self::t('Agama', ['Nama agama'], [['Kristen Protestan'], ['Katolik'], ['Islam'], ['Buddha'], ['Hindu'], ['Konghucu']]),
            'pekerjaan'     => self::t('Pekerjaan', ['Nama pekerjaan'], [['PNS / ASN'], ['TNI / Polri'], ['Karyawan swasta'], ['Wiraswasta'], ['Petani / nelayan'], ['Lainnya']]),
            'sekolah'       => self::t('Sekolah Asal', ['Nama sekolah', 'Jenjang'], [['SMP Contoh Medan', 'SMP'], ['SMP Negeri 1 Medan', 'SMP'], ['MTs Contoh Medan', 'MTs']]),
            'status-siswa'  => self::t('Status Siswa', ['Status'], [['Calon'], ['Aktif'], ['Lulus'], ['Mutasi keluar'], ['Nonaktif']]),
            'jenis-dokumen' => self::t('Jenis Dokumen', ['Nama dokumen'], [['Kartu Keluarga'], ['Akta Kelahiran'], ['Ijazah / SKL'], ['Pas Foto'], ['Surat keterangan pindah']]),
            'wilayah'       => self::t('Wilayah', ['Provinsi', 'Kota/Kabupaten', 'Kecamatan', 'Kelurahan'], [['Sumatera Utara', 'Medan', 'Medan Kota', 'Contoh 1'], ['Sumatera Utara', 'Medan', 'Medan Baru', 'Contoh 2']]),
        ];
    }

    // tabel: ref_field_data
    public static function aturanField(): array
    {
        return [
            ['Nama lengkap', 'siswa.nama_lengkap', true, true, 'Akta Kelahiran'],
            ['NISN', 'siswa.nisn', false, false, '-'],
            ['NIK', 'siswa.nik', true, true, 'Kartu Keluarga'],
            ['No. KK', 'siswa.no_kk', true, true, 'Kartu Keluarga'],
            ['Tempat & tanggal lahir', 'siswa.tempat_lahir', true, true, 'Akta Kelahiran'],
            ['No. HP', 'siswa.no_hp', true, false, '-'],
            ['Alamat', 'alamat_siswa.jalan', true, false, '-'],
            ['Nama orang tua', 'orang_tua_wali.nama', true, true, 'Kartu Keluarga'],
        ];
    }

    // A. tabel: guru & staf
    public static function guruStaf(): array
    {
        return [
            'guru' => self::t('Data Guru', ['Nama', 'NIP', 'No. HP', 'Username'], [['Bu Contoh Guru', '198001012005012001', '0812-1111-2222', 'guru01'], ['Pak Harahap', '197805052003121002', '0813-3333-4444', 'guru02']]),
            'staf' => self::t('Data Staf', ['Nama', 'No. HP', 'Username'], [['Pak Contoh TU', '0811-5555-6666', 'tu01'], ['Bu Sitompul', '0811-7777-8888', 'tu02']]),
        ];
    }

    // A. tabel: pengaturan_sistem
    public static function pengaturan(): array
    {
        return [
            ['Nama sekolah', 'nama_sekolah', 'SMA Methodist 2 Medan', 'Tampil di header dan laporan'],
            ['Batas login gagal', 'max_login_gagal', '5', 'Akun dikunci sementara setelah gagal sebanyak ini'],
            ['Lama penguncian (menit)', 'durasi_kunci', '15', 'Lama akun terkunci setelah gagal beruntun'],
            ['Ukuran maksimal berkas (MB)', 'ukuran_berkas_mb', '2', 'Batas unggah per berkas'],
            ['Format berkas diizinkan', 'format_berkas', 'pdf, jpg, png', 'Pisahkan dengan koma'],
        ];
    }

    // C. konfigurasi akademik
    public static function akademik(): array
    {
        return [
            'tahun-ajaran' => self::t('Tahun Ajaran', ['Tahun ajaran', 'Mulai', 'Selesai', 'Status'], [['2026/2027', '13 Jul 2026', '30 Jun 2027', 'Aktif'], ['2025/2026', '14 Jul 2025', '30 Jun 2026', 'Selesai']]),
            'semester'     => self::t('Semester', ['Semester', 'Tahun ajaran', 'Mulai', 'Selesai', 'Status'], [['Ganjil', '2026/2027', '13 Jul 2026', '19 Des 2026', 'Aktif'], ['Genap', '2026/2027', '04 Jan 2027', '30 Jun 2027', 'Belum mulai']]),
            'kelas'        => self::t('Kelas', ['Kelas', 'Tingkat', 'Tahun ajaran', 'Wali kelas'], [['X IPA 1', 'X', '2026/2027', 'Bu Contoh Guru'], ['X IPS 1', 'X', '2026/2027', 'Pak Harahap'], ['XI IPA 1', 'XI', '2026/2027', '-']]),
            'mapel'        => self::t('Mata Pelajaran', ['Kode', 'Nama mata pelajaran', 'Kategori'], [['MTK', 'Matematika', 'Wajib'], ['BIN', 'Bahasa Indonesia', 'Wajib'], ['BIG', 'Bahasa Inggris', 'Wajib'], ['PAK', 'Pendidikan Agama Kristen', 'Wajib'], ['FIS', 'Fisika', 'Peminatan']]),
            'komponen'     => self::t('Komponen Penilaian', ['Komponen', 'Bobot (%)'], [['Tugas', '20'], ['Ulangan harian', '20'], ['UTS', '25'], ['UAS', '35']]),
            'predikat'     => self::t('Skala Predikat', ['Huruf', 'Nilai min', 'Nilai maks', 'Deskripsi'], [['A', '90', '100', 'Sangat baik'], ['B', '80', '89', 'Baik'], ['C', '70', '79', 'Cukup'], ['D', '0', '69', 'Perlu bimbingan']]),
        ];
    }

    public static function daftarMapel(): array
    {
        return ['Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'Fisika', 'Kimia', 'Biologi', 'PKn', 'Pendidikan Agama Kristen', 'Seni Budaya', 'PJOK'];
    }

    public static function daftarGuru(): array
    {
        return ['Bu Contoh Guru', 'Pak Harahap', 'Bu Sitompul', 'Pak Nainggolan'];
    }

    // C. tabel: jadwal_pelajaran (untuk guru)
    public static function jadwalGuru(): array
    {
        return [
            ['hari' => 'Senin', 'jam' => '07.00–08.30', 'kelas' => 'X IPA 1', 'mapel' => 'Matematika'],
            ['hari' => 'Senin', 'jam' => '09.30–11.00', 'kelas' => 'X IPS 1', 'mapel' => 'Matematika'],
            ['hari' => 'Selasa', 'jam' => '07.45–09.15', 'kelas' => 'XI IPA 1', 'mapel' => 'Matematika'],
            ['hari' => 'Rabu', 'jam' => '07.00–08.30', 'kelas' => 'X IPA 1', 'mapel' => 'Matematika'],
            ['hari' => 'Kamis', 'jam' => '10.15–11.45', 'kelas' => 'X IPS 1', 'mapel' => 'Matematika'],
        ];
    }

    // D. tabel: kelas + penugasan_wali_kelas
    public static function kelas(): array
    {
        return [
            ['id' => 1, 'nama' => 'X IPA 1', 'tingkat' => 'X', 'wali' => 'Bu Contoh Guru', 'jumlah' => 36],
            ['id' => 2, 'nama' => 'X IPS 1', 'tingkat' => 'X', 'wali' => 'Pak Harahap', 'jumlah' => 34],
            ['id' => 3, 'nama' => 'XI IPA 1', 'tingkat' => 'XI', 'wali' => '(belum ditetapkan)', 'jumlah' => 35],
        ];
    }

    // D. tabel: keanggotaan_kelas
    public static function siswaKelas(): array
    {
        return [
            ['nama' => 'Contoh Siswa', 'nisn' => '0012345678'],
            ['nama' => 'Dewi Lubis', 'nisn' => '0056789012'],
            ['nama' => 'Eka Manurung', 'nisn' => '0067890123'],
            ['nama' => 'Gita Silalahi', 'nisn' => '0089012345'],
            ['nama' => 'Hendra Panjaitan', 'nisn' => '0090123456'],
        ];
    }

    public static function belumBerkelas(): array
    {
        return [
            ['nama' => 'Anggi Sitorus', 'nisn' => '0023456789'],
            ['nama' => 'Bima Nainggolan', 'nisn' => '0034567890'],
            ['nama' => 'Citra Simanjuntak', 'nisn' => '0045678901'],
        ];
    }

    // D. tabel: catatan_wali_kelas
    public static function catatan(): array
    {
        return [
            ['siswa' => 'Contoh Siswa', 'isi' => 'Aktif di kelas dan rajin mengumpulkan tugas. Perlu meningkatkan ketepatan waktu.', 'tgl' => '20 Okt 2026'],
            ['siswa' => 'Dewi Lubis', 'isi' => 'Nilai matematika menurun, sudah dibicarakan dengan orang tua.', 'tgl' => '12 Okt 2026'],
        ];
    }

    // B. wilayah berjenjang: provinsi > kota > kecamatan > kelurahan
    public static function wilayahPohon(): array
    {
        return [
            'Sumatera Utara' => [
                'Medan' => [
                    'Medan Kota'    => ['Pasar Baru', 'Teladan Barat', 'Sudirejo I'],
                    'Medan Baru'    => ['Padang Bulan', 'Babura', 'Merdeka'],
                    'Medan Petisah' => ['Petisah Tengah', 'Sei Putih Barat'],
                ],
                'Deli Serdang' => [
                    'Lubuk Pakam'     => ['Lubuk Pakam I-II', 'Pasar Melintang'],
                    'Percut Sei Tuan' => ['Tembung', 'Bandar Khalipah'],
                ],
            ],
            'DKI Jakarta' => [
                'Jakarta Pusat' => ['Gambir' => ['Gambir', 'Petojo Selatan'], 'Menteng' => ['Menteng', 'Cikini']],
            ],
        ];
    }

    // E. data pendaftar yang sudah ada (untuk peringatan pendaftaran ganda)
    public static function pendaftarTerdaftar(): array
    {
        return [
            'nisn' => ['0012345678' => 'Contoh Siswa', '0023456789' => 'Anggi Sitorus'],
            'nik'  => ['1271010101100001' => 'Contoh Siswa', '1271010101100009' => 'Anggi Sitorus'],
        ];
    }
}