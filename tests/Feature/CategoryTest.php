<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_creation(): void
    {
        $category = Category::factory()->create();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'is_active' => $category->is_active,
        ]);
    }

    public function test_category_inactive_state(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->assertFalse($category->is_active);
    }

    public function test_category_active_state(): void
    {
        $category = Category::factory()->create();

        $this->assertTrue($category->is_active);
    }

    public function test_category_description_is_nullable(): void
    {
        $category = Category::factory()->create([
            'description' => null,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'description' => null,
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Category::factory()->create([
            'name' => null,
        ]);
    }

    public function test_category_name_is_unique(): void
    {
        $category = Category::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Category::factory()->create([
            'name' => $category->name,
        ]);
    }

    public function test_it_returns_active_categories(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', $category->name);
        $response->assertJsonPath('data.0.description', $category->description);
    }

    public function test_it_does_not_return_inactive_categories(): void
    {
        $category = Category::factory()->inactive()->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_it_returns_a_single_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', $category->name);
        $response->assertJsonPath('data.description', $category->description);
    }

    public function test_it_returns_404_for_non_existent_category(): void
    {
        $response = $this->getJson('/api/categories/999');

        $response->assertNotFound();
    }

    public function test_it_can_create_a_category(): void
    {
        $data = [
            'name' => 'New Category',
            'description' => 'This is a new category.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $response = $this->postJson('/api/categories', $data);
        $response->assertCreated();
    }

    public function test_it_can_update_a_category(): void
    {
        $category = Category::factory()->create();

        $data = [
            'name' => 'Updated Category',
            'description' => 'This is an updated category.',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/categories/{$category->id}", $data);
        $response->assertOk();

    }

    public function test_it_can_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertNoContent();
    }


    public function test_it_returns_404_for_showing_non_existent_category(): void
    {
        $response = $this->getJson('/api/categories/999');

        $response->assertNotFound();
    }

    public function test_it_returns_all_categories()
    {
        $categories = Category::factory()->count(5)->create();

        $response = $this->getJson('/api/categories');
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
    }


}
