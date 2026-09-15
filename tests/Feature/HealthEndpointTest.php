<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok_status_with_database_connectivity(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJson([
            'status' => 'ok',
            'database' => 'ok',
        ]);
        $response->assertJsonStructure(['status', 'timestamp', 'database']);
    }
}
