<?php
namespace Tests\Feature; use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase; use App\Models\Pengguna;
class PermissionMiddlewareTest extends TestCase { use RefreshDatabase; public function test_protected_endpoint_requires_authentication():void { $this->getJson('/api/ppdb')->assertUnauthorized(); } }
