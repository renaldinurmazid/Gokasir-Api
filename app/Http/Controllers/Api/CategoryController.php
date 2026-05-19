<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    public function index()
    {
        return $this->ok(Category::forTenant()->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $cat = Category::create(['tenant_id' => $this->tenantId(), 'name' => $request->name]);
        return $this->ok($cat, 'Kategori ditambahkan.', 201);
    }

    public function update(Request $request, Category $category)
    {
        abort_if($category->tenant_id !== $this->tenantId(), 403);
        $request->validate(['name' => 'required|string|max:100']);
        $category->update(['name' => $request->name]);
        return $this->ok($category, 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        abort_if($category->tenant_id !== $this->tenantId(), 403);
        $category->delete();
        return $this->ok(null, 'Kategori dihapus.');
    }
}
