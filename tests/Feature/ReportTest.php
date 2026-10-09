<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Report;
use function PHPUnit\Framework\assertTrue;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_creation(): void
    {
        $category = Category::factory()->create();
        $report = Report::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'category_id' => $report->category_id,
            'description' => $report->description,
            'location' => $report->location,
            'tracking_number' => $report->tracking_number,
            'reporter_email' => $report->reporter_email,
        ]);
    }

    public function test_report_category_relationship(): void
    {
        $category = Category::factory()->create();
        $report = Report::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Category::class, $report->category);
        $this->assertEquals($category->id, $report->category->id);
    }

    public function test_report_tracking_number_is_unique(): void
    {
        $category = Category::factory()->create();
        $report = Report::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Report::factory()->create([
            'category_id' => $category->id,
            'tracking_number' => $report->tracking_number,
        ]);
    }

    public function test_report_reporter_email_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Report::factory()->create([
            'reporter_email' => null,
        ]);
    }

    public function test_report_description_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Report::factory()->create([
            'description' => null,
        ]);
    }

    public function test_report_location_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Report::factory()->create([
            'location' => null,
        ]);
    }

    public function test_report_tracking_number_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Report::factory()->create([
            'tracking_number' => null,
        ]);
    }

    public function test_report_category_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Report::factory()->create([
            'category_id' => null,
        ]);
    }

    public function test_report_category_id_must_exist(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Report::factory()->create([
            'category_id' => 9999, // Assuming this ID does not exist
        ]);
    }

    public function test_report_can_be_updated(): void
    {
        $report = Report::factory()->create();

        $originalTrackingNumber = $report->tracking_number;

        $report->update([
            'description' => 'Updated description',
        ]);

        $report->refresh();

        $this->assertEquals(
            'Updated description',
            $report->description
        );

        $this->assertEquals(
            $originalTrackingNumber,
            $report->tracking_number
        );
    }

}
