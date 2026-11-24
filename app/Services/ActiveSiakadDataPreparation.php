<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ActiveSiakadDataPreparation
{
    private const DATABASE = 'msbd_tubes_kel7';

    private const TARGETS = [
        'aisyahputrinasution' => 'Aisyah Putri Nasution',
        'mariasimbolon' => 'Maria Simbolon',
        'danielsitorus' => 'Daniel Sitorus',
        'sansmorgan' => 'Sans Morgan',
        'davidmorganneuron' => 'David Morgan Neuron',
        'ekasiraitspd' => 'Eka Sirait S.Pd',
    ];

    private const STUDENTS = [
        ['Ahmad Fikri Ramadhan', 'L', '2010-03-12', 'Islam', 'A', 'D', '1', '02', '3'],
        ['Siti Nur Aisyah', 'P', '2010-04-23', 'Islam', 'C', 'D', '1', '01', '2'],
        ['Raka Bintang Prasetyo', 'L', '2010-05-04', 'Islam', 'A', 'B', '1', '02', '4'],
        ['Putri Anindya Lestari', 'P', '2010-06-15', 'Islam', 'B', 'D', '1', '03', '3'],
        ['Kevin Aditya Wijaya', 'L', '2010-07-26', 'Kristen', 'C', 'F', '1', '02', '4'],
        ['Maria Felicia Sihombing', 'P', '2010-08-07', 'Katolik', 'B', 'D', '1', '04', '3'],
        ['Made Arya Wicaksana', 'L', '2010-09-18', 'Hindu', 'A', 'B', '1', '02', '4'],
        ['Ni Luh Ayu Pertiwi', 'P', '2010-10-29', 'Hindu', 'B', 'G', '1', '01', '3'],
        ['Taufik Hidayat', 'L', '2010-11-10', 'Islam', 'A', 'B', '1', '03', '2'],
        ['Nadia Maharani', 'P', '2010-12-21', 'Islam', 'C', 'D', '1', '01', '3'],
        ['Yohanes Bima Tarigan', 'L', '2011-01-08', 'Kristen', 'F', 'B', '2', '02', '4'],
        ['Clara Yuliana Simanjuntak', 'P', '2011-02-19', 'Kristen', 'B', 'D', '1', '04', '4'],
        ['Iqbal Maulana', 'L', '2011-03-30', 'Islam', 'A', 'A', '1', '03', '2'],
        ['Citra Kirana', 'P', '2011-04-11', 'Islam', 'C', 'D', '1', '01', '3'],
        ['Dewa Gede Mahendra', 'L', '2011-05-22', 'Hindu', 'A', 'F', '1', '02', '4'],
        ['Ayu Saraswati', 'P', '2011-06-03', 'Hindu', 'B', 'G', '1', '01', '4'],
        ['Samuel Daniel Manurung', 'L', '2011-07-14', 'Kristen', 'F', 'B', '2', '03', '4'],
        ['Theresia Natalia Samosir', 'P', '2011-08-25', 'Katolik', 'B', 'D', '1', '04', '4'],
        ['Farhan Rizki Akbar', 'L', '2011-09-06', 'Islam', 'A', 'A', '1', '02', '3'],
        ['Zahra Nabila Putri', 'P', '2011-10-17', 'Islam', 'C', 'D', '1', '01', '3'],
        ['Made Bagus Santika', 'L', '2010-02-02', 'Hindu', 'A', 'F', '1', '02', '4'],
        ['Komang Putri Utami', 'P', '2010-03-13', 'Hindu', 'B', 'G', '1', '01', '4'],
        ['Aditya Pramudya', 'L', '2010-04-24', 'Islam', 'C', 'A', '1', '03', '3'],
        ['Intan Permata Sari', 'P', '2010-05-05', 'Islam', 'B', 'D', '1', '01', '3'],
        ['Rafael Jonathan Hutabarat', 'L', '2010-06-16', 'Kristen', 'F', 'B', '2', '02', '5'],
        ['Debora Angelia Marbun', 'P', '2010-07-27', 'Kristen', 'B', 'D', '1', '04', '4'],
        ['Arief Wicaksono', 'L', '2010-08-08', 'Islam', 'A', 'A', '1', '03', '3'],
        ['Laila Rahmadani', 'P', '2010-09-19', 'Islam', 'C', 'D', '1', '01', '2'],
        ['Yudhistira Putra Kusuma', 'L', '2010-10-30', 'Buddha', 'A', 'F', '1', '02', '4'],
        ['Meilin Tan', 'P', '2010-11-11', 'Konghucu', 'B', 'H', '3', '03', '5'],
        ['Bagas Prakoso', 'L', '2010-12-22', 'Islam', 'A', 'A', '1', '02', '3'],
        ['Rani Oktaviani', 'P', '2011-01-09', 'Islam', 'C', 'D', '1', '01', '3'],
    ];

    private const DOCUMENTS = [
        'Kartu Keluarga',
        'Akta Kelahiran',
        'Ijazah/SKL',
        'Pas Foto 3x4',
    ];

    private array $primaryKeys = [];
    private array $foreignKeys = [];

    public function run(Command $command, bool $execute): int
    {
        $connection = DB::connection('pgsql');
        DB::setDefaultConnection('pgsql');

        $identity = $connection->selectOne(
            "SELECT current_database() AS database_name, current_user AS username,
                    rolsuper
             FROM pg_roles
             WHERE rolname = current_user"
        );

        if ($connection->getDriverName() !== 'pgsql'
            || $identity->database_name !== self::DATABASE
            || $identity->username !== 'postgres'
            || ! $identity->rolsuper) {
            $command->error('Perintah harus memakai superuser PostgreSQL pada database msbd_tubes_kel7.');

            return 1;
        }

        if (! $execute) {
            $connection->beginTransaction();

            try {
                $this->printDryRun($command, $connection);
            } finally {
                if ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
            }

            return 0;
        }

        $password = getenv('SIACAD_DUMMY_PASSWORD');
        if (! is_string($password) || strlen($password) < 8) {
            $command->error('Set SIACAD_DUMMY_PASSWORD (minimal 8 karakter) di environment proses untuk membuat akun dummy.');

            return 1;
        }

        $before = $this->tableCounts($connection);
        $createdFiles = [];
        $filesToRemove = [];
        $counts = [];

        try {
            $connection->transaction(function () use ($connection, $password, &$createdFiles, &$filesToRemove, &$counts) {
                $connection->statement("SELECT pg_advisory_xact_lock(hashtext('siakad:prepare-active-data'))");
                $matches = $this->findPersonMatches($connection);
                $this->assertUnambiguousMatches($matches);
                $plan = $this->discoverDeletePlan($connection, $matches);
                $this->assertNoDemoAccounts($plan);

                $counts['B1'] = $this->deleteTargets($connection, $matches, $plan, $filesToRemove, $password);
                $counts['B2'] = $this->applyAcademicData($connection);
                $counts['B3'] = $this->seedStudents($connection, $password, $createdFiles);
                $this->verifyTargetsRemoved($connection);
                $this->verifyDummyStudents($connection, $password);
                $this->verifyLogTriggers($connection);
            });
        } catch (Throwable $exception) {
            foreach ($createdFiles as $path) {
                if (Storage::disk('private')->exists($path)) {
                    Storage::disk('private')->delete($path);
                }
            }

            throw $exception;
        }

        foreach (array_unique($filesToRemove) as $path) {
            if (Storage::disk('private')->exists($path)
                && ! Storage::disk('private')->delete($path)) {
                $command->error("Database committed, tetapi berkas privat gagal dihapus: {$path}");

                return 1;
            }
        }

        Cache::forget('academic-header-context');
        $after = $this->tableCounts($connection);
        $this->printExecutionSummary($command, $counts, $before, $after, count(array_unique($filesToRemove)));

        return 0;
    }

    private function printDryRun(Command $command, $connection): void
    {
        $matches = $this->findPersonMatches($connection);
        $this->assertUnambiguousMatches($matches);
        $plan = $this->discoverDeletePlan($connection, $matches);
        $this->assertNoDemoAccounts($plan);
        $deleteCounts = collect($plan)->map(fn ($rows) => count($rows))->sortKeys()->all();
        $targetUserIds = $this->targetUserIds($plan);
        $targetUsernames = $this->targetUsernames($plan);
        $logCounts = $this->targetLogCounts($connection, $plan, $matches, $targetUserIds, $targetUsernames);
        $files = $this->targetFiles($plan);
        $years = $connection->table('tahun_ajaran')->orderBy('nama_tahun_ajaran')->get([
            'id_tahun_ajaran', 'nama_tahun_ajaran', 'is_aktif',
        ]);
        $semesters = $connection->table('semester')->orderBy('id_semester')->get([
            'id_semester', 'nama_semester', 'id_tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai', 'is_aktif',
        ]);
        $periods = $connection->table('periode_update')->orderBy('id_periode_update')->get([
            'id_periode_update', 'nama_periode', 'status', 'tanggal_mulai', 'tanggal_selesai',
        ]);
        $periodConflicts = $this->confirmationMergePlan($connection, $this->targetStudentIds($matches), false);
        $class = $connection->table('kelas as k')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('k.nama_kelas', 'X MIA 1')
            ->where('ta.nama_tahun_ajaran', '2026/2027')
            ->first(['k.id_kelas', 'k.kapasitas']);
        $teacherScheduleCount = $this->targetTeacherScheduleCount($connection, $matches);

        $command->info('DRY-RUN: database aktif terverifikasi; belum ada perubahan data atau berkas.');
        $command->line('B1 kecocokan nama:');
        $rows = [];
        foreach ($matches['hits'] as $hit) {
            $rows[] = [$hit['target'], $hit['table'], $hit['column'], $hit['value']];
        }
        $command->table(['Nama target', 'Tabel', 'Kolom', 'Nilai cocok'], $rows);
        $command->line('B1 perkiraan hapus per tabel:');
        $command->table(['Tabel', 'Baris'], collect($deleteCounts)->map(
            fn ($count, $table) => [$table, $count]
        )->values()->all());
        $command->line('B1 log immutable: '.json_encode($logCounts, JSON_UNESCAPED_UNICODE));
        $command->line('B1 berkas privat yang dirujuk: '.count($files));
        $command->line('B1 jadwal guru target yang dipertahankan dan dialihkan ke wali dummy: '.$teacherScheduleCount);
        $command->line('B2 tahun ajaran: '.json_encode($years, JSON_UNESCAPED_UNICODE));
        $command->line('B2 semester: '.json_encode($semesters, JSON_UNESCAPED_UNICODE));
        $command->line('B2 periode update: '.json_encode($periods, JSON_UNESCAPED_UNICODE));
        $command->line('B2 baris konfirmasi duplikat identik yang perlu digabung: '.json_encode($periodConflicts, JSON_UNESCAPED_UNICODE));
        $command->line('B3 kelas X MIA 1 T.A. 2026/2027: '.json_encode($class, JSON_UNESCAPED_UNICODE));
        $command->line('B3 rencana: 32 siswa, 32 akun siswa, 1 akun wali bila belum ada wali valid, 128 PDF privat.');
    }

    private function findPersonMatches($connection): array
    {
        $normalizedTargets = array_keys(self::TARGETS);
        $normalizedTargets[] = 'ekasirait';
        $columns = $connection->select(
            "SELECT c.relname AS table_name, a.attname AS column_name
             FROM pg_class c
             JOIN pg_namespace n ON n.oid = c.relnamespace
             JOIN pg_attribute a ON a.attrelid = c.oid
             JOIN information_schema.columns ic
               ON ic.table_schema = n.nspname
              AND ic.table_name = c.relname
              AND ic.column_name = a.attname
             WHERE n.nspname = 'public'
               AND c.relkind IN ('r', 'p')
               AND a.attnum > 0
               AND NOT a.attisdropped
               AND ic.data_type IN ('text', 'character varying', 'character')
               AND a.attname ~* '(nama|name|username)'
             ORDER BY c.relname, a.attnum"
        );
        $matches = ['hits' => [], 'rows' => [], 'ambiguous' => []];

        foreach ($columns as $column) {
            $table = $this->quoteIdentifier($column->table_name);
            $field = $this->quoteIdentifier($column->column_name);
            foreach ($normalizedTargets as $normalized) {
                $rows = $connection->select(
                    "SELECT ctid::text AS row_ctid, to_jsonb(t)::text AS row_json,
                            t.{$field}::text AS matched_value
                     FROM public.{$table} t
                     WHERE regexp_replace(lower(coalesce(t.{$field}::text, '')),
                           '[^[:alnum:]]', '', 'g') = ?",
                    [$normalized]
                );

                foreach ($rows as $result) {
                    $row = json_decode($result->row_json, true, flags: JSON_THROW_ON_ERROR);
                    $target = self::TARGETS[$normalized] ?? 'Eka Sirait S.Pd';
                    $matches['hits'][] = [
                        'target' => $target,
                        'normalized' => $normalized,
                        'table' => $column->table_name,
                        'column' => $column->column_name,
                        'value' => $result->matched_value,
                    ];

                    if (in_array($column->table_name, ['log_audit', 'log_login'], true)) {
                        continue;
                    }

                    $this->addPlanRow($matches['rows'], $column->table_name, $row, $result->row_ctid, 0);
                }
            }
        }

        foreach ($matches['rows'] as $table => $rows) {
            foreach ($rows as $entry) {
                if (! isset($entry['row']['id_pengguna'])
                    || in_array($table, ['pengguna', 'log_login', 'log_audit'], true)) {
                    continue;
                }

                $user = $connection->table('pengguna')
                    ->where('id_pengguna', $entry['row']['id_pengguna'])
                    ->first();
                if ($user) {
                    $this->addPlanRow(
                        $matches['rows'],
                        'pengguna',
                        (array) $user,
                        $this->rowCtid($connection, 'pengguna', 'id_pengguna', $user->id_pengguna),
                        0
                    );
                }
            }
        }

        foreach (self::TARGETS as $normalized => $name) {
            $candidateRows = [];
            foreach ($matches['rows'] as $tableRows) {
                foreach ($tableRows as $entry) {
                    if ($this->rowHasNormalizedValue($entry['row'], $normalized)) {
                        $candidateRows[] = $entry['row'];
                    }
                }
            }

            $candidateRows = $this->uniqueRows($candidateRows);
            if (count($candidateRows) > 1 && ! $this->rowsShareIdentity($candidateRows)) {
                $matches['ambiguous'][] = $name;
            }
        }

        return $matches;
    }

    private function assertUnambiguousMatches(array $matches): void
    {
        if ($matches['ambiguous'] !== []) {
            throw new RuntimeException(
                'B1 dibatalkan karena kecocokan nama ambigu: '.implode(', ', $matches['ambiguous'])
            );
        }
    }

    private function discoverDeletePlan($connection, array $matches): array
    {
        $plan = [];
        $queue = [];
        foreach ($matches['rows'] as $table => $rows) {
            foreach ($rows as $entry) {
                $this->addPlanRow($plan, $table, $entry['row'], $entry['ctid'], 0, $queue);
            }
        }

        $foreignKeys = $this->foreignKeys($connection);
        while ($queue !== []) {
            [$parentTable, $parentRow] = array_shift($queue);

            foreach ($foreignKeys[$parentTable] ?? [] as $foreignKey) {
                if (in_array($foreignKey['child'], ['log_audit', 'log_login'], true)
                    || in_array($foreignKey['delete_action'], ['n', 'd'], true)) {
                    continue;
                }
                if ($parentTable === 'guru'
                    && $foreignKey['child'] === 'jadwal_pelajaran'
                    && in_array('id_guru', $foreignKey['child_columns'], true)) {
                    continue;
                }

                $parentValues = [];
                foreach ($foreignKey['parent_columns'] as $index => $parentColumn) {
                    $parentValues[$foreignKey['child_columns'][$index]] = $parentRow[$parentColumn] ?? null;
                }
                if (in_array(null, $parentValues, true)) {
                    continue;
                }

                foreach ($this->findReferencingRows($connection, $foreignKey['child'], $parentValues) as $entry) {
                    $this->addPlanRow(
                        $plan,
                        $foreignKey['child'],
                        $entry['row'],
                        $entry['ctid'],
                        1,
                        $queue
                    );
                }
            }
        }

        $userIds = $this->targetUserIds($plan);
        $studentIds = $this->targetStudentIds($matches);
        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'failed_jobs', 'job_batches'] as $table) {
            $exists = $connection->selectOne('SELECT to_regclass(?) AS table_name', ['public.'.$table]);
            if (! $exists?->table_name) {
                continue;
            }

            $columns = $connection->select(
                "SELECT column_name, data_type
                 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = ?
                 ORDER BY ordinal_position",
                [$table]
            );
            foreach ($columns as $column) {
                if (in_array($column->column_name, ['user_id', 'id_pengguna'], true) && $userIds !== []) {
                    foreach ($this->findRowsByValues($connection, $table, $column->column_name, $userIds) as $entry) {
                        $this->addPlanRow($plan, $table, $entry['row'], $entry['ctid'], 1);
                    }
                }
                if ($column->column_name === 'id_siswa' && $studentIds !== []) {
                    foreach ($this->findRowsByValues($connection, $table, $column->column_name, $studentIds) as $entry) {
                        $this->addPlanRow($plan, $table, $entry['row'], $entry['ctid'], 1);
                    }
                }
                if (! in_array($column->data_type, ['text', 'character varying', 'character', 'json', 'jsonb'], true)) {
                    continue;
                }

                foreach (array_merge(array_values(self::TARGETS), $this->targetUsernames($plan)) as $term) {
                    foreach ($this->findRowsLike($connection, $table, $column->column_name, $term) as $entry) {
                        $this->addPlanRow($plan, $table, $entry['row'], $entry['ctid'], 1);
                    }
                }
            }
        }

        return $plan;
    }

    private function deleteTargets($connection, array $matches, array $plan, array &$filesToRemove, string $password): array
    {
        $targetStudentIds = $this->targetStudentIds($matches);
        $targetUserIds = $this->targetUserIds($plan);
        $targetUsernames = $this->targetUsernames($plan);
        $targetFiles = $this->targetFiles($plan);
        $this->assertPrivateFilesAreNotShared($connection, $targetFiles, $plan);

        $statusPeriods = $connection->table('periode_update as p')
            ->join('konfirmasi_data as c', 'c.id_periode_update', '=', 'p.id_periode_update')
            ->whereIn('c.id_siswa', $targetStudentIds)
            ->where('p.status', 'ditutup')
            ->distinct()
            ->pluck('p.id_periode_update')
            ->all();

        if ($statusPeriods !== []) {
            $connection->table('periode_update')
                ->whereIn('id_periode_update', $statusPeriods)
                ->update(['status' => 'draft']);
        }

        $removedLogs = $this->deleteTargetLogs($connection, $plan, $matches, $targetUserIds, $targetUsernames);

        $teacherIds = $this->targetTeacherIds($matches);
        $scheduleCount = $this->targetTeacherScheduleCount($connection, $matches);
        if ($scheduleCount > 0) {
            $waliRoleId = $connection->table('role')->where('nama_role', 'wali_kelas')->value('id_role');
            if (! $waliRoleId) {
                throw new RuntimeException('Role wali_kelas tidak tersedia untuk mempertahankan jadwal guru target.');
            }
            $dummyTeacher = $this->ensureDummyTeacher($connection, (int) $waliRoleId, $password);
            $connection->table('jadwal_pelajaran')
                ->whereIn('id_guru', $teacherIds)
                ->update(['id_guru' => $dummyTeacher['id_guru']]);
        }

        $entries = [];
        foreach ($plan as $table => $tableRows) {
            foreach ($tableRows as $entry) {
                $entries[] = ['table' => $table] + $entry;
            }
        }
        usort($entries, fn ($left, $right) => $right['depth'] <=> $left['depth']);

        $counts = [];
        foreach ($entries as $entry) {
            $deleted = $this->deletePlanRow($connection, $entry['table'], $entry['row'], $entry['ctid']);
            if ($deleted) {
                $counts[$entry['table']] = ($counts[$entry['table']] ?? 0) + $deleted;
            }
        }

        if ($statusPeriods !== []) {
            $connection->table('periode_update')
                ->whereIn('id_periode_update', $statusPeriods)
                ->update(['status' => 'ditutup']);
        }

        $moreRemovedLogs = $this->deleteTargetLogs($connection, $plan, $matches, $targetUserIds, $targetUsernames);
        foreach (['log_audit', 'log_login'] as $table) {
            $counts[$table] = ($removedLogs[$table] ?? 0) + ($moreRemovedLogs[$table] ?? 0);
        }
        if ($scheduleCount > 0) {
            $counts['jadwal_dialihkan_ke_wali_dummy'] = $scheduleCount;
        }
        $filesToRemove = array_merge($filesToRemove, $targetFiles);

        return $counts;
    }

    private function deleteTargetLogs($connection, array $plan, array $matches, array $userIds, array $usernames): array
    {
        $auditIds = $this->targetAuditLogIds($connection, $plan, $matches, $userIds, $usernames);
        $loginIds = $connection->table('log_login')
            ->where(function ($query) use ($userIds, $usernames, $matches) {
                if ($userIds !== []) {
                    $query->whereIn('id_pengguna', $userIds);
                }
                if ($usernames !== []) {
                    $query->orWhereIn('username_dicoba', $usernames);
                }
                foreach ($matches['hits'] as $hit) {
                    $query->orWhereRaw(
                        "regexp_replace(lower(username_dicoba), '[^[:alnum:]]', '', 'g') = ?",
                        [$hit['normalized']]
                    );
                }
            })
            ->pluck('id_log_login')
            ->all();

        $triggers = $connection->select(
            "SELECT c.relname AS table_name, t.tgname, t.tgenabled
             FROM pg_trigger t
             JOIN pg_class c ON c.oid = t.tgrelid
             WHERE NOT t.tgisinternal
               AND (c.relname, t.tgname) IN (
                   ('log_audit', 'trg_blokir_update_log_audit'),
                   ('log_login', 'trg_blokir_update_log_login')
               )"
        );
        foreach ($triggers as $trigger) {
            if ($trigger->tgenabled !== 'O') {
                throw new RuntimeException("Trigger pemblokir {$trigger->tgname} tidak aktif sebelum pembersihan B1.");
            }
        }

        $disabled = [];
        $deleted = ['log_audit' => 0, 'log_login' => 0];
        try {
            if ($auditIds !== []) {
                $connection->statement('ALTER TABLE public.log_audit DISABLE TRIGGER trg_blokir_update_log_audit');
                $disabled[] = ['log_audit', 'trg_blokir_update_log_audit'];
                $deleted['log_audit'] = $connection->table('log_audit')->whereIn('id_log', $auditIds)->delete();
            }
            if ($loginIds !== []) {
                $connection->statement('ALTER TABLE public.log_login DISABLE TRIGGER trg_blokir_update_log_login');
                $disabled[] = ['log_login', 'trg_blokir_update_log_login'];
                $deleted['log_login'] = $connection->table('log_login')->whereIn('id_log_login', $loginIds)->delete();
            }
        } finally {
            foreach (array_reverse($disabled) as [$table, $trigger]) {
                $connection->statement(
                    'ALTER TABLE public.'.$this->quoteIdentifier($table).' ENABLE TRIGGER '.$this->quoteIdentifier($trigger)
                );
            }
        }

        return $deleted;
    }

    private function targetAuditLogIds($connection, array $plan, array $matches, array $userIds, array $usernames): array
    {
        $query = $connection->table('log_audit')->select('id_log');
        $query->where(function ($where) use ($plan, $matches, $userIds, $usernames) {
            $hasClause = false;
            if ($userIds !== []) {
                $where->whereIn('id_pengguna_pengubah', $userIds);
                $hasClause = true;
            }
            if ($usernames !== []) {
                $where->orWhereIn('username_pengubah', $usernames);
                $hasClause = true;
            }
            foreach ($matches['hits'] as $hit) {
                $where->orWhereRaw(
                    "regexp_replace(lower(concat_ws(' ', coalesce(nilai_lama::text, ''),
                         coalesce(nilai_baru::text, ''), coalesce(username_pengubah, ''))),
                         '[^[:alnum:]]', '', 'g') LIKE ?",
                    ['%'.$hit['normalized'].'%']
                );
                $hasClause = true;
            }
            foreach ($plan as $table => $rows) {
                foreach ($rows as $entry) {
                    $keys = $this->primaryKeyColumns($table);
                    $where->orWhere(function ($rowQuery) use ($table, $entry, $keys) {
                        if (count($keys) === 1) {
                            $rowQuery->where(function ($idQuery) use ($table, $entry, $keys) {
                                $idQuery->where(function ($dataQuery) use ($table, $entry, $keys) {
                                    $dataQuery->where('nama_tabel', $table)
                                        ->where('id_data', (string) ($entry['row'][$keys[0]] ?? ''));
                                });
                                foreach ($keys as $key) {
                                    if (! array_key_exists($key, $entry['row'])) {
                                        continue;
                                    }
                                    $json = json_encode([$key => $entry['row'][$key]], JSON_THROW_ON_ERROR);
                                    $idQuery->orWhere(function ($jsonQuery) use ($table, $json) {
                                        $jsonQuery->where('nama_tabel', $table)
                                            ->whereRaw(
                                                '(nilai_lama @> ?::jsonb OR nilai_baru @> ?::jsonb)',
                                                [$json, $json]
                                            );
                                    });
                                }
                            });
                        } else {
                            $rowQuery->where('nama_tabel', $table);
                            foreach ($keys as $key) {
                                if (! array_key_exists($key, $entry['row'])) {
                                    continue;
                                }
                                $json = json_encode([$key => $entry['row'][$key]], JSON_THROW_ON_ERROR);
                                $rowQuery->orWhere(function ($jsonQuery) use ($table, $json) {
                                    $jsonQuery->where('nama_tabel', $table)
                                        ->whereRaw(
                                            '(nilai_lama @> ?::jsonb OR nilai_baru @> ?::jsonb)',
                                            [$json, $json]
                                        );
                                });
                            }
                        }
                    });
                    $hasClause = true;
                }
            }

            if (! $hasClause) {
                $where->whereRaw('FALSE');
            }
        });

        return $query->distinct()->pluck('id_log')->all();
    }

    private function targetLogCounts($connection, array $plan, array $matches, array $userIds, array $usernames): array
    {
        $audit = $this->targetAuditLogIds($connection, $plan, $matches, $userIds, $usernames);
        $login = $connection->table('log_login')
            ->where(function ($query) use ($userIds, $usernames) {
                if ($userIds !== []) {
                    $query->whereIn('id_pengguna', $userIds);
                }
                if ($usernames !== []) {
                    $query->orWhereIn('username_dicoba', $usernames);
                }
            })
            ->count();

        return ['log_audit' => count($audit), 'log_login' => $login];
    }

    private function applyAcademicData($connection): array
    {
        $years = $connection->table('tahun_ajaran')
            ->orderBy('id_tahun_ajaran')
            ->get()
            ->keyBy('nama_tahun_ajaran');
        foreach (['2026/2027', '2027/2028', '2028/2029'] as $name) {
            if (! isset($years[$name])) {
                throw new RuntimeException("Tahun ajaran {$name} tidak ditemukan; B2 tidak dijalankan.");
            }
        }
        $activeYearId = (int) $years['2026/2027']->id_tahun_ajaran;

        $connection->table('tahun_ajaran')->update(['is_aktif' => false]);
        $connection->table('tahun_ajaran')
            ->where('id_tahun_ajaran', $activeYearId)
            ->update(['is_aktif' => true]);

        $semester = $connection->table('semester')
            ->where('id_tahun_ajaran', $activeYearId)
            ->where('nama_semester', 'Ganjil')
            ->first();
        if (! $semester) {
            throw new RuntimeException('Semester Ganjil 2026/2027 tidak ditemukan.');
        }

        $otherSemesters = $connection->table('semester')
            ->where('id_semester', '<>', $semester->id_semester)
            ->get(['id_semester']);
        $otherIds = $otherSemesters->pluck('id_semester')->all();
        $remainingDependencies = $this->semesterDependencies($connection, $otherIds);
        if ($remainingDependencies !== []) {
            throw new RuntimeException(
                'B2 menemukan dependensi semester selain target yang belum terselesaikan: '.json_encode($remainingDependencies)
            );
        }

        $connection->table('semester')->whereIn('id_semester', $otherIds)->delete();
        $connection->table('semester')
            ->where('id_semester', $semester->id_semester)
            ->update([
                'nama_semester' => 'Ganjil',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'is_aktif' => true,
            ]);

        $period = $connection->table('periode_update')
            ->where('nama_periode', 'Update Data 2026/2027')
            ->whereDate('tanggal_mulai', '2026-07-01')
            ->whereDate('tanggal_selesai', '2027-06-30')
            ->orderBy('id_periode_update')
            ->first();
        if (! $period) {
            throw new RuntimeException('Tidak ada periode 2026/2027 dengan rentang tanggal target.');
        }

        $connection->table('periode_update')->update(['status' => 'draft']);
        $merge = $this->confirmationMergePlan($connection, [], true, (int) $period->id_periode_update);
        $removedConfirmations = 0;
        foreach ($merge as $studentId => $rows) {
            $keeper = collect($rows)->firstWhere('id_periode_update', $period->id_periode_update)
                ?? collect($rows)->sortBy('id_konfirmasi')->first();
            foreach ($rows as $row) {
                if ((int) $row->id_konfirmasi === (int) $keeper->id_konfirmasi) {
                    continue;
                }
                $removedConfirmations += $connection->table('konfirmasi_data')
                    ->where('id_konfirmasi', $row->id_konfirmasi)
                    ->delete();
            }
            if ((int) $keeper->id_periode_update !== (int) $period->id_periode_update) {
                $connection->table('konfirmasi_data')
                    ->where('id_konfirmasi', $keeper->id_konfirmasi)
                    ->update(['id_periode_update' => $period->id_periode_update]);
            }
        }

        $connection->table('pengajuan_perubahan')
            ->whereNotNull('id_periode_update')
            ->where('id_periode_update', '<>', $period->id_periode_update)
            ->update(['id_periode_update' => $period->id_periode_update]);

        $periodIds = $connection->table('periode_update')
            ->where('id_periode_update', '<>', $period->id_periode_update)
            ->pluck('id_periode_update')
            ->all();
        $remainingPeriodDependencies = $this->periodDependencies($connection, $periodIds);
        if ($remainingPeriodDependencies !== []) {
            throw new RuntimeException(
                'B2 menemukan dependensi periode yang belum dapat dipindahkan: '.json_encode($remainingPeriodDependencies)
            );
        }

        $connection->table('periode_update')->whereIn('id_periode_update', $periodIds)->delete();
        $connection->table('periode_update')
            ->where('id_periode_update', $period->id_periode_update)
            ->update([
                'nama_periode' => 'Update Data 2026/2027',
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'status' => 'aktif',
            ]);

        return [
            'semester_dihapus' => count($otherIds),
            'periode_dihapus' => count($periodIds),
            'konfirmasi_duplikat_dihapus' => $removedConfirmations,
            'periode_tersisa_id' => (int) $period->id_periode_update,
        ];
    }

    private function confirmationMergePlan($connection, array $excludedStudentIds, bool $assertIdentical, ?int $targetPeriodId = null): array
    {
        $query = $connection->table('konfirmasi_data')->orderBy('id_siswa')->orderBy('id_konfirmasi');
        if ($excludedStudentIds !== []) {
            $query->whereNotIn('id_siswa', $excludedStudentIds);
        }
        $groups = $query->get()->groupBy('id_siswa');
        $result = [];
        $identityColumns = ['id_konfirmasi', 'id_periode_update', 'id_siswa'];

        foreach ($groups as $studentId => $rows) {
            if ($rows->count() <= 1 && $targetPeriodId === null) {
                continue;
            }

            if ($rows->count() > 1) {
                $values = $rows->map(function ($row) use ($identityColumns) {
                    $data = (array) $row;
                    foreach ($identityColumns as $column) {
                        unset($data[$column]);
                    }

                    ksort($data);
                    return $data;
                });

                if ($assertIdentical && $values->unique(fn ($value) => json_encode($value))->count() !== 1) {
                    throw new RuntimeException(
                        "Konfirmasi siswa {$studentId} berbeda antarperiode; B2 dibatalkan untuk menjaga data."
                    );
                }
            }

            $result[(int) $studentId] = $rows->all();
        }

        return $result;
    }

    private function semesterDependencies($connection, array $semesterIds): array
    {
        if ($semesterIds === []) {
            return [];
        }

        $dependencies = [];
        foreach ($this->foreignKeys($connection)['semester'] ?? [] as $foreignKey) {
            $counts = $connection->table($foreignKey['child'])
                ->whereIn($foreignKey['child_columns'][0], $semesterIds)
                ->count();
            if ($counts > 0) {
                $dependencies[$foreignKey['child']] = $counts;
            }
        }

        return $dependencies;
    }

    private function periodDependencies($connection, array $periodIds): array
    {
        if ($periodIds === []) {
            return [];
        }

        $dependencies = [];
        foreach ($this->foreignKeys($connection)['periode_update'] ?? [] as $foreignKey) {
            $counts = $connection->table($foreignKey['child'])
                ->whereIn($foreignKey['child_columns'][0], $periodIds)
                ->count();
            if ($counts > 0) {
                $dependencies[$foreignKey['child']] = $counts;
            }
        }

        return $dependencies;
    }

    private function seedStudents($connection, string $password, array &$createdFiles): array
    {
        $class = $connection->table('kelas as k')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('k.nama_kelas', 'X MIA 1')
            ->where('ta.nama_tahun_ajaran', '2026/2027')
            ->where('k.is_aktif', true)
            ->first(['k.id_kelas', 'k.kapasitas', 'k.id_tingkat', 'k.id_jurusan', 'k.id_tahun_ajaran']);
        if (! $class || (int) $class->kapasitas < count(self::STUDENTS)) {
            throw new RuntimeException('Kelas X MIA 1 T.A. 2026/2027 harus tersedia dengan kapasitas minimal 32.');
        }

        $semester = $connection->table('semester')
            ->where('id_tahun_ajaran', $class->id_tahun_ajaran)
            ->where('nama_semester', 'Ganjil')
            ->where('is_aktif', true)
            ->first();
        $statusId = $connection->table('status_siswa')->where('kode_status', 'aktif')->value('id_status_siswa');
        $roleIds = $connection->table('role')->pluck('id_role', 'nama_role');
        if (! $semester || ! $statusId || ! isset($roleIds['siswa'], $roleIds['wali_kelas'])) {
            throw new RuntimeException('Referensi semester aktif, status siswa, atau role belum lengkap.');
        }

        $region = $this->ensureMedanRegion($connection);
        $religions = $connection->table('agama')->where('is_aktif', true)->pluck('id_agama', 'nama_agama');
        $jobs = $connection->table('pekerjaan')->where('is_aktif', true)->pluck('id_pekerjaan', 'nama_pekerjaan');
        if (! isset($religions['Islam'], $religions['Kristen'], $religions['Katolik'], $religions['Hindu'], $religions['Buddha'], $religions['Konghucu'])) {
            throw new RuntimeException('Referensi agama yang dipakai siswa dummy belum lengkap.');
        }

        $wali = $this->ensureClassTeacher(
            $connection,
            (int) $class->id_kelas,
            (int) $semester->id_semester,
            (int) $roleIds['wali_kelas'],
            $password
        );

        $existingDummyStudentIds = $connection->table('pengguna as p')
            ->join('siswa as s', 's.id_pengguna', '=', 'p.id_pengguna')
            ->whereIn('p.username', $this->studentUsernames())
            ->where('s.id_pengguna', '>', 0)
            ->pluck('s.id_siswa')
            ->all();
        $activeCount = $connection->table('keanggotaan_kelas')
            ->where('id_kelas', $class->id_kelas)
            ->where('id_semester', $semester->id_semester)
            ->where('status', 'aktif')
            ->count();
        $otherCount = $activeCount - $connection->table('keanggotaan_kelas')
            ->where('id_kelas', $class->id_kelas)
            ->where('id_semester', $semester->id_semester)
            ->where('status', 'aktif')
            ->whereIn('id_siswa', $existingDummyStudentIds)
            ->count();
        if ($otherCount + count(self::STUDENTS) > (int) $class->kapasitas) {
            throw new RuntimeException('Kapasitas kelas tidak cukup untuk 32 siswa dummy tanpa mengganggu siswa lain.');
        }

        $documentTypes = $connection->table('jenis_dokumen')->get(['nama_dokumen']);
        $canonicalDocuments = [];
        foreach (self::DOCUMENTS as $document) {
            $normalized = Str::lower(str_replace([' ', '/'], '', $document));
            $match = $documentTypes->first(
                fn ($row) => Str::lower(str_replace([' ', '/'], '', $row->nama_dokumen)) === $normalized
            );
            if (! $match) {
                throw new RuntimeException("Jenis dokumen wajib {$document} tidak tersedia.");
            }
            $canonicalDocuments[] = $match->nama_dokumen;
        }

        $studentIds = [];
        foreach (self::STUDENTS as $offset => $definition) {
            $number = $offset + 1;
            [$name, $gender, $birthDate, $religion, $hobby, $aspiration, $home, $transport, $income] = $definition;
            $username = sprintf('siswa_dummy_2026_%02d', $number);
            $nis = (string) (20260000 + $number);
            $nisn = (string) (9000000000 + $number);
            $nik = sprintf('999999999999%04d', $number);
            $email = sprintf('siswa.dummy.%02d@example.test', $number);
            $studentPhone = sprintf('0813000000%02d', $number);
            $religionId = (int) $religions[$religion];
            $parentJobs = array_values($jobs->all());
            if ($parentJobs === []) {
                throw new RuntimeException('Referensi pekerjaan aktif belum tersedia.');
            }

            $userId = $this->ensureUser(
                $connection,
                $username,
                (int) $roleIds['siswa'],
                Hash::make($password),
                $name
            );
            $studentId = $this->ensureStudent($connection, [
                'id_pengguna' => $userId,
                'id_status_siswa' => (int) $statusId,
                'id_agama' => $religionId,
                'nis' => $nis,
                'nisn' => $nisn,
                'nik' => $nik,
                'nama_lengkap' => $name,
                'jenis_kelamin' => $gender,
                'tempat_lahir' => $region['kota'],
                'tanggal_lahir' => $birthDate,
                'email' => $email,
                'no_telepon' => $studentPhone,
                'tanggal_masuk' => '2026-07-01',
                'dibuat_pada' => now(),
                'diubah_pada' => now(),
            ]);
            $studentIds[] = $studentId;

            $this->upsertAddress($connection, $studentId, $region, $number);
            $this->upsertParents($connection, $studentId, $parentJobs, $number, $region);
            $applicationId = $this->ensurePpdbApplication(
                $connection,
                $number,
                $name,
                $gender,
                $birthDate,
                $religionId,
                $nis,
                $nisn,
                $nik,
                $email,
                $studentPhone,
                $class,
                $region,
                $definition
            );
            $this->upsertPpdbRelations($connection, $applicationId, $number, $region, $parentJobs);
            $this->upsertEnrollment(
                $connection,
                $studentId,
                (int) $class->id_kelas,
                (int) $semester->id_semester
            );

            foreach ($canonicalDocuments as $document) {
                $path = 'dokumen-siswa/dummy/2026/'.sprintf('%s-%02d-%s.pdf', $nisn, $number, Str::slug($document));
                $pdf = $this->placeholderPdf($document, $name, $nisn);
                if (! Storage::disk('private')->exists($path)) {
                    Storage::disk('private')->put($path, $pdf);
                    $createdFiles[] = $path;
                } elseif (Storage::disk('private')->get($path) !== $pdf) {
                    throw new RuntimeException("Berkas dummy yang sudah ada tidak cocok dengan konten yang diharapkan: {$path}");
                }

                $existingDocument = $connection->table('dokumen_siswa')->where('path_file', $path)->first();
                $documentData = [
                    'id_siswa' => $studentId,
                    'id_pendaftaran' => $applicationId,
                    'jenis_dokumen' => $document,
                    'nama_file_asli' => 'DOKUMEN_DUMMY_'.$number.'_'.Str::slug($document).'.pdf',
                    'path_file' => $path,
                    'status_verifikasi' => 'valid',
                    'catatan' => 'DOKUMEN DUMMY - BUKAN DOKUMEN ASLI',
                    'diunggah_pada' => now(),
                ];
                if ($existingDocument) {
                    if ((int) $existingDocument->id_siswa !== $studentId
                        || (int) $existingDocument->id_pendaftaran !== $applicationId) {
                        throw new RuntimeException("Berkas dummy {$path} sudah dipakai oleh data lain.");
                    }
                    $connection->table('dokumen_siswa')
                        ->where('id_dokumen', $existingDocument->id_dokumen)
                        ->update($documentData);
                } else {
                    $connection->table('dokumen_siswa')->insert($documentData);
                }
            }
        }

        $this->activateConfirmationTrigger($connection);

        return [
            'siswa' => count($studentIds),
            'akun_siswa' => count($studentIds),
            'wali_username' => $wali['username'],
            'dokumen' => count($studentIds) * count($canonicalDocuments),
            'kelas_id' => (int) $class->id_kelas,
            'semester_id' => (int) $semester->id_semester,
        ];
    }

    private function ensureMedanRegion($connection): array
    {
        $province = $connection->table('provinsi')->where('nama_provinsi', 'Sumatera Utara')->first();
        $city = $province
            ? $connection->table('kota')
                ->where('id_provinsi', $province->id_provinsi)
                ->where('nama_kota', 'Kota Medan')
                ->first()
            : null;
        if (! $province || ! $city) {
            throw new RuntimeException('Referensi Sumatera Utara dan Kota Medan harus tersedia.');
        }

        $district = $connection->table('kecamatan')
            ->where('id_kota', $city->id_kota)
            ->where('nama_kecamatan', 'Medan Kota')
            ->first();
        if (! $district) {
            $districtId = $connection->table('kecamatan')->insertGetId([
                'id_kota' => $city->id_kota,
                'nama_kecamatan' => 'Medan Kota',
            ], 'id_kecamatan');
            $district = (object) ['id_kecamatan' => $districtId, 'nama_kecamatan' => 'Medan Kota'];
        }

        $village = $connection->table('kelurahan')
            ->where('id_kecamatan', $district->id_kecamatan)
            ->where('nama_kelurahan', 'Pasar Baru')
            ->first();
        if (! $village) {
            $villageId = $connection->table('kelurahan')->insertGetId([
                'id_kecamatan' => $district->id_kecamatan,
                'nama_kelurahan' => 'Pasar Baru',
                'kode_pos' => null,
            ], 'id_kelurahan');
            $village = (object) ['id_kelurahan' => $villageId, 'nama_kelurahan' => 'Pasar Baru', 'kode_pos' => null];
        }

        return [
            'provinsi' => $province->nama_provinsi,
            'kota' => $city->nama_kota,
            'kecamatan' => $district->nama_kecamatan,
            'kelurahan' => $village->nama_kelurahan,
            'kode_pos' => $village->kode_pos,
        ];
    }

    private function ensureClassTeacher($connection, int $classId, int $semesterId, int $waliRoleId, string $password): array
    {
        $assignment = $connection->table('penugasan_wali_kelas as pw')
            ->join('guru as g', 'g.id_guru', '=', 'pw.id_guru')
            ->leftJoin('pengguna as p', 'p.id_pengguna', '=', 'g.id_pengguna')
            ->where('pw.id_kelas', $classId)
            ->where('pw.id_semester', $semesterId)
            ->first(['g.id_guru', 'g.id_pengguna', 'g.nama_guru', 'p.username', 'p.id_role']);
        if ($assignment && $assignment->id_pengguna && (int) $assignment->id_role === $waliRoleId) {
            return ['id_guru' => (int) $assignment->id_guru, 'username' => $assignment->username];
        }
        if ($assignment) {
            throw new RuntimeException('Penugasan wali kelas yang ada tidak dapat dipakai untuk B3.');
        }

        $dummyTeacher = $this->ensureDummyTeacher($connection, $waliRoleId, $password);
        $teacherId = $dummyTeacher['id_guru'];
        $username = $dummyTeacher['username'];
        $connection->table('penugasan_wali_kelas')->insert([
            'id_kelas' => $classId,
            'id_guru' => $teacherId,
            'id_semester' => $semesterId,
            'ditugaskan_pada' => now(),
        ]);

        return ['id_guru' => $teacherId, 'username' => $username];
    }

    private function ensureDummyTeacher($connection, int $waliRoleId, string $password): array
    {
        $username = 'wali_dummy_2026';
        $userId = $this->ensureUser(
            $connection,
            $username,
            $waliRoleId,
            Hash::make($password),
            'Wali Kelas Dummy 2026'
        );
        $teacher = $connection->table('guru')->where('id_pengguna', $userId)->first();
        if (! $teacher) {
            $teacherId = $connection->table('guru')->insertGetId([
                'id_pengguna' => $userId,
                'nip' => 'DUMMY-WALI-2026',
                'nama_guru' => 'Wali Kelas Dummy 2026',
                'email' => 'wali.dummy.2026@example.test',
                'no_telepon' => '081399000001',
                'is_aktif' => true,
            ], 'id_guru');
        } else {
            $teacherId = (int) $teacher->id_guru;
            $connection->table('guru')->where('id_guru', $teacherId)->update([
                'nama_guru' => 'Wali Kelas Dummy 2026',
                'is_aktif' => true,
            ]);
        }

        return ['id_guru' => (int) $teacherId, 'username' => $username];
    }

    private function ensureUser($connection, string $username, int $roleId, string $passwordHash, string $expectedName): int
    {
        $existing = $connection->table('pengguna')->where('username', $username)->first();
        if ($existing) {
            if ((int) $existing->id_role !== $roleId) {
                throw new RuntimeException("Username dummy {$username} sudah dipakai role lain.");
            }
            $student = $connection->table('siswa')->where('id_pengguna', $existing->id_pengguna)->first();
            $teacher = $connection->table('guru')->where('id_pengguna', $existing->id_pengguna)->first();
            if (($student && $student->nama_lengkap !== $expectedName)
                || ($teacher && $teacher->nama_guru !== $expectedName)) {
                throw new RuntimeException("Username dummy {$username} sudah terhubung ke orang lain.");
            }
            $connection->table('pengguna')->where('id_pengguna', $existing->id_pengguna)->update([
                'password_hash' => $passwordHash,
                'is_aktif' => true,
                'diubah_pada' => now(),
            ]);

            return (int) $existing->id_pengguna;
        }

        return (int) $connection->table('pengguna')->insertGetId([
            'id_role' => $roleId,
            'username' => $username,
            'password_hash' => $passwordHash,
            'is_aktif' => true,
            'dibuat_pada' => now(),
            'diubah_pada' => now(),
        ], 'id_pengguna');
    }

    private function ensureStudent($connection, array $data): int
    {
        $existing = $connection->table('siswa')->where('nisn', $data['nisn'])->first();
        if ($existing && ((int) $existing->id_pengguna !== (int) $data['id_pengguna']
            || $existing->nama_lengkap !== $data['nama_lengkap'])) {
            throw new RuntimeException("NISN dummy {$data['nisn']} sudah dipakai siswa lain.");
        }
        if ($existing) {
            $connection->table('siswa')->where('id_siswa', $existing->id_siswa)->update($data);

            return (int) $existing->id_siswa;
        }

        foreach (['nis' => 'NIS', 'nik' => 'NIK'] as $key => $label) {
            if ($connection->table('siswa')->where($key, $data[$key])->exists()) {
                throw new RuntimeException("{$label} dummy {$data[$key]} sudah dipakai siswa lain.");
            }
        }

        return (int) $connection->table('siswa')->insertGetId($data, 'id_siswa');
    }

    private function upsertAddress($connection, int $studentId, array $region, int $number): void
    {
        $data = [
            'alamat' => sprintf('Jalan Dummy %02d, Kecamatan %s', $number, $region['kecamatan']),
            'rt' => sprintf('%03d', $number),
            'rw' => sprintf('%03d', $number),
            'kelurahan' => $region['kelurahan'],
            'kecamatan' => $region['kecamatan'],
            'kota' => $region['kota'],
            'provinsi' => $region['provinsi'],
            'kode_pos' => $region['kode_pos'],
        ];
        $existing = $connection->table('alamat_siswa')->where('id_siswa', $studentId)->first();
        if ($existing) {
            $connection->table('alamat_siswa')->where('id_alamat', $existing->id_alamat)->update($data);
        } else {
            $connection->table('alamat_siswa')->insert(['id_siswa' => $studentId] + $data);
        }
    }

    private function upsertParents($connection, int $studentId, array $jobIds, int $number, array $region): void
    {
        $lastName = Str::of(self::STUDENTS[$number - 1][0])->explode(' ')->last();
        foreach (['ayah', 'ibu', 'wali'] as $offset => $relationship) {
            $data = [
                'nama_lengkap' => ucfirst($relationship).' Dummy '.$lastName,
                'id_pekerjaan' => (int) $jobIds[($number + $offset) % count($jobIds)],
                'no_telepon' => sprintf('0814000000%02d', $number * 3 + $offset),
                'email' => sprintf('%s.dummy.%02d@example.test', $relationship, $number),
                'alamat' => sprintf('Jalan Dummy %02d, %s', $number, $region['kota']),
            ];
            $existing = $connection->table('orang_tua_wali')
                ->where('id_siswa', $studentId)
                ->where('hubungan', $relationship)
                ->first();
            if ($existing) {
                $connection->table('orang_tua_wali')
                    ->where('id_orang_tua_wali', $existing->id_orang_tua_wali)
                    ->update($data);
            } else {
                $connection->table('orang_tua_wali')->insert([
                    'id_siswa' => $studentId,
                    'hubungan' => $relationship,
                ] + $data);
            }
        }
    }

    private function ensurePpdbApplication(
        $connection,
        int $number,
        string $name,
        string $gender,
        string $birthDate,
        int $religionId,
        string $nis,
        string $nisn,
        string $nik,
        string $email,
        string $phone,
        object $class,
        array $region,
        array $definition
    ): int {
        $applicationNumber = sprintf('DUMMY-SIAKAD-2026-%02d', $number);
        $data = [
            'nomor_pendaftaran' => $applicationNumber,
            'nama_calon_siswa' => $name,
            'nisn' => $nisn,
            'tanggal_lahir' => $birthDate,
            'sekolah_asal' => sprintf('SMP Contoh %02d', $number),
            'alamat' => sprintf('Jalan Dummy %02d, %s, %s', $number, $region['kelurahan'], $region['kota']),
            'no_telepon' => $phone,
            'status_pendaftaran' => 'diterima',
            'diajukan_pada' => now(),
            'diperiksa_pada' => now(),
            'langkah_terakhir' => 4,
            'jenis_kelamin' => $gender,
            'nik' => $nik,
            'no_kk' => sprintf('999977777777%04d', $number),
            'tempat_lahir' => $region['kota'],
            'email' => $email,
            'id_agama' => $religionId,
            'jenjang_tujuan' => 'SMA',
            'kelas_tujuan' => 'X MIA 1',
            'tahun_pelajaran' => '2026/2027',
            'nis' => $nis,
            'persetujuan_ortu' => true,
            'pernyataan' => true,
            'id_kelas_diterima' => $class->id_kelas,
            'pendaftaran_untuk' => 'SMA',
            'hobi' => $definition[4],
            'cita_cita' => $definition[5],
            'anak_ke' => 1,
            'status_keluarga' => 'Anak kandung',
            'berkebutuhan_khusus' => '00',
            'tempat_tinggal' => $definition[6],
            'moda_transportasi' => $definition[7],
            'penerima_kps' => 'TIDAK',
            'kewarganegaraan' => 'WNI',
            'layak_pip' => 'TIDAK',
        ];

        $existing = $connection->table('pendaftaran_ppdb')->where('nomor_pendaftaran', $applicationNumber)->first();
        if (! $existing) {
            $existing = $connection->table('pendaftaran_ppdb')->where('nisn', $nisn)->first();
        }
        if ($existing) {
            if ($existing->nomor_pendaftaran !== $applicationNumber
                || $existing->nama_calon_siswa !== $name) {
                throw new RuntimeException("NISN {$nisn} sudah dipakai pendaftaran bukan dummy.");
            }
            $connection->table('pendaftaran_ppdb')
                ->where('id_pendaftaran', $existing->id_pendaftaran)
                ->update($data);

            return (int) $existing->id_pendaftaran;
        }

        return (int) $connection->table('pendaftaran_ppdb')->insertGetId($data, 'id_pendaftaran');
    }

    private function upsertPpdbRelations($connection, int $applicationId, int $number, array $region, array $jobIds): void
    {
        $address = [
            'jalan' => sprintf('Jalan Dummy %02d', $number),
            'rt' => sprintf('%03d', $number),
            'rw' => sprintf('%03d', $number),
            'kelurahan' => $region['kelurahan'],
            'kecamatan' => $region['kecamatan'],
            'kota' => $region['kota'],
            'provinsi' => $region['provinsi'],
            'dusun' => 'Lingkungan Dummy',
            'kode_pos' => $region['kode_pos'],
            'telp' => sprintf('06170000%04d', $number),
            'no_hp' => sprintf('0813000000%02d', $number),
        ];
        $connection->table('ppdb_alamat')->updateOrInsert(
            ['id_pendaftaran' => $applicationId],
            $address
        );

        $connection->table('ppdb_periodik')->updateOrInsert(
            ['id_pendaftaran' => $applicationId],
            [
                'tinggi_badan' => 145 + ($number % 26),
                'berat_badan' => 38 + ($number % 24),
                'gol_darah' => ['O', 'A', 'B', 'AB'][$number % 4],
                'jarak' => (string) (1 + ($number % 5)),
                'jarak_km' => 1 + ($number % 5),
                'waktu_tempuh_jam' => 0,
                'waktu_tempuh_menit' => 10 + ($number % 40),
                'jumlah_saudara' => 1 + ($number % 4),
            ]
        );

        $lastName = Str::of(self::STUDENTS[$number - 1][0])->explode(' ')->last();
        foreach (['ayah', 'ibu', 'wali'] as $offset => $relationship) {
            $parentData = [
                'nama_lengkap' => ucfirst($relationship).' Dummy '.$lastName,
                'nik' => sprintf('999988888888%04d', $number * 3 + $offset),
                'tempat_lahir' => $region['kota'],
                'tanggal_lahir' => sprintf('%04d-%02d-%02d', 1970 + ($number % 20), 1 + ($number % 12), 1 + ($number % 27)),
                'pendidikan' => ['04', '05', '09'][($number + $offset) % 3],
                'id_pekerjaan' => (int) $jobIds[($number + $offset) % count($jobIds)],
                'penghasilan' => (string) self::STUDENTS[$number - 1][8],
                'berkebutuhan_khusus' => '00',
                'no_telepon' => sprintf('0814000000%02d', $number * 3 + $offset),
                'alamat' => sprintf('Jalan Dummy %02d, %s', $number, $region['kota']),
            ];
            $existing = $connection->table('ppdb_orang_tua')
                ->where('id_pendaftaran', $applicationId)
                ->where('hubungan', $relationship)
                ->first();
            if ($existing) {
                $connection->table('ppdb_orang_tua')
                    ->where('id_orang_tua', $existing->id_orang_tua)
                    ->update($parentData);
            } else {
                $connection->table('ppdb_orang_tua')->insert([
                    'id_pendaftaran' => $applicationId,
                    'hubungan' => $relationship,
                ] + $parentData);
            }
        }
    }

    private function upsertEnrollment($connection, int $studentId, int $classId, int $semesterId): void
    {
        $rows = $connection->table('keanggotaan_kelas')
            ->where('id_siswa', $studentId)
            ->where('id_semester', $semesterId)
            ->orderBy('id_keanggotaan')
            ->get();
        if ($rows->count() > 1) {
            throw new RuntimeException("Siswa {$studentId} memiliki lebih dari satu keanggotaan target.");
        }
        $data = [
            'id_kelas' => $classId,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => null,
            'status' => 'aktif',
        ];
        if ($rows->isNotEmpty()) {
            $connection->table('keanggotaan_kelas')
                ->where('id_keanggotaan', $rows->first()->id_keanggotaan)
                ->update($data);
        } else {
            $connection->table('keanggotaan_kelas')->insert([
                'id_siswa' => $studentId,
                'id_semester' => $semesterId,
            ] + $data);
        }
    }

    private function activateConfirmationTrigger($connection): void
    {
        $periodId = $connection->table('periode_update')->where('status', 'aktif')->value('id_periode_update');
        if (! $periodId) {
            throw new RuntimeException('Tidak ada periode aktif untuk membuat konfirmasi siswa.');
        }

        $connection->table('periode_update')->where('id_periode_update', $periodId)->update(['status' => 'draft']);
        $connection->table('periode_update')->where('id_periode_update', $periodId)->update(['status' => 'aktif']);
    }

    private function verifyTargetsRemoved($connection): void
    {
        $matches = $this->findPersonMatches($connection);
        if ($matches['hits'] !== []) {
            throw new RuntimeException(
                'B1 gagal verifikasi; masih ditemukan kecocokan nama: '.json_encode($matches['hits'], JSON_UNESCAPED_UNICODE)
            );
        }
    }

    private function verifyDummyStudents($connection, string $password): void
    {
        $records = $connection->table('pengguna as p')
            ->join('role as r', 'r.id_role', '=', 'p.id_role')
            ->join('siswa as s', 's.id_pengguna', '=', 'p.id_pengguna')
            ->whereIn('p.username', $this->studentUsernames())
            ->orderBy('p.username')
            ->get(['p.username', 'p.password_hash', 'p.is_aktif', 'r.nama_role', 's.id_siswa', 's.nisn', 's.nik']);
        if ($records->count() !== count(self::STUDENTS)) {
            throw new RuntimeException('Verifikasi akun dan siswa dummy harus menghasilkan tepat 32 pasangan.');
        }

        foreach ($records as $record) {
            if (! $record->is_aktif || $record->nama_role !== 'siswa' || ! Hash::check($password, $record->password_hash)) {
                throw new RuntimeException("Akun siswa dummy {$record->username} gagal verifikasi role, status, atau hash.");
            }
            if (strlen((string) $record->nisn) !== 10 || strlen((string) $record->nik) !== 16) {
                throw new RuntimeException("Identitas dummy {$record->username} tidak sesuai panjang NISN/NIK.");
            }

            $documents = $connection->table('dokumen_siswa')
                ->where('id_siswa', $record->id_siswa)
                ->where('status_verifikasi', 'valid')
                ->get(['jenis_dokumen', 'path_file']);
            foreach (self::DOCUMENTS as $requiredDocument) {
                $found = $documents->first(fn ($document) => Str::lower(str_replace([' ', '/'], '', $document->jenis_dokumen))
                    === Str::lower(str_replace([' ', '/'], '', $requiredDocument)));
                if (! $found || ! Storage::disk('private')->exists($found->path_file)) {
                    throw new RuntimeException("Dokumen wajib {$requiredDocument} tidak lengkap untuk {$record->username}.");
                }
                $contents = Storage::disk('private')->get($found->path_file);
                if (! str_starts_with($contents, '%PDF-1.4') || ! str_contains($contents, 'DOKUMEN DUMMY - BUKAN DOKUMEN ASLI')) {
                    throw new RuntimeException("Berkas {$found->path_file} bukan PDF dummy valid yang diharapkan.");
                }
            }
        }

        $classSummary = $connection->table('vw_kelengkapan_data_kelas')
            ->where('id_kelas', $connection->table('kelas')
                ->where('nama_kelas', 'X MIA 1')
                ->where('id_tahun_ajaran', $connection->table('tahun_ajaran')
                    ->where('nama_tahun_ajaran', '2026/2027')->value('id_tahun_ajaran'))
                ->value('id_kelas'))
            ->first(['jumlah_siswa', 'siswa_terdata']);
        if (! $classSummary
            || (int) $classSummary->jumlah_siswa !== 32
            || (int) $classSummary->siswa_terdata !== 32) {
            throw new RuntimeException('vw_kelengkapan_data_kelas tidak menunjukkan tepat 32 siswa lengkap.');
        }

        $activeSemesterId = $connection->table('semester')->where('is_aktif', true)->value('id_semester');
        $activeClassId = $connection->table('kelas')
            ->where('nama_kelas', 'X MIA 1')
            ->where('id_tahun_ajaran', $connection->table('tahun_ajaran')
                ->where('nama_tahun_ajaran', '2026/2027')->value('id_tahun_ajaran'))
            ->value('id_kelas');
        $assignedTeacher = $connection->table('penugasan_wali_kelas as pw')
            ->join('guru as g', 'g.id_guru', '=', 'pw.id_guru')
            ->join('pengguna as p', 'p.id_pengguna', '=', 'g.id_pengguna')
            ->join('role as r', 'r.id_role', '=', 'p.id_role')
            ->where('pw.id_kelas', $activeClassId)
            ->where('pw.id_semester', $activeSemesterId)
            ->where('r.nama_role', 'wali_kelas')
            ->exists();
        if (! $assignedTeacher) {
            throw new RuntimeException('Kelas X MIA 1 belum memiliki wali kelas valid.');
        }
    }

    private function verifyLogTriggers($connection): void
    {
        $triggers = $connection->select(
            "SELECT c.relname AS table_name, t.tgname, t.tgenabled
             FROM pg_trigger t
             JOIN pg_class c ON c.oid = t.tgrelid
             WHERE NOT t.tgisinternal
               AND (c.relname, t.tgname) IN (
                   ('log_audit', 'trg_blokir_update_log_audit'),
                   ('log_login', 'trg_blokir_update_log_login')
               )"
        );
        if (count($triggers) !== 2) {
            throw new RuntimeException('Tidak ditemukan kedua trigger immutable log yang harus dipulihkan.');
        }
        foreach ($triggers as $trigger) {
            if ($trigger->tgenabled !== 'O') {
                throw new RuntimeException("Trigger immutable {$trigger->tgname} tidak kembali aktif.");
            }
        }
    }

    private function printExecutionSummary(Command $command, array $counts, array $before, array $after, int $filesRemoved): void
    {
        $command->info('B1, B2, dan B3 selesai dalam satu transaksi PostgreSQL; data uji tidak digunakan.');
        $command->line('Hasil B1: '.json_encode($counts['B1'], JSON_UNESCAPED_UNICODE));
        $command->line('Hasil B2: '.json_encode($counts['B2'], JSON_UNESCAPED_UNICODE));
        $command->line('Hasil B3: '.json_encode($counts['B3'], JSON_UNESCAPED_UNICODE));
        $command->line('Berkas privat target yang dihapus setelah commit: '.$filesRemoved);
        $deltas = [];
        foreach ($after as $table => $rowCount) {
            $delta = $rowCount - ($before[$table] ?? 0);
            if ($delta !== 0) {
                $deltas[] = [$table, $before[$table] ?? 0, $rowCount, $delta];
            }
        }
        $command->table(['Tabel', 'Sebelum', 'Sesudah', 'Selisih'], $deltas);
    }

    private function tableCounts($connection): array
    {
        $tables = $connection->select(
            "SELECT tablename
             FROM pg_tables
             WHERE schemaname = 'public'
             ORDER BY tablename"
        );
        $counts = [];
        foreach ($tables as $table) {
            $name = $this->quoteIdentifier($table->tablename);
            $counts[$table->tablename] = (int) $connection->selectOne(
                "SELECT count(*)::int AS row_count FROM public.{$name}"
            )->row_count;
        }

        return $counts;
    }

    private function assertNoDemoAccounts(array $plan): void
    {
        foreach ($plan['pengguna'] ?? [] as $entry) {
            $username = (string) ($entry['row']['username'] ?? '');
            if (str_starts_with($username, 'demo_')) {
                throw new RuntimeException("B1 dibatalkan karena rencana menyentuh akun demo: {$username}");
            }
        }
    }

    private function targetStudentIds(array $matches): array
    {
        $ids = [];
        foreach ($matches['rows']['siswa'] ?? [] as $entry) {
            if (isset($entry['row']['id_siswa'])) {
                $ids[] = (int) $entry['row']['id_siswa'];
            }
        }

        return array_values(array_unique($ids));
    }

    private function targetTeacherIds(array $matches): array
    {
        $ids = [];
        foreach ($matches['rows']['guru'] ?? [] as $entry) {
            if (isset($entry['row']['id_guru'])) {
                $ids[] = (int) $entry['row']['id_guru'];
            }
        }

        return array_values(array_unique($ids));
    }

    private function targetTeacherScheduleCount($connection, array $matches): int
    {
        $teacherIds = $this->targetTeacherIds($matches);
        if ($teacherIds === []) {
            return 0;
        }

        return $connection->table('jadwal_pelajaran')->whereIn('id_guru', $teacherIds)->count();
    }

    private function targetUserIds(array $plan): array
    {
        $ids = [];
        foreach ($plan['pengguna'] ?? [] as $entry) {
            if (isset($entry['row']['id_pengguna'])) {
                $ids[] = (int) $entry['row']['id_pengguna'];
            }
        }

        return array_values(array_unique($ids));
    }

    private function targetUsernames(array $plan): array
    {
        $usernames = [];
        foreach ($plan['pengguna'] ?? [] as $entry) {
            if (! empty($entry['row']['username'])) {
                $usernames[] = (string) $entry['row']['username'];
            }
        }

        return array_values(array_unique($usernames));
    }

    private function targetFiles(array $plan): array
    {
        $paths = [];
        foreach ($plan as $rows) {
            foreach ($rows as $entry) {
                foreach (['path_file', 'path_lampiran', 'bukti_path'] as $column) {
                    $path = $entry['row'][$column] ?? null;
                    if (! is_string($path) || $path === '') {
                        continue;
                    }
                    if (str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
                        throw new RuntimeException('Path berkas target berada di luar disk privat yang diizinkan.');
                    }
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function assertPrivateFilesAreNotShared($connection, array $paths, array $plan): void
    {
        if ($paths === []) {
            return;
        }

        $deletedPathRows = [];
        foreach ($plan as $table => $rows) {
            foreach ($rows as $entry) {
                foreach (['path_file', 'path_lampiran', 'bukti_path'] as $column) {
                    if (isset($entry['row'][$column])) {
                        $deletedPathRows[$table.'|'.$this->rowKey($table, $entry['row'], $entry['ctid'])] = true;
                    }
                }
            }
        }

        $pathColumns = $connection->select(
            "SELECT c.relname AS table_name, a.attname AS column_name
             FROM pg_class c
             JOIN pg_namespace n ON n.oid = c.relnamespace
             JOIN pg_attribute a ON a.attrelid = c.oid
             JOIN information_schema.columns ic
               ON ic.table_schema = n.nspname
              AND ic.table_name = c.relname
              AND ic.column_name = a.attname
             WHERE n.nspname = 'public'
               AND c.relkind IN ('r', 'p')
               AND a.attnum > 0
               AND NOT a.attisdropped
               AND a.attname ~* '(path|file|bukti|lampiran)'
               AND ic.data_type IN ('text', 'character varying', 'character')"
        );
        foreach ($paths as $path) {
            foreach ($pathColumns as $column) {
                $table = $this->quoteIdentifier($column->table_name);
                $field = $this->quoteIdentifier($column->column_name);
                $rows = $connection->select(
                    "SELECT ctid::text AS row_ctid, to_jsonb(t)::text AS row_json
                     FROM public.{$table} t WHERE t.{$field} = ?",
                    [$path]
                );
                foreach ($rows as $row) {
                    $data = json_decode($row->row_json, true, flags: JSON_THROW_ON_ERROR);
                    $key = $column->table_name.'|'.$this->rowKey($column->table_name, $data, $row->row_ctid);
                    if (! isset($deletedPathRows[$key])) {
                        throw new RuntimeException("Path target {$path} juga dirujuk data di luar B1.");
                    }
                }
            }
        }
    }

    private function findPersonMatchesByName($connection): array
    {
        return $this->findPersonMatches($connection);
    }

    private function rowHasNormalizedValue(array $row, string $normalized): bool
    {
        foreach ($row as $value) {
            if (is_string($value) && preg_replace('/[^[:alnum:]]/i', '', mb_strtolower($value)) === $normalized) {
                return true;
            }
        }

        return false;
    }

    private function rowsShareIdentity(array $rows): bool
    {
        foreach (['nisn', 'nik', 'email', 'username', 'id_pengguna'] as $field) {
            $values = array_map(fn ($row) => trim((string) ($row[$field] ?? '')), $rows);
            if ($values[0] !== '' && count(array_unique($values)) === 1) {
                return true;
            }
        }

        return false;
    }

    private function uniqueRows(array $rows): array
    {
        $unique = [];
        foreach ($rows as $row) {
            $unique[json_encode($row, JSON_THROW_ON_ERROR)] = $row;
        }

        return array_values($unique);
    }

    private function addPlanRow(
        array &$plan,
        string $table,
        array $row,
        string $ctid,
        int $depth,
        ?array &$queue = null
    ): void {
        $key = $this->rowKey($table, $row, $ctid);
        if (isset($plan[$table][$key])) {
            return;
        }
        $plan[$table][$key] = ['row' => $row, 'ctid' => $ctid, 'depth' => $depth];
        if ($queue !== null) {
            $queue[] = [$table, $row];
        }
    }

    private function rowKey(string $table, array $row, string $ctid): string
    {
        $columns = $this->primaryKeyColumns($table);
        if ($columns === []) {
            return 'ctid:'.$ctid;
        }

        $values = [];
        foreach ($columns as $column) {
            $values[$column] = $row[$column] ?? null;
        }

        return json_encode($values, JSON_THROW_ON_ERROR);
    }

    private function primaryKeyColumns(string $table): array
    {
        if (isset($this->primaryKeys[$table])) {
            return $this->primaryKeys[$table];
        }

        $columns = DB::connection('pgsql')->select(
            "SELECT array_to_json(array_agg(a.attname ORDER BY k.ordinality))::text AS columns
             FROM pg_constraint c
             JOIN LATERAL unnest(c.conkey) WITH ORDINALITY AS k(attnum, ordinality) ON true
             JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = k.attnum
             JOIN pg_class t ON t.oid = c.conrelid
             JOIN pg_namespace n ON n.oid = t.relnamespace
             WHERE c.contype = 'p' AND n.nspname = 'public' AND t.relname = ?",
            [$table]
        );
        $json = $columns[0]->columns ?? null;
        $this->primaryKeys[$table] = $json ? json_decode($json, true, flags: JSON_THROW_ON_ERROR) : [];

        return $this->primaryKeys[$table];
    }

    private function foreignKeys($connection): array
    {
        if ($this->foreignKeys !== []) {
            return $this->foreignKeys;
        }

        $rows = $connection->select(
            "SELECT parent.relname AS parent_table, child.relname AS child_table, c.confdeltype,
                    array_to_json(array_agg(child_col.attname ORDER BY ck.ordinality))::text AS child_columns,
                    array_to_json(array_agg(parent_col.attname ORDER BY pk.ordinality))::text AS parent_columns
             FROM pg_constraint c
             JOIN pg_class child ON child.oid = c.conrelid
             JOIN pg_namespace child_ns ON child_ns.oid = child.relnamespace
             JOIN pg_class parent ON parent.oid = c.confrelid
             JOIN pg_namespace parent_ns ON parent_ns.oid = parent.relnamespace
             JOIN LATERAL unnest(c.conkey) WITH ORDINALITY AS ck(attnum, ordinality) ON true
             JOIN pg_attribute child_col ON child_col.attrelid = child.oid AND child_col.attnum = ck.attnum
             JOIN LATERAL unnest(c.confkey) WITH ORDINALITY AS pk(attnum, ordinality) ON pk.ordinality = ck.ordinality
             JOIN pg_attribute parent_col ON parent_col.attrelid = parent.oid AND parent_col.attnum = pk.attnum
             WHERE c.contype = 'f' AND child_ns.nspname = 'public' AND parent_ns.nspname = 'public'
             GROUP BY parent.relname, child.relname, c.oid, c.confdeltype
             ORDER BY parent.relname, child.relname"
        );

        foreach ($rows as $row) {
            $this->foreignKeys[$row->parent_table][] = [
                'child' => $row->child_table,
                'delete_action' => $row->confdeltype,
                'child_columns' => json_decode($row->child_columns, true, flags: JSON_THROW_ON_ERROR),
                'parent_columns' => json_decode($row->parent_columns, true, flags: JSON_THROW_ON_ERROR),
            ];
        }

        return $this->foreignKeys;
    }

    private function findReferencingRows($connection, string $table, array $columnValues): array
    {
        $tableName = $this->quoteIdentifier($table);
        $conditions = [];
        $bindings = [];
        foreach ($columnValues as $column => $value) {
            $conditions[] = 't.'.$this->quoteIdentifier($column).' = ?';
            $bindings[] = $value;
        }
        $rows = $connection->select(
            'SELECT t.ctid::text AS row_ctid, to_jsonb(t)::text AS row_json FROM public.'
                .$tableName.' t WHERE '.implode(' AND ', $conditions),
            $bindings
        );

        return array_map(fn ($row) => [
            'row' => json_decode($row->row_json, true, flags: JSON_THROW_ON_ERROR),
            'ctid' => $row->row_ctid,
        ], $rows);
    }

    private function findRowsByValues($connection, string $table, string $column, array $values): array
    {
        $tableName = $this->quoteIdentifier($table);
        $field = $this->quoteIdentifier($column);
        $rows = $connection->select(
            "SELECT t.ctid::text AS row_ctid, to_jsonb(t)::text AS row_json
             FROM public.{$tableName} t
             WHERE t.{$field} IN (".implode(',', array_fill(0, count($values), '?')).')',
            $values
        );

        return array_map(fn ($row) => [
            'row' => json_decode($row->row_json, true, flags: JSON_THROW_ON_ERROR),
            'ctid' => $row->row_ctid,
        ], $rows);
    }

    private function findRowsLike($connection, string $table, string $column, string $term): array
    {
        $tableName = $this->quoteIdentifier($table);
        $field = $this->quoteIdentifier($column);
        $rows = $connection->select(
            "SELECT t.ctid::text AS row_ctid, to_jsonb(t)::text AS row_json
             FROM public.{$tableName} t
             WHERE t.{$field}::text ILIKE ?",
            ['%'.$term.'%']
        );

        return array_map(fn ($row) => [
            'row' => json_decode($row->row_json, true, flags: JSON_THROW_ON_ERROR),
            'ctid' => $row->row_ctid,
        ], $rows);
    }

    private function deletePlanRow($connection, string $table, array $row, string $ctid): int
    {
        $query = $connection->table($table);
        $primaryKeys = $this->primaryKeyColumns($table);
        if ($primaryKeys === []) {
            return $query->whereRaw('ctid::text = ?', [$ctid])->delete();
        }
        foreach ($primaryKeys as $column) {
            $query->where($column, $row[$column] ?? null);
        }

        return $query->delete();
    }

    private function studentUsernames(): array
    {
        return array_map(fn ($number) => sprintf('siswa_dummy_2026_%02d', $number), range(1, count(self::STUDENTS)));
    }

    private function placeholderPdf(string $document, string $name, string $nisn): string
    {
        $lines = [
            'DOKUMEN DUMMY - BUKAN DOKUMEN ASLI',
            'Jenis dokumen: '.$document,
            'Nama: '.$name,
            'NISN dummy: '.$nisn,
        ];
        $commands = [];
        foreach ($lines as $index => $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $commands[] = ($index === 0 ? '50 760 Td' : '0 -32 Td').' ('.$safe.') Tj';
        }
        $stream = "BT\n/F1 14 Tf\n".implode("\n", $commands)."\nET\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";

        return $pdf;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new RuntimeException('Identifier PostgreSQL tidak valid.');
        }

        return '"'.$identifier.'"';
    }

    private function rowCtid($connection, string $table, string $column, mixed $value): string
    {
        $tableName = $this->quoteIdentifier($table);
        $field = $this->quoteIdentifier($column);

        return (string) $connection->selectOne(
            "SELECT ctid::text AS row_ctid FROM public.{$tableName} WHERE {$field} = ?",
            [$value]
        )->row_ctid;
    }
}
