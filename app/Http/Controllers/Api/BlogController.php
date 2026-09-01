<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    use ApiResponse;

    /**
     * Published blog posts for the storefront journal.
     *
     * Query: q, category, page, perPage
     */
    public function index(Request $request)
    {
        $perPage = max(6, min(48, (int) ($request->input('perPage') ?: 24)));

        $query = $this->publishedQuery()
            ->with(['category:id,name,slug', 'author:id,name']);

        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $category = trim((string) $request->input('category', ''));
        if ($category !== '') {
            $query->whereHas('category', function (Builder $builder) use ($category) {
                if (ctype_digit($category)) {
                    $builder->where('id', (int) $category);
                } else {
                    $builder->where('slug', $category)->orWhere('name', $category);
                }
            });
        }

        $blogs = $query
            ->orderByDesc('is_featured')
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        $posts = collect($blogs->items())->map(fn (Blog $blog) => $this->transform($blog));

        return $this->success(
            [
                'current_page' => $blogs->currentPage(),
                'data' => $posts->values()->all(),
                'first_page_url' => $blogs->url(1),
                'from' => $blogs->firstItem(),
                'last_page' => $blogs->lastPage(),
                'last_page_url' => $blogs->url($blogs->lastPage()),
                'next_page_url' => $blogs->nextPageUrl(),
                'path' => $blogs->path(),
                'per_page' => $blogs->perPage(),
                'prev_page_url' => $blogs->previousPageUrl(),
                'to' => $blogs->lastItem(),
                'total' => $blogs->total(),
            ],
            [
                'categories' => $this->activeCategories(),
                'applied' => [
                    'q' => $q,
                    'category' => $category,
                    'page' => (int) $blogs->currentPage(),
                    'perPage' => (int) $blogs->perPage(),
                ],
            ],
            'Blogs fetched successfully'
        );
    }

    /**
     * Single published blog post by slug (or numeric id), with related posts.
     */
    public function show(string $slug)
    {
        $query = $this->publishedQuery()
            ->with(['category:id,name,slug', 'author:id,name']);

        $blog = ctype_digit($slug)
            ? $query->where('id', (int) $slug)->first()
            : $query->where('slug', $slug)->first();

        if (! $blog) {
            return $this->error('Blog post not found.', null, null, 404);
        }

        $related = $this->publishedQuery()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->where('id', '!=', $blog->id)
            ->when(
                $blog->blog_category_id,
                fn (Builder $q) => $q->where('blog_category_id', $blog->blog_category_id)
            )
            ->orderByDesc('is_featured')
            ->orderByDesc('publish_date')
            ->limit(6)
            ->get()
            ->map(fn (Blog $item) => $this->transform($item))
            ->values()
            ->all();

        // If same-category related is thin, top up with latest posts.
        if (count($related) < 3) {
            $existingIds = collect($related)->pluck('id')->push($blog->id)->all();
            $extra = $this->publishedQuery()
                ->with(['category:id,name,slug', 'author:id,name'])
                ->whereNotIn('id', $existingIds)
                ->orderByDesc('publish_date')
                ->limit(3 - count($related))
                ->get()
                ->map(fn (Blog $item) => $this->transform($item))
                ->values()
                ->all();
            $related = array_values(array_merge($related, $extra));
        }

        return $this->success(
            [
                'post' => $this->transform($blog, true),
                'related' => $related,
            ],
            null,
            'Blog post fetched successfully'
        );
    }

    protected function publishedQuery(): Builder
    {
        return Blog::query()
            ->where('is_active', true)
            ->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('publish_date')
                    ->orWhere('publish_date', '<=', now());
            });
    }

    protected function activeCategories(): array
    {
        return BlogCategory::query()
            ->where('is_active', true)
            ->whereHas('blogs', fn (Builder $q) => $q
                ->where('is_active', true)
                ->where('status', 'published')
                ->where(function (Builder $inner) {
                    $inner->whereNull('publish_date')
                        ->orWhere('publish_date', '<=', now());
                }))
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (BlogCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->values()
            ->all();
    }

    protected function transform(Blog $blog, bool $withContent = false): array
    {
        $payload = [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'description' => rewrite_api_assets_in_html($blog->description),
            'featured_image' => $blog->featured_image ? api_asset($blog->featured_image) : null,
            'publish_date' => optional($blog->publish_date)->toIso8601String(),
            'is_featured' => (bool) $blog->is_featured,
            'views_count' => (int) $blog->views_count,
            'read_minutes' => $this->estimateReadMinutes($blog->content ?? $blog->description),
            'category' => $blog->category ? [
                'id' => $blog->category->id,
                'name' => $blog->category->name,
                'slug' => $blog->category->slug,
            ] : null,
            'author' => [
                'name' => $blog->author?->name ?: 'Agonito',
            ],
        ];

        if ($withContent) {
            $payload['content'] = rewrite_api_assets_in_html($blog->content);
        }

        return $payload;
    }

    protected function estimateReadMinutes(?string $html): int
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)) ?? '');
        $words = $text === '' ? 0 : str_word_count($text);

        return max(1, (int) ceil($words / 200));
    }
}
