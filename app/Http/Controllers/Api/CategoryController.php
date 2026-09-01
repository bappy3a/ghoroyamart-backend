<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * All active categories as a parent/child tree (via parent_id).
     *
     * Query: featured, popular, flat, parent_id
     */
    public function index(Request $request)
    {
        $query = Category::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('popular')) {
            $query->where('is_popular', true);
        }

        // Flat list of all categories (including children), each with parent_id.
        if ($request->boolean('flat')) {
            if ($request->filled('parent_id')) {
                $query->where('parent_id', (int) $request->input('parent_id'));
            }

            $categories = $query->get()->map(fn (Category $category) => $this->transform($category));

            return $this->success($categories, null, 'Categories fetched successfully');
        }

        // Tree: root parents with nested children linked by parent_id.
        $roots = $query
            ->whereNull('parent_id')
            ->with(['children' => function ($q) use ($request) {
                $q->where('is_active', true)->orderBy('name');

                if ($request->boolean('featured')) {
                    $q->where('is_featured', true);
                }

                if ($request->boolean('popular')) {
                    $q->where('is_popular', true);
                }
            }])
            ->get()
            ->map(fn (Category $category) => $this->transform($category, true));

        return $this->success($roots, null, 'Categories fetched successfully');
    }

    /**
     * Single category by id or slug, including children.
     */
    public function show(string $id)
    {
        $query = Category::query()
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('name')]);

        $category = ctype_digit($id)
            ? $query->where('id', (int) $id)->first()
            : $query->where('slug', $id)->first();

        if (! $category) {
            return $this->error('Category not found.', null, null, 404);
        }

        return $this->success($this->transform($category, true, true), null, 'Category fetched successfully');
    }

    protected function transform(Category $category, bool $withChildren = false, bool $withMeta = false): array
    {
        $payload = [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon ? api_asset($category->icon) : null,
            'icon_class' => $category->icon_class,
            'image' => $category->image ? api_asset($category->image) : null,
            'is_featured' => (bool) $category->is_featured,
            'is_popular' => (bool) $category->is_popular,
        ];

        if ($withMeta) {
            $payload['meta'] = [
                'title' => $category->meta_title ?: $category->name,
                'description' => $category->meta_description ?: $category->description,
                'keywords' => $category->meta_keywords,
                'image' => ($category->meta_image ?: $category->image)
                    ? api_asset($category->meta_image ?: $category->image)
                    : null,
            ];
        }

        if ($withChildren) {
            $payload['children'] = $category->children
                ->map(fn (Category $child) => $this->transform($child))
                ->values()
                ->all();
        }

        return $payload;
    }
}
