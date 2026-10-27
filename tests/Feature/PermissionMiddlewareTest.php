<?php

namespace Tests\Feature;

use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    public function test_protected_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/ppdb')
            ->assertUnauthorized();
    }
}
