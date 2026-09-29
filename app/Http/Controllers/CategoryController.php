<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;


class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)->get();
        return CategoryResource::collection($categories);
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        return CategoryResource::make($category);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:categories|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = Category::create($validated);
        return CategoryResource::make($category)
                ->response()
                ->setStatusCode(201);

    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|unique:categories,name,' . $category->id . '|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);
        return CategoryResource::make($category);
    }

    public function delete($id)
    {
        $category = Category::findOrFail($id);

        $category->update([
            'is_active' => false,
        ]);

        return response()->noContent();
    }
}
