<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlogRequest;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class BlogController extends Controller
{
    public function index(): View
    {
        $blogs = Blog::with('category', 'author')
            ->orderByDesc('is_active')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create(): View
    {
        return view('admin.blogs.create', [
            'blog' => new Blog(),
            'categories' => BlogCategory::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(BlogRequest $request): RedirectResponse
    {
        Blog::create($this->payloadFrom($request));

        HomeController::clearCache();
        flash_message('Blog created successfully!');

        return redirect()->route('blogs.index');
    }

    public function edit(Blog $blog): View
    {
        return view('admin.blogs.edit', [
            'blog' => $blog,
            'categories' => BlogCategory::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(BlogRequest $request, Blog $blog): RedirectResponse
    {
        $blog->update($this->payloadFrom($request, $blog));

        HomeController::clearCache();
        flash_message('Blog updated successfully!');

        return redirect()->route('blogs.index');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $blog->delete();

        HomeController::clearCache();
        flash_message('Blog deleted successfully!');

        return redirect()->route('blogs.index');
    }

    protected function payloadFrom(BlogRequest $request, ?Blog $blog = null): array
    {
        $data = $request->validated();

        $data['slug'] = Str::slug($data['slug'] ?? $data['title']);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = Auth::id();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = upload_webp_image($request->file('featured_image'), 'uploads/blogs', 80);
        } else {
            unset($data['featured_image']);
        }

        return $data;
    }
}
