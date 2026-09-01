<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductsCollection;
use App\Http\Resources\Slider\SliderCollection;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Slider;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    use ApiResponse;

    private const CACHE_KEY = 'api.home';

    private const CACHE_TTL_MINUTES = 720; // 12 hours

    /** @var array<string, mixed> */
    private array $settings = [];

    /** @var array<string, array<int, mixed>> */
    private array $productResults = [];

    /**
     * Full storefront homepage payload in one request.
     * Shape mirrors agonito-frontend/src/app/page.tsx.
     */
    public function index()
    {
        $payload = Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            return $this->buildHomePayload();
        });

        return $this->success($payload, null, 'Home page fetched successfully');
    }

    private function buildHomePayload(): array
    {
        // Loading every setting separately creates a cache/database query per key.
        // The homepage uses many settings, so fetch the cached key/value map once.
        $this->settings = Setting::getAllKeyValue();
        $this->productResults = [];

        $counts = [
            'sliders' => 10,
            'popular_categories' => 8,
            'featured' => 8,
            'best_selling' => 10,
            'trending' => 15,
            'new_arrival' => 12,
            'popular' => 12,
            'diamond' => 12,
            'brands' => 10,
        ];
        $sliders = $this->sliders($counts['sliders']);
        $flashSale = $this->flashSale();
        $sectionData = $this->loadSectionCategories(
            $this->shoppingSectionItems(),
            $this->categoryBannerItems()
        );

        return [
            // Hero 3-column layout: featured categories | slider | flash sale tile
            'hero' => [
                'featured_categories' => $this->featuredCategories(11),
                'sliders' => $sliders,
                'flash_sale' => $this->flashSalePreview($flashSale),
            ],
            'sliders' => $sliders,
            'popular_categories' => [
                'title' => $this->setting('home_popular_categories_title', 'Popular Categories'),
                'items' => $this->popularCategories($counts['popular_categories']),
            ],
            'flash_sale' => $flashSale,
            'recommended' => $this->productsBy('popular', 18),
            'rails' => $this->rails($counts),
            'brands' => $this->brands($counts['brands']),
            'deals_under' => [
                'eyebrow' => 'Budget picks',
                'title' => 'Deals under ৳10',
                'max_price' => 10,
                'products' => $this->dealsUnder(10, 20),
            ],
            // Matches CategorySection in page.tsx (banner + product rail)
            'shopping_sections' => $this->shoppingSections($sectionData['shopping_items'], $sectionData['categories'], $sectionData['children_by_parent']),
            'category_banners' => $this->categoryBanners($sectionData['banner_items'], $sectionData['categories'], $sectionData['children_by_parent']),
            'trending_banner' => [
                'image' => $this->assetOrNull($this->setting('home_trending_banner_image', '')),
                'subtitle' => $this->setting('home_trending_banner_subtitle', ''),
                'text' => $this->setting('home_trending_banner_text', ''),
                'link' => $this->setting('home_trending_banner_link', '#'),
                'link_text' => $this->setting('home_trending_banner_link_text', 'Collection'),
            ],
            'video' => [
                'link' => $this->setting('home_video_link', ''),
                'banner' => $this->assetOrNull($this->setting('home_video_banner', '')),
                'text' => $this->setting('home_video_banner_text', ''),
            ],
            'reviews' => [
                'subtitle' => $this->setting('home_product_review_subtitle', ''),
                'title' => $this->setting('home_product_review_title', ''),
                'description' => $this->setting('home_product_review_description', ''),
            ],
            'instagram' => $this->instagramImages(),
            'blogs' => $this->blogs(6),
            'featured_section' => [
                'subtitle' => $this->setting('home_featured_section_subtitle', 'Summer collection'),
                'title' => $this->setting('home_featured_section_title', 'Shopping Every Day'),
            ],
            'top_selling_title' => $this->setting('home_top_selling_title', 'Top selling Categories This Week'),
        ];
    }

    /**
     * Hero left sidebar — active is_featured categories with children for flyout.
     */
    private function featuredCategories(int $limit): array
    {
        $publishedScope = fn (Builder $q) => $q
            ->where('status', 'published')
            ->where('visibility', 'public');

        return Category::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with(['children' => function ($q) use ($publishedScope) {
                $q->where('is_active', true)
                    ->withCount(['products as products_count' => $publishedScope])
                    ->orderBy('name');
            }])
            ->withCount(['products as products_count' => $publishedScope])
            ->orderBy('name')
            ->limit(max(1, $limit))
            ->get()
            ->map(function (Category $category) {
                $children = $category->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'image' => $this->assetOrNull($child->image),
                    'icon' => $this->assetOrNull($child->icon),
                    'products_count' => (int) $child->products_count,
                ])->values()->all();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $this->assetOrNull($category->image),
                    'icon' => $this->assetOrNull($category->icon),
                    'icon_class' => $category->icon_class,
                    'products_count' => (int) $category->products_count + collect($children)->sum('products_count'),
                    'children' => $children,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Compact flash-sale payload for the hero right column tile.
     * Frontend Quick view scrolls to #flash-sale section.
     */
    private function flashSalePreview(?array $deal): ?array
    {
        if (! $deal) {
            return null;
        }

        $firstProduct = $deal['products'][0] ?? null;

        return [
            'id' => $deal['id'],
            'title' => $deal['title'],
            'slug' => $deal['slug'],
            'banner_image' => $deal['banner_image'],
            'discount_percentage' => $deal['discount_percentage'],
            'start_date' => $deal['start_date'],
            'end_date' => $deal['end_date'],
            'section_anchor' => 'flash-sale',
            'product' => $firstProduct,
            'products_count' => count($deal['products']),
        ];
    }

    private function sliders(int $limit): array
    {
        $sliders = Slider::query()
            ->active()
            ->ordered()
            ->limit(max(1, $limit))
            ->get();

        return (new SliderCollection($sliders))->resolve();
    }

    private function popularCategories(int $limit): array
    {
        $publishedScope = fn (Builder $q) => $q
            ->where('status', 'published')
            ->where('visibility', 'public');

        return Category::query()
            ->where('is_active', true)
            ->where('is_popular', true)
            ->withCount(['products as products_count' => $publishedScope])
            ->orderBy('name')
            ->limit(max(1, $limit))
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'image' => $this->assetOrNull($category->image),
                'icon' => $this->assetOrNull($category->icon),
                'products_count' => (int) $category->products_count,
            ])
            ->values()
            ->all();
    }

    private function flashSale(): ?array
    {
        $deal = FlashDeal::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $deal) {
            return null;
        }

        $productIds = is_array($deal->product_ids) ? $deal->product_ids : [];
        $products = collect();

        if ($productIds !== []) {
            $products = $this->storefrontQuery()
                ->with(['category.parent', 'brand'])
                ->whereIn('id', $productIds)
                ->get()
                ->sortBy(fn (Product $p) => array_search($p->id, $productIds, true))
                ->values();
        }

        return [
            'id' => $deal->id,
            'title' => $deal->title,
            'slug' => $deal->slug,
            'description' => $deal->description,
            'banner_image' => $this->assetOrNull($deal->banner_image),
            'background_color' => $deal->background_color,
            'text_color' => $deal->text_color,
            'start_date' => optional($deal->start_date)->toDateString(),
            'end_date' => optional($deal->end_date)->toDateString(),
            'discount_percentage' => $deal->discount_percentage,
            'products' => (new ProductsCollection($products))->resolve(),
        ];
    }

    /**
     * Product rails matching frontend RAIL_SECTIONS order/keys.
     */
    private function rails(array $counts): array
    {
        $definitions = [
            ['key' => 'trending', 'eyebrow' => 'Hot right now', 'title' => 'Trending products', 'type' => 'trending', 'limit' => $counts['trending']],
            // ['key' => 'best', 'eyebrow' => 'Bestsellers', 'title' => 'Best selling products', 'type' => 'best_selling', 'limit' => $counts['best_selling']],
            ['key' => 'new', 'eyebrow' => 'Just landed', 'title' => 'New arrivals', 'type' => 'new', 'limit' => $counts['new_arrival']],
            ['key' => 'featured', 'eyebrow' => 'Handpicked', 'title' => 'Featured products', 'type' => 'featured', 'limit' => $counts['featured']],
            // ['key' => 'toprated', 'eyebrow' => '4.8★ and above', 'title' => 'Top rated products', 'type' => 'top_rated', 'limit' => $counts['popular']],
            // ['key' => 'week', 'eyebrow' => 'This week', 'title' => 'Popular this week', 'type' => 'popular', 'limit' => $counts['popular']],
            // ['key' => 'viewed', 'eyebrow' => 'Shopper favourites', 'title' => 'Most viewed', 'type' => 'most_viewed', 'limit' => $counts['popular']],
            // ['key' => 'recent', 'eyebrow' => 'Fresh', 'title' => 'Recently added', 'type' => 'new', 'limit' => $counts['new_arrival']],
            // ['key' => 'editor', 'eyebrow' => 'Curated', 'title' => "Editor's choice", 'type' => 'featured', 'limit' => $counts['diamond']],
            // ['key' => 'daily', 'eyebrow' => 'Today only', 'title' => 'Daily deals', 'type' => 'discounted', 'limit' => $counts['popular']],
            // ['key' => 'weekend', 'eyebrow' => '48 hours', 'title' => 'Weekend offers', 'type' => 'discounted', 'limit' => $counts['trending']],
            // ['key' => 'limited', 'eyebrow' => 'Almost gone', 'title' => 'Limited time deals', 'type' => 'low_stock', 'limit' => $counts['popular']],
            ['key' => 'viewedagain', 'eyebrow' => 'Pick up where you left', 'title' => 'Recently viewed', 'type' => 'most_viewed', 'limit' => $counts['popular']],
        ];

        return collect($definitions)->map(fn (array $rail) => [
            'key' => $rail['key'],
            'eyebrow' => $rail['eyebrow'],
            'title' => $rail['title'],
            'products' => $this->productsBy($rail['type'], max(1, (int) $rail['limit'])),
        ])->values()->all();
    }

    private function brands(int $limit): array
    {
        return Brand::query()
            ->where('status', true)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(max(1, $limit))
            ->get(['id', 'name', 'slug', 'logo', 'is_featured'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'logo' => $this->assetOrNull($brand->logo),
                'is_featured' => (bool) $brand->is_featured,
            ])
            ->values()
            ->all();
    }

    private function dealsUnder(float $maxPrice, int $limit): array
    {
        $products = $this->storefrontQuery()
            ->with(['category.parent', 'brand'])
            ->where('price', '<=', $maxPrice)
            ->orderBy('price')
            ->orderByDesc('num_of_sale')
            ->limit($limit)
            ->get();

        return (new ProductsCollection($products))->resolve();
    }

    /**
     * Raw shopping-section item config from admin home-page-settings, with the
     * legacy single-section fallback applied. No queries here.
     */
    private function shoppingSectionItems(): array
    {
        $items = json_decode($this->setting('home_shopping_section_items', '[]'), true) ?: [];

        if ($items === []) {
            // Legacy single shopping section fallback
            $categoryId = $this->setting('home_shopping_category_id', '');
            if ($categoryId !== '' && $categoryId !== null) {
                $items = [[
                    'title' => $this->setting('home_shopping_banner_title', 'Trending Now Only This Weekend!'),
                    'category_id' => (string) $categoryId,
                    'products_limit' => (int) $this->setting('home_shopping_products_limit', 8),
                    'link' => '#',
                    'link_text' => 'Shop Now',
                    'banner_image' => '',
                ]];
            }
        }

        return $items;
    }

    private function categoryBannerItems(): array
    {
        return json_decode($this->setting('home_category_banners', '[]'), true) ?: [];
    }

    /**
     * Resolve every category referenced by shopping-section/category-banner items
     * in one batched pair of queries (categories + their children), instead of
     * one query per section/banner row.
     *
     * @return array{categories: Collection<int, Category>, children_by_parent: Collection<int, Collection<int, int>>, shopping_items: array, banner_items: array}
     */
    private function loadSectionCategories(array $shoppingItems, array $bannerItems): array
    {
        $categoryIds = collect($shoppingItems)
            ->concat($bannerItems)
            ->pluck('category_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id');

        $childrenByParent = Category::query()
            ->where('is_active', true)
            ->whereIn('parent_id', $categoryIds)
            ->get()
            ->groupBy('parent_id')
            ->map(fn (Collection $children) => $children->pluck('id'));

        return [
            'shopping_items' => $shoppingItems,
            'banner_items' => $bannerItems,
            'categories' => $categories,
            'children_by_parent' => $childrenByParent,
        ];
    }

    /**
     * Shopping Every Day sections from admin home-page-settings.
     * Each item: banner + category products — same as CategorySection on the homepage.
     */
    private function shoppingSections(array $items, Collection $categories, Collection $childrenByParent): array
    {
        if ($items === []) {
            return [];
        }

        $sections = [];

        foreach ($items as $item) {
            $categoryId = (int) ($item['category_id'] ?? 0);
            $category = $categories->get($categoryId);
            $limit = max(1, (int) ($item['products_limit'] ?? 8));
            $title = trim((string) ($item['title'] ?? '')) ?: ($category?->name ?? 'Shop the collection');
            $linkText = trim((string) ($item['link_text'] ?? '')) ?: ('Explore '.($category?->name ?? 'collection'));
            $link = trim((string) ($item['link'] ?? ''));
            if ($link === '' || $link === '#') {
                $link = $category ? '/categories/'.$category->slug : '#';
            }

            $sections[] = [
                'title' => $title,
                'eyebrow' => 'Shop the collection',
                'banner_image' => $this->assetOrNull($item['banner_image'] ?? ''),
                'link' => $link,
                'link_text' => $linkText,
                'products_limit' => $limit,
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $this->assetOrNull($category->image),
                ] : null,
                'products' => $category
                    ? $this->productsForCategory($category, $limit, $childrenByParent->get($categoryId, collect()))
                    : [],
            ];
        }

        return $sections;
    }

    private function categoryBanners(array $banners, Collection $categories, Collection $childrenByParent): array
    {
        if ($banners === []) {
            return [];
        }

        $result = [];

        foreach ($banners as $banner) {
            $categoryId = (int) ($banner['category_id'] ?? 0);
            $category = $categories->get($categoryId);
            $limit = max(1, (int) ($banner['products_limit'] ?? 8));

            $result[] = [
                'text' => $banner['text'] ?? '',
                'discount_text' => $banner['discount_text'] ?? '',
                'link' => $banner['link'] ?? '#',
                'link_text' => $banner['link_text'] ?? 'Check Discount',
                'banner_image' => $this->assetOrNull($banner['banner_image'] ?? ''),
                'products_limit' => $limit,
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $this->assetOrNull($category->image),
                ] : null,
                'products' => $category
                    ? $this->productsForCategory($category, $limit, $childrenByParent->get($categoryId, collect()))
                    : [],
            ];
        }

        return $result;
    }

    private function instagramImages(): array
    {
        $images = json_decode($this->setting('home_instagram_images', '[]'), true) ?: [];

        return collect($images)
            ->filter()
            ->map(fn ($path) => $this->assetOrNull($path))
            ->filter()
            ->values()
            ->all();
    }

    private function blogs(int $limit): array
    {
        return Blog::query()
            ->where('is_active', true)
            ->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('publish_date')
                    ->orWhere('publish_date', '<=', now());
            })
            ->with('category:id,name,slug')
            ->orderByDesc('is_featured')
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Blog $blog) => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => rewrite_api_assets_in_html($blog->description),
                'featured_image' => $this->assetOrNull($blog->featured_image),
                'publish_date' => optional($blog->publish_date)->toDateString(),
                'is_featured' => (bool) $blog->is_featured,
                'views_count' => (int) $blog->views_count,
                'category' => $blog->category ? [
                    'id' => $blog->category->id,
                    'name' => $blog->category->name,
                    'slug' => $blog->category->slug,
                ] : null,
            ])
            ->values()
            ->all();
    }

    private function productsForCategory(Category $category, int $limit, Collection $childIds): array
    {
        $ids = collect([$category->id])
            ->merge($childIds)
            ->unique()
            ->values();

        $products = $this->storefrontQuery()
            ->with(['category.parent', 'brand'])
            ->whereIn('category_id', $ids)
            ->orderByDesc('num_of_sale')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return (new ProductsCollection($products))->resolve();
    }

    private function productsBy(string $type, int $limit): array
    {
        $cacheKey = $type.':'.$limit;

        if (array_key_exists($cacheKey, $this->productResults)) {
            return $this->productResults[$cacheKey];
        }

        $query = $this->storefrontQuery()->with(['category.parent', 'brand']);

        match ($type) {
            'featured' => $query->where('is_featured', true)->orderByDesc('num_of_sale'),
            'best_selling' => $query->where('is_best_seller', true)->orderByDesc('num_of_sale'),
            'new' => $query->where(function (Builder $q) {
                $q->where('is_new', true)
                    ->orWhere('published_at', '>=', now()->subDays(30));
            })->orderByDesc('published_at')->orderByDesc('id'),
            'trending' => $query->where('is_featured', true)->orderByDesc('num_of_views')->orderByDesc('num_of_sale'),
            'top_rated' => $query->where('reviews_avg', '>=', 4.8)->orderByDesc('reviews_avg')->orderByDesc('num_of_reviews'),
            'most_viewed' => $query->orderByDesc('num_of_views')->orderByDesc('id'),
            'discounted' => $query->where('is_discounted', true)->orderByDesc('discount_percentage')->orderByDesc('discount_amount'),
            'low_stock' => $query->where('quantity', '>', 0)->where('quantity', '<=', 10)->orderBy('quantity')->orderByDesc('num_of_sale'),
            default => $query->orderByDesc('num_of_sale')->orderByDesc('num_of_views'),
        };

        // If flag-based queries return too few rows, backfill with popular products.
        $products = $query->limit($limit)->get();

        if ($products->count() < $limit && in_array($type, ['featured', 'best_selling', 'new'], true)) {
            $needed = $limit - $products->count();
            $extra = $this->storefrontQuery()
                ->with(['category.parent', 'brand'])
                ->whereNotIn('id', $products->pluck('id'))
                ->orderByDesc('num_of_sale')
                ->limit($needed)
                ->get();
            $products = $products->concat($extra)->values();
        }

        return $this->productResults[$cacheKey] = (new ProductsCollection($products))->resolve();
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->settings)
            ? $this->settings[$key]
            : $default;
    }

    private function storefrontQuery(): Builder
    {
        return Product::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->with([
                'category.parent',
                'brand',
                'variants' => fn ($q) => $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['values.attribute', 'values.value']),
            ]);
    }

    private function assetOrNull(?string $path): ?string
    {
        $path = trim((string) $path);

        return $path !== '' ? api_asset($path) : null;
    }

    /**
     * Bust homepage API cache after admin settings change.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
