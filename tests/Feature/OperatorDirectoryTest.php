<?php

    namespace Tests\Feature;

    use App\Enums\UserRole;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;
    use Illuminate\Http\Request;

    class OperatorDirectoryTest extends TestCase
    {
        use RefreshDatabase;

        public function test_unauthenticated_user_cannot_access_operator_directory(): void
        {
            $this->getJson('/api/operators')
                ->assertUnauthorized();
        }

        public function test_citizen_cannot_access_operator_directory(): void
        {
            $citizen = User::factory()->create([
                'role' => UserRole::CITIZEN,
            ]);

            $this->actingAs($citizen, 'sanctum')
                ->getJson('/api/operators')
                ->assertForbidden();
        }

        public function test_operator_can_access_operator_directory(): void
        {
            $operator = User::factory()->create([
                'role' => UserRole::OPERATOR,
            ]);

            $this->actingAs($operator, 'sanctum')
                ->getJson('/api/operators')
                ->assertOk();
        }

        public function test_admin_can_access_operator_directory(): void
        {
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
            ]);

            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/operators')
                ->assertOk();
        }

        public function test_directory_returns_only_operators(): void
        {
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
            ]);

            $operator = User::factory()->create([
                'role' => UserRole::OPERATOR,
                'name' => 'Alice',
            ]);

            $citizen = User::factory()->create([
                'role' => UserRole::CITIZEN,
            ]);

            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/operators')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $operator->id)
                ->assertJsonPath('data.0.name', 'Alice')
                ->assertJsonMissing(['id' => $admin->id])
                ->assertJsonMissing(['id' => $citizen->id]);
        }

        public function test_directory_includes_operators_without_assigned_reports(): void
        {
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
            ]);

            $operator = User::factory()->create([
                'role' => UserRole::OPERATOR,
                'name' => 'New Operator',
            ]);

            // No reports or assignments are created for this operator.
            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/operators')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $operator->id);
        }

        public function test_directory_returns_operators_in_name_order(): void
        {
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
            ]);

            User::factory()->create([
                'role' => UserRole::OPERATOR,
                'name' => 'Charlie',
            ]);

            User::factory()->create([
                'role' => UserRole::OPERATOR,
                'name' => 'Alice',
            ]);

            User::factory()->create([
                'role' => UserRole::OPERATOR,
                'name' => 'Bob',
            ]);

            $response = $this->actingAs($admin, 'sanctum')
                ->getJson('/api/operators');

            $response->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonPath('data.0.name', 'Alice')
                ->assertJsonPath('data.1.name', 'Bob')
                ->assertJsonPath('data.2.name', 'Charlie');
        }

        public function test_operator_response_contains_only_required_fields(): void
        {
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
            ]);

            User::factory()->create([
                'role' => UserRole::OPERATOR,
            ]);

            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/operators')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name'],
                    ],
                ]);
        }

        public function test_directory_returns_empty_array_when_no_operators_exist(): void
        {
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
            ]);

            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/operators')
                ->assertOk()
                ->assertJsonPath('data', []);
        }
    }
