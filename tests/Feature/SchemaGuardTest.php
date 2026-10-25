<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Support\Facades\Schema;
class SchemaGuardTest extends TestCase { public function test_expected_current_schema_tables_are_present_in_test_database():void { $this->assertTrue(Schema::hasTable('pengguna')); $this->assertTrue(Schema::hasTable('siswa')); $this->assertTrue(Schema::hasTable('pendaftaran_ppdb')); } }
