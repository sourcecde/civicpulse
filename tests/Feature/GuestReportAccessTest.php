<?php

    namespace Tests\Feature;

    use App\Models\Category;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;

    class GuestReportAccessTest extends TestCase
    {
        use RefreshDatabase;

        /**
         * Create a guest report and return its access credentials.
         */
        private function createGuestReport(): array
        {
            $category = Category::factory()->create();

            $response = $this->postJson('/api/reports', [
                'category_id' => $category->id,
                'description' => 'Test report',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'reporter_email' => 'test@example.com',
            ]);

            $response->assertSuccessful();

            return [
                'tracking_number' => $response->json('data.tracking_number'),
                'access_token' => $response->json('guest_access_token'),
            ];
        }

        public function test_guest_can_access_report_with_valid_token(): void
        {
            $report = $this->createGuestReport();

            $response = $this->postJson(
                "/api/guest/reports/{$report['tracking_number']}/lookup",
                [
                    'access_token' => $report['access_token'],
                ]
            );

            $response->assertOk()
                ->assertJsonPath(
                    'data.tracking_number',
                    $report['tracking_number']
                );
        }

        public function test_guest_cannot_access_report_with_invalid_token(): void
        {
            $report = $this->createGuestReport();

            $response = $this->postJson(
                "/api/guest/reports/{$report['tracking_number']}/lookup",
                [
                    'access_token' => 'invalidtoken',
                ]
            );

            $response->assertStatus(403)
                ->assertJsonPath(
                    'message',
                    'Invalid tracking number or access token.'
                );
        }

        public function test_guest_cannot_access_report_with_invalid_tracking_number(): void
        {
            $response = $this->postJson(
                '/api/guest/reports/CP-NONEXISTENT/lookup',
                [
                    'access_token' => bin2hex(random_bytes(32)),
                ]
            );

            $response->assertStatus(403)
                ->assertJsonPath(
                    'message',
                    'Invalid tracking number or access token.'
                );
        }

        public function test_guest_cannot_access_report_without_reporter_email(): void
        {
            $category = Category::factory()->create();

            $response = $this->postJson('/api/reports', [
                'category_id' => $category->id,
                'description' => 'Test report',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['reporter_email']);
        }

        public function test_guest_cannot_access_report_without_location(): void
        {
            $category = Category::factory()->create();

            $response = $this->postJson('/api/reports', [
                'category_id' => $category->id,
                'description' => 'Test report',
                'reporter_email' => 'test@example.com',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'latitude',
                    'longitude',
                ]);
        }

        public function test_guest_cannot_access_report_without_category_id(): void
        {
            $response = $this->postJson('/api/reports', [
                'description' => 'Test report',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'reporter_email' => 'test@example.com',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['category_id']);
        }
    }
