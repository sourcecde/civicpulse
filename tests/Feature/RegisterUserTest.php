<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Citizen',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ], $overrides);
    }

    public function test_citizen_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertCreated();
        $response->assertJsonPath('data.email', 'jane@example.com');
        $response->assertJsonPath('data.role', UserRole::CITIZEN->value);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => UserRole::CITIZEN->value,
        ]);
    }

    public function test_registration_always_assigns_citizen_role_even_if_role_field_is_submitted(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload([
            'role' => UserRole::ADMIN->value,
        ]));

        $response->assertCreated();
        $response->assertJsonPath('data.role', UserRole::CITIZEN->value);

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertSame(UserRole::CITIZEN, $user->role);
    }

    public function test_registration_requires_name(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload(['name' => '']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload(['email' => 'not-an-email']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload([
            'password_confirmation' => 'does-not-match',
        ]));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_registration_password_is_hashed(): void
    {
        $this->postJson('/api/register', $this->validPayload())->assertCreated();

        $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertNotSame('Str0ng-Passw0rd!', $user->password);
        $this->assertTrue(Hash::check('Str0ng-Passw0rd!', $user->password));
    }

    public function test_registration_response_does_not_expose_password(): void
    {
        $response = $this->postJson('/api/register', $this->validPayload());

        $response->assertCreated();
        $response->assertJsonMissingPath('data.password');
    }
}
