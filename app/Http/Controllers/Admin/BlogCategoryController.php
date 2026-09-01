<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlogCategoryRequest;
use App\Models\BlogCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function index(): View
    {
        $categories = BlogCategory::orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.blog-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.blog-categories.create', [
            'blog_category' => new BlogCategory(),
        ]);
    }

    public function store(BlogCategoryRequest $request): RedirectResponse
    {
        BlogCategory::create($this->payloadFrom($request));

        flash_message('Blog Category created successfully!');

        return redirect()->route('blog-categories.index');
    }

    public function edit(BlogCategory $blog_category): View
    {
        return view('admin.blog-categories.edit', compact('blog_category'));
    }

    public function update(BlogCategoryRequest $request, BlogCategory $blog_category): RedirectResponse
    {
        $blog_category->update($this->payloadFrom($request, $blog_category));

        flash_message('Blog Category updated successfully!');

        return redirect()->route('blog-categories.index');
    }

    public function destroy(BlogCategory $blog_category): RedirectResponse
    {
        $blog_category->delete();

        flash_message('Blog Category deleted successfully!');

        return redirect()->route('blog-categories.index');
    }

    protected function payloadFrom(BlogCategoryRequest $request, ?BlogCategory $category = null): array
    {
        $data = $request->validated();

        $data['slug'] = Str::slug($data['slug'] ?? $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('icon')) {
            $data['icon'] = upload_webp_image($request->file('icon'), 'uploads/blog-categories', 75);
        } else {
            unset($data['icon']);
        }

        return $data;
    }
}
