<?php

use App\Services\ActiveSiakadDataPreparation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:cek-role', function () {
    if (! app()->environment('local')) {
        $this->error('Perintah db:cek-role hanya tersedia di lingkungan local.');

        return 1;
    }

    $defaultDriver = DB::connection()->getDriverName();
    if ($defaultDriver !== 'pgsql') {
        $this->error('Driver default database bukan PostgreSQL. Perintah db:cek-role memerlukan pgsql.');

        return 1;
    }

    $requiredConnections = ['pgsql', 'pgsql_owner', 'pgsql_siswa', 'pgsql_tu', 'pgsql_kepsek', 'pgsql_wali_kelas'];
    $missingOrInvalid = [];

    foreach ($requiredConnections as $connectionName) {
        $driver = config("database.connections.{$connectionName}.driver");

        if (! array_key_exists($connectionName, config('database.connections')) || $driver !== 'pgsql') {
            $missingOrInvalid[] = $connectionName;
        }
    }

    if ($missingOrInvalid !== []) {
        $this->error('Koneksi PostgreSQL belum terdefinisi dengan driver pgsql: '.implode(', ', $missingOrInvalid));

        return 1;
    }

    $expectedPrivileges = [
        'pgsql' => ['allowed_table' => 'public.pengguna', 'allowed_privilege' => 'SELECT', 'denied_table' => 'public.siswa', 'denied_privilege' => 'SELECT'],
        'pgsql_owner' => ['allowed_table' => 'public.pengguna', 'allowed_privilege' => 'SELECT', 'denied_table' => 'public.log_audit', 'denied_privilege' => 'SELECT'],
        'pgsql_siswa' => ['allowed_table' => 'public.siswa', 'allowed_privilege' => 'SELECT', 'denied_table' => 'public.log_audit', 'denied_privilege' => 'SELECT'],
        'pgsql_tu' => ['allowed_table' => 'public.siswa', 'allowed_privilege' => 'SELECT', 'denied_table' => 'public.detail_pengajuan', 'denied_privilege' => 'SELECT'],
        'pgsql_kepsek' => ['allowed_table' => 'public.log_audit', 'allowed_privilege' => 'SELECT', 'denied_table' => 'public.alamat_siswa', 'denied_privilege' => 'SELECT'],
        'pgsql_wali_kelas' => ['allowed_table' => 'public.pengajuan_perubahan', 'allowed_privilege' => 'SELECT', 'denied_table' => 'public.alamat_siswa', 'denied_privilege' => 'SELECT'],
    ];

    $rows = [];
    $allPassed = true;

    foreach ($requiredConnections as $connectionName) {
        $connection = DB::connection($connectionName);
        $probe = $expectedPrivileges[$connectionName];

        try {
            $roleState = $connection->selectOne("SELECT current_user AS current_user, current_database() AS current_database, current_setting('search_path', true) AS search_path, rolsuper, rolcanlogin FROM pg_roles WHERE rolname = current_user");
            $allowedValue = $connection->selectOne("SELECT has_table_privilege(current_user, '{$probe['allowed_table']}', '{$probe['allowed_privilege']}') AS allowed");
            $deniedValue = $connection->selectOne("SELECT has_table_privilege(current_user, '{$probe['denied_table']}', '{$probe['denied_privilege']}') AS denied");
        } catch (Throwable $exception) {
            $this->error('Koneksi '.$connectionName.' gagal: '.$exception->getMessage());
            $allPassed = false;

            $rows[] = [
                'koneksi' => $connectionName,
                'current_user' => '-',
                'current_database' => '-',
                'search_path' => '-',
                'rolsuper' => '-',
                'rolcanlogin' => '-',
                'probe_izinkan' => '-',
                'probe_tolak' => '-',
                'status' => 'FAIL',
            ];

            continue;
        }

        $currentUser = $roleState->current_user ?? '-';
        $currentDatabase = $roleState->current_database ?? '-';
        $searchPath = $roleState->search_path ?? '-';
        $rolsuper = (bool) ($roleState->rolsuper ?? false);
        $rolcanlogin = (bool) ($roleState->rolcanlogin ?? false);
        $probeAllowed = (bool) ($allowedValue->allowed ?? false);
        $probeDenied = (bool) ($deniedValue->denied ?? false);
        $isValidDb = $currentDatabase === 'msbd_tubes_kel7';
        $passed = $isValidDb && $probeAllowed && ! $probeDenied;
        $allPassed = $allPassed && $passed;

        $rows[] = [
            'koneksi' => $connectionName,
            'current_user' => $currentUser,
            'current_database' => $currentDatabase,
            'search_path' => $searchPath,
            'rolsuper' => $rolsuper ? 'true' : 'false',
            'rolcanlogin' => $rolcanlogin ? 'true' : 'false',
            'probe_izinkan' => $probeAllowed ? 'YA' : 'TIDAK',
            'probe_tolak' => $probeDenied ? 'TIDAK' : 'YA',
            'status' => $passed ? 'PASS' : 'FAIL',
        ];
    }

    $this->table(['koneksi', 'current_user', 'current_database', 'search_path', 'rolsuper', 'rolcanlogin', 'probe_izinkan', 'probe_tolak', 'status'], $rows);

    if ($allPassed) {
        $this->info('Semua baris koneksi role lolos pemeriksaan baca-saja.');

        return 0;
    }

    $this->error('Terdapat baris role yang gagal verifikasi.');

    return 1;
})->purpose('Verify pgsql role connections and read-only access in local development');

Artisan::command('siakad:prepare-active-data {--execute}', function (ActiveSiakadDataPreparation $preparation) {
    return $preparation->run($this, (bool) $this->option('execute'));
})->purpose('Dry-run or apply the reviewed active SIAKAD data preparation');
