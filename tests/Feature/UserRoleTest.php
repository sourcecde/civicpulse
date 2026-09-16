<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_defaults_to_citizen_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::CITIZEN, $user->fresh()->role);
    }

    public function test_user_can_be_created_with_operator_role(): void
    {
        $user = User::factory()->operator()->create();

        $this->assertSame(UserRole::OPERATOR, $user->fresh()->role);
    }

    public function test_user_can_be_created_with_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame(UserRole::ADMIN, $user->fresh()->role);
    }

    public function test_persisted_role_is_cast_to_user_role_enum(): void
    {
        $user = User::factory()->operator()->create();

        $persisted = User::query()->findOrFail($user->id);

        $this->assertInstanceOf(UserRole::class, $persisted->role);
        $this->assertSame(UserRole::OPERATOR, $persisted->role);
    }
}
