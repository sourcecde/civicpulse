<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_openapi_document_is_accessible_and_lists_existing_endpoints(): void
    {
        // Scramble's docs middleware only allows unrestricted access in the
        // `local` environment; the test suite otherwise runs as `testing`.
        $this->app['env'] = 'local';

        $response = $this->getJson('/docs/api.json');

        $response->assertOk();

        // Scramble strips the shared `/api` prefix into the server URL rather
        // than repeating it in each path key, so `servers[0].url` + a path
        // key together reconstruct the full route (e.g. `/api` + `/health`).
        $this->assertStringEndsWith('/api', $response->json('servers.0.url'));

        $paths = array_keys($response->json('paths'));

        $this->assertContains('/health', $paths);
        $this->assertContains('/register', $paths);
        $this->assertContains('/login', $paths);
        $this->assertContains('/me', $paths);
        $this->assertContains('/logout', $paths);

        // Sanctum-protected routes are documented as requiring a bearer token;
        // public routes explicitly opt out of security.
        $this->assertSame('bearer', $response->json('components.securitySchemes.http.scheme'));
        $this->assertSame([], $response->json('paths./login.post.security'));
        $this->assertNull($response->json('paths./me.get.security'));
    }
}
