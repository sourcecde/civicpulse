<?php

    namespace Tests\Feature;

    use App\Enums\ReportStatus;
    use App\Models\Category;
    use App\Models\Report;
    use App\Models\User;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\DB;
    use Laravel\Sanctum\Sanctum;
    use Tests\TestCase;

    class OperatorDashboardApiTest extends TestCase
    {
        use RefreshDatabase;

        // ---- GET /api/dashboard/summary ----

        public function test_guest_cannot_view_dashboard_summary(): void
        {
            $this->getJson('/api/dashboard/summary')->assertUnauthorized();
        }

        public function test_citizen_cannot_view_dashboard_summary(): void
        {
            Sanctum::actingAs(User::factory()->citizen()->create());

            $this->getJson('/api/dashboard/summary')->assertForbidden();
        }

        public function test_operator_sees_total_and_counts_per_status(): void
        {
            $operator = User::factory()->operator()->create();

            Report::factory()->count(3)->open()->create();
            Report::factory()->count(2)->assignedTo($operator)->create();
            Report::factory()->count(4)->inProgressWith($operator)->create();
            Report::factory()->count(1)->resolvedWith($operator)->create();

            Sanctum::actingAs($operator);

            $this->getJson('/api/dashboard/summary')
                ->assertOk()
                ->assertExactJson([
                    'data' => [
                        'total' => 10,
                        'by_status' => [
                            'OPEN' => 3,
                            'ASSIGNED' => 2,
                            'IN_PROGRESS' => 4,
                            'RESOLVED' => 1,
                        ],
                    ],
                ]);
        }

        public function test_admin_can_view_dashboard_summary(): void
        {
            Report::factory()->count(2)->open()->create();

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->getJson('/api/dashboard/summary')
                ->assertOk()
                ->assertJsonPath('data.total', 2)
                ->assertJsonPath('data.by_status.OPEN', 2);
        }

        public function test_summary_reports_zero_for_every_status_when_empty(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/dashboard/summary')
                ->assertOk()
                ->assertExactJson([
                    'data' => [
                        'total' => 0,
                        'by_status' => [
                            'OPEN' => 0,
                            'ASSIGNED' => 0,
                            'IN_PROGRESS' => 0,
                            'RESOLVED' => 0,
                        ],
                    ],
                ]);
        }

        public function test_summary_uses_a_single_aggregate_query(): void
        {
            $operator = User::factory()->operator()->create();
            Report::factory()->count(5)->open()->create();
            Report::factory()->count(5)->assignedTo($operator)->create();

            Sanctum::actingAs($operator);

            DB::enableQueryLog();
            $this->getJson('/api/dashboard/summary')->assertOk();
            $reportQueries = collect(DB::getQueryLog())
                ->filter(fn ($entry) => str_contains($entry['query'], '"reports"'));

            $this->assertCount(1, $reportQueries);
        }

        // ---- GET /api/reports: authentication & authorization ----

        public function test_guest_cannot_list_reports(): void
        {
            $this->getJson('/api/reports')->assertUnauthorized();
        }

        public function test_citizen_cannot_list_reports(): void
        {
            Report::factory()->create();

            Sanctum::actingAs(User::factory()->citizen()->create());

            $this->getJson('/api/reports')->assertForbidden();
        }

        public function test_citizen_is_forbidden_before_filters_are_validated(): void
        {
            Sanctum::actingAs(User::factory()->citizen()->create());

            $this->getJson('/api/reports?status=NOT_A_STATUS')->assertForbidden();
        }

        public function test_admin_can_list_reports(): void
        {
            Report::factory()->count(2)->create();

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->getJson('/api/reports')
                ->assertOk()
                ->assertJsonCount(2, 'data');
        }

        // ---- GET /api/reports: pagination ----

        public function test_reports_are_paginated_with_default_page_size(): void
        {
            Report::factory()->count(20)->create();

            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports')
                ->assertOk()
                ->assertJsonCount(15, 'data')
                ->assertJsonPath('meta.total', 20)
                ->assertJsonPath('meta.per_page', 15)
                ->assertJsonPath('meta.current_page', 1)
                ->assertJsonPath('meta.last_page', 2)
                ->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta']);
        }

        public function test_reports_respect_per_page_and_page(): void
        {
            Report::factory()->count(7)->create();

            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?per_page=3&page=3')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('meta.current_page', 3)
                ->assertJsonPath('meta.last_page', 3);
        }

        public function test_pages_do_not_overlap(): void
        {
            Report::factory()->count(6)->create(['created_at' => now()]);

            Sanctum::actingAs(User::factory()->operator()->create());

            $first = $this->getJson('/api/reports?per_page=3&page=1')->json('data.*.id');
            $second = $this->getJson('/api/reports?per_page=3&page=2')->json('data.*.id');

            $this->assertCount(6, array_unique(array_merge($first, $second)));
        }

        public function test_per_page_is_capped(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?per_page=101')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('per_page');

            $this->getJson('/api/reports?per_page=0')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('per_page');
        }

        public function test_invalid_page_is_rejected(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?page=0')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('page');
        }

        public function test_pagination_links_keep_filters(): void
        {
            Report::factory()->count(3)->open()->create();

            Sanctum::actingAs(User::factory()->operator()->create());

            $next = $this->getJson('/api/reports?status=OPEN&per_page=1')
                ->assertOk()
                ->json('links.next');

            $this->assertStringContainsString('status=OPEN', $next);
            $this->assertStringContainsString('per_page=1', $next);
        }

        // ---- GET /api/reports: filters ----

        public function test_reports_can_be_filtered_by_status(): void
        {
            $operator = User::factory()->operator()->create();
            Report::factory()->count(2)->open()->create();
            $inProgress = Report::factory()->inProgressWith($operator)->create();

            Sanctum::actingAs($operator);

            $this->getJson('/api/reports?status=IN_PROGRESS')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $inProgress->id)
                ->assertJsonPath('data.0.status', ReportStatus::IN_PROGRESS->value);
        }

        public function test_invalid_status_filter_is_rejected(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?status=CLOSED')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('status');
        }

        public function test_reports_can_be_filtered_by_category(): void
        {
            $roads = Category::factory()->create();
            $lights = Category::factory()->create();
            Report::factory()->count(2)->create(['category_id' => $roads->id]);
            Report::factory()->count(3)->create(['category_id' => $lights->id]);

            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson("/api/reports?category_id={$lights->id}")
                ->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonPath('data.0.category_id', $lights->id);
        }

        public function test_invalid_category_filter_is_rejected(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?category_id=abc')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('category_id');
        }

        public function test_assigned_to_me_returns_only_the_operators_reports(): void
        {
            $me = User::factory()->operator()->create();
            $colleague = User::factory()->operator()->create();

            $mine = Report::factory()->assignedTo($me)->create();
            Report::factory()->assignedTo($colleague)->create();
            Report::factory()->open()->create();

            Sanctum::actingAs($me);

            $this->getJson('/api/reports?assigned_to=me')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $mine->id)
                ->assertJsonPath('data.0.assigned_operator_id', $me->id);
        }

        public function test_reports_can_be_filtered_by_assigned_operator_id(): void
        {
            $me = User::factory()->operator()->create();
            $colleague = User::factory()->operator()->create();

            Report::factory()->assignedTo($me)->create();
            Report::factory()->count(2)->inProgressWith($colleague)->create();

            Sanctum::actingAs($me);

            $this->getJson("/api/reports?assigned_to={$colleague->id}")
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonPath('data.0.assigned_operator_id', $colleague->id);
        }

        public function test_admin_assigned_to_me_returns_nothing_when_nothing_is_assigned_to_them(): void
        {
            $operator = User::factory()->operator()->create();
            Report::factory()->assignedTo($operator)->create();

            Sanctum::actingAs(User::factory()->admin()->create());

            $this->getJson('/api/reports?assigned_to=me')
                ->assertOk()
                ->assertJsonCount(0, 'data');
        }

        public function test_invalid_assigned_to_is_rejected(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            foreach (['someone', '-1', '0', '1;DROP'] as $value) {
                $this->getJson('/api/reports?assigned_to=' . urlencode($value))
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors('assigned_to');
            }
        }

        public function test_reports_can_be_filtered_by_inclusive_date_range(): void
        {
            Report::factory()->create(['created_at' => Carbon::parse('2026-09-30 23:59:59')]);
            $start = Report::factory()->create(['created_at' => Carbon::parse('2026-10-01 00:00:00')]);
            $end = Report::factory()->create(['created_at' => Carbon::parse('2026-10-05 23:59:59')]);
            Report::factory()->create(['created_at' => Carbon::parse('2026-10-06 00:00:00')]);

            Sanctum::actingAs(User::factory()->operator()->create());

            $ids = $this->getJson('/api/reports?created_from=2026-10-01&created_to=2026-10-05')
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->json('data.*.id');

            $this->assertEqualsCanonicalizing([$start->id, $end->id], $ids);
        }

        public function test_reports_can_be_filtered_by_open_ended_date_range(): void
        {
            Report::factory()->create(['created_at' => Carbon::parse('2026-09-01 10:00:00')]);
            Report::factory()->create(['created_at' => Carbon::parse('2026-10-01 10:00:00')]);

            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?created_from=2026-09-15')
                ->assertOk()
                ->assertJsonCount(1, 'data');

            $this->getJson('/api/reports?created_to=2026-09-15')
                ->assertOk()
                ->assertJsonCount(1, 'data');
        }

        public function test_invalid_date_filters_are_rejected(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?created_from=01-10-2026')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('created_from');

            $this->getJson('/api/reports?created_from=2026-10-05&created_to=2026-10-01')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('created_to');
        }

        public function test_filters_can_be_combined(): void
        {
            $me = User::factory()->operator()->create();
            $roads = Category::factory()->create();
            $lights = Category::factory()->create();

            $match = Report::factory()->inProgressWith($me)->create(['category_id' => $roads->id]);
            Report::factory()->assignedTo($me)->create(['category_id' => $roads->id]);
            Report::factory()->inProgressWith($me)->create(['category_id' => $lights->id]);
            Report::factory()->inProgressWith(User::factory()->operator()->create())
                ->create(['category_id' => $roads->id]);

            Sanctum::actingAs($me);

            $this->getJson("/api/reports?assigned_to=me&status=IN_PROGRESS&category_id={$roads->id}")
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $match->id);
        }

        // ---- GET /api/reports: sorting ----

        public function test_reports_are_sorted_newest_first_by_default(): void
        {
            $old = Report::factory()->create(['created_at' => now()->subDays(2)]);
            $new = Report::factory()->create(['created_at' => now()]);
            $middle = Report::factory()->create(['created_at' => now()->subDay()]);

            Sanctum::actingAs(User::factory()->operator()->create());

            $this->assertSame(
                [$new->id, $middle->id, $old->id],
                $this->getJson('/api/reports')->assertOk()->json('data.*.id')
            );
        }

        public function test_reports_can_be_sorted_ascending(): void
        {
            $old = Report::factory()->create(['created_at' => now()->subDays(2)]);
            $new = Report::factory()->create(['created_at' => now()]);

            Sanctum::actingAs(User::factory()->operator()->create());

            $this->assertSame(
                [$old->id, $new->id],
                $this->getJson('/api/reports?sort=created_at&direction=asc')->assertOk()->json('data.*.id')
            );
        }

        public function test_unassigned_reports_sort_last_by_assigned_at(): void
        {
            $operator = User::factory()->operator()->create();
            $unassigned = Report::factory()->open()->create();
            $earlier = Report::factory()->assignedTo($operator)->create(['assigned_at' => now()->subDay()]);
            $later = Report::factory()->assignedTo($operator)->create(['assigned_at' => now()]);

            Sanctum::actingAs($operator);

            $this->assertSame(
                [$later->id, $earlier->id, $unassigned->id],
                $this->getJson('/api/reports?sort=assigned_at&direction=desc')->assertOk()->json('data.*.id')
            );
        }

        public function test_invalid_sort_parameters_are_rejected(): void
        {
            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports?sort=reporter_email')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('sort');

            $this->getJson('/api/reports?sort=created_at;DROP TABLE reports')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('sort');

            $this->getJson('/api/reports?direction=sideways')
                ->assertUnprocessable()
                ->assertJsonValidationErrors('direction');
        }

        // ---- GET /api/reports: eager loading & response shape ----

        public function test_reports_include_category_and_assigned_operator(): void
        {
            $operator = User::factory()->operator()->create(['name' => 'Field Operator']);
            $category = Category::factory()->create(['name' => 'Road Damage']);
            Report::factory()->assignedTo($operator)->create(['category_id' => $category->id]);

            Sanctum::actingAs($operator);

            $this->getJson('/api/reports')
                ->assertOk()
                ->assertJsonPath('data.0.category.id', $category->id)
                ->assertJsonPath('data.0.category.name', 'Road Damage')
                ->assertJsonPath('data.0.assigned_operator.id', $operator->id)
                ->assertJsonPath('data.0.assigned_operator.name', 'Field Operator')
                ->assertJsonMissingPath('data.0.assigned_operator.password')
                ->assertJsonMissingPath('data.0.guest_token_hash');
        }

        public function test_unassigned_report_has_null_assigned_operator(): void
        {
            Report::factory()->open()->create();

            Sanctum::actingAs(User::factory()->operator()->create());

            $this->getJson('/api/reports')
                ->assertOk()
                ->assertJsonPath('data.0.assigned_operator', null);
        }

        public function test_listing_does_not_issue_queries_per_report(): void
        {
            $operator = User::factory()->operator()->create();

            Sanctum::actingAs($operator);

            $countQueries = function (): int {
                DB::flushQueryLog();
                DB::enableQueryLog();
                $this->getJson('/api/reports')->assertOk();
                DB::disableQueryLog();

                return count(DB::getQueryLog());
            };

            Report::factory()->assignedTo($operator)->create();
            $withOne = $countQueries();

            Report::factory()->count(10)->assignedTo($operator)->create();
            $withEleven = $countQueries();

            $this->assertSame($withOne, $withEleven);
        }
    }
