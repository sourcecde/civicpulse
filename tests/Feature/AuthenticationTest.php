<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passw0rd!',
        ], $attributes));
    }

    private function loginPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passw0rd!',
        ], $overrides);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/login', $this->loginPayload());

        $response->assertOk();
        $response->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email', 'role']]);
        $response->assertJsonPath('token_type', 'Bearer');
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.role', UserRole::CITIZEN->value);
        $response->assertJsonMissingPath('user.password');

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_uses_device_name_as_token_name_when_given(): void
    {
        $user = $this->createUser();

        $this->postJson('/api/login', $this->loginPayload(['device_name' => 'pixel-8']))
            ->assertOk();

        $this->assertSame('pixel-8', $user->tokens()->first()->name);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/login', $this->loginPayload(['password' => 'wrong-password']));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
        $response->assertJsonMissingPath('token');
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_login_fails_for_unknown_email_with_same_error_as_wrong_password(): void
    {
        $this->createUser();

        $unknown = $this->postJson('/api/login', $this->loginPayload(['email' => 'nobody@example.com']));
        $wrongPassword = $this->postJson('/api/login', $this->loginPayload(['password' => 'wrong-password']));

        $unknown->assertUnprocessable();
        $this->assertSame($wrongPassword->json('errors'), $unknown->json('errors'));
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited(): void
    {
        $this->createUser();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $this->loginPayload(['password' => 'wrong-password']))
                ->assertUnprocessable();
        }

        $this->postJson('/api/login', $this->loginPayload())
            ->assertTooManyRequests();
    }

    public function test_issued_token_authenticates_me_endpoint(): void
    {
        $user = $this->createUser();
        $token = $this->postJson('/api/login', $this->loginPayload())->json('token');

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.email', 'jane@example.com');
        $response->assertJsonPath('data.role', UserRole::CITIZEN->value);
        $response->assertJsonMissingPath('data.password');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_rejects_invalid_token(): void
    {
        $this->withToken('not-a-real-token')->getJson('/api/me')->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = $this->createUser();
        $token = $this->postJson('/api/login', $this->loginPayload())->json('token');
        $otherDeviceToken = $user->createToken('other-device')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertNotNull(PersonalAccessToken::findToken($otherDeviceToken));
    }

    public function test_revoked_token_can_no_longer_access_me(): void
    {
        $this->createUser();
        $token = $this->postJson('/api/login', $this->loginPayload())->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        // Guards cache the resolved user within a single application
        // instance; reset them so the next request re-validates the token.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
