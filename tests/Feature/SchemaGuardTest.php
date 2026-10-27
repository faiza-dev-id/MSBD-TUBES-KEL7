<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaGuardTest extends TestCase
{
    public function test_expected_current_schema_tables_are_present_in_test_database(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped(
                'Pemeriksaan skema akademik khusus PostgreSQL.'
            );
        }

        $this->assertTrue(Schema::hasTable('pengguna'));
        $this->assertTrue(Schema::hasTable('siswa'));
        $this->assertTrue(Schema::hasTable('pendaftaran_ppdb'));
    }
}
