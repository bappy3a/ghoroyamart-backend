<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductsCollection;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Public products catalogue — one endpoint for listing + all filter options.
     *
     * Query: q, category, sub, vendor, sort, rating, min, max, page, perPage
     */
    public function index(Request $request)
    {
        $perPage = max(12, min(48, (int) ($request->input('perPage') ?: 24)));
        $sort = trim((string) ($request->input('sort') ?: 'popular')) ?: 'popular';

        $query = $this->storefrontQuery()->with(['category.parent', 'brand']);

        $this->applyFilters($query, $request);
        $this->applySort($query, $sort);

        $products = $query->paginate($perPage)->appends($request->query());

        return $this->productsWithPagination(
            $products,
            [
                'applied' => [
                    'q' => (string) $request->input('q', ''),
                    'category' => (string) $request->input('category', ''),
                    'sub' => (string) $request->input('sub', ''),
                    'vendor' => (string) $request->input('vendor', ''),
                    'sort' => $sort,
                    'rating' => (float) $request->input('rating', 0),
                    'min' => (float) $request->input('min', 0),
                    'max' => (float) $request->input('max', 0),
                    'page' => (int) $products->currentPage(),
                    'perPage' => (int) $products->perPage(),
                ],
                'categories' => $this->categoryTreeWithCounts(),
                'vendors' => $this->vendorsWithCounts(),
                'sorts' => [
                    ['value' => 'popular', 'label' => 'Most popular'],
                    ['value' => 'new', 'label' => 'Newest'],
                    ['value' => 'price-asc', 'label' => 'Price: low to high'],
                    ['value' => 'price-desc', 'label' => 'Price: high to low'],
                    ['value' => 'rating', 'label' => 'Top rated'],
                    ['value' => 'discount', 'label' => 'Biggest discount'],
                ],
                'ratings' => [4.5, 4, 3.5, 0],
            ],
            'Products fetched successfully'
        );
    }

    /**
     * Product details for storefront /product/[id].
     * Accepts numeric id or slug. Includes variants, reviews, related products.
     */
    public function show(string $id)
    {
        $query = $this->storefrontQuery()
            ->with([
                'category.parent',
                'brand',
                'variants' => fn ($q) => $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['values.attribute', 'values.value']),
                'approvedReviews' => fn ($q) => $q->with('user:id,name')->latest()->limit(20),
            ]);

        $product = ctype_digit($id)
            ? $query->where('id', (int) $id)->first()
            : $query->where('slug', $id)->first();

        if (! $product) {
            return $this->error('Product not found.', null, null, 404);
        }

        $related = $this->storefrontQuery()
            ->with(['category.parent', 'brand'])
            ->where('id', '!=', $product->id)
            ->when(
                $product->category_id,
                fn (Builder $q) => $q->where(function (Builder $inner) use ($product) {
                    $inner->where('category_id', $product->category_id);
                    if ($product->category?->parent_id) {
                        $childIds = Category::query()
                            ->where('parent_id', $product->category->parent_id)
                            ->pluck('id');
                        $inner->orWhereIn('category_id', $childIds->push($product->category->parent_id));
                    }
                })
            )
            ->orderByDesc('num_of_sale')
            ->limit(12)
            ->get();

        return $this->success([
            'product' => $this->transformProductDetail($product),
            'related' => (new ProductsCollection($related))->resolve(),
        ], null, 'Product details fetched successfully');
    }

    private function transformProductDetail(Product $product): array
    {
        $images = collect(is_array($product->images) ? $product->images : [])
            ->filter()
            ->map(fn ($image) => api_asset($image))
            ->values()
            ->all();
        $thumbnailImage = $product->thumbnail_image ? api_asset($product->thumbnail_image) : null;
        $gallery = array_values(array_filter(array_unique(array_merge(
            $thumbnailImage ? [$thumbnailImage] : [],
            $images
        ))));

        $variants = $product->variants->map(function ($variant) {
            $attributes = $variant->values->map(fn ($row) => [
                'attribute_id' => $row->variant_attribute_id,
                'attribute' => $row->attribute?->name,
                'attribute_slug' => $row->attribute?->slug,
                'value_id' => $row->variant_attribute_value_id,
                'value' => $row->value?->value,
            ])->values()->all();

            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'image' => $variant->image ? api_asset($variant->image) : null,
                'quantity' => $variant->quantity,
                'selling_price' => $variant->selling_price,
                'attributes' => $attributes,
            ];
        })->values()->all();

        // Group attribute options for color/size style UI (like frontend VariantGroup).
        $variantGroups = product_variant_groups($product);

        $reviews = $product->approvedReviews->map(fn ($review) => [
            'id' => $review->id,
            'rating' => $review->rating,
            'review_text' => $review->review_text,
            'images' => collect($review->images ?? [])->map(fn ($img) => api_asset($img))->values()->all(),
            'user' => $review->user?->name,
            'created_at' => optional($review->created_at)?->toIso8601String(),
        ])->values()->all();

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'title' => $product->name,
            'short_description' => rewrite_api_assets_in_html($product->short_description),
            'description' => rewrite_api_assets_in_html($product->description),
            'how_to_use' => rewrite_api_assets_in_html($product->how_to_use),
            'good_to_know' => rewrite_api_assets_in_html($product->good_to_know),
            'warranty' => rewrite_api_assets_in_html($product->warranty),
            'sku' => $product->sku,
            'price' => $product->price,
            'regular_price' => $product->regular_price,
            'old_price' => $product->regular_price,
            'discount_percentage' => $product->discount_percentage,
            'discount_amount' => $product->discount_amount,
            'discount' => $product->discount_percentage,
            'is_discounted' => $product->is_discounted,
            'is_featured' => $product->is_featured,
            'is_new' => $product->is_new,
            'is_best_seller' => $product->is_best_seller,
            'reviews_avg' => $product->reviews_avg,
            'rating' => $product->reviews_avg,
            'reviews_count' => $product->num_of_reviews,
            'reviews' => $product->num_of_reviews,
            'sold' => $product->num_of_sale,
            'stock' => $product->quantity,
            'stock_status' => $product->stock_status,
            'thumbnail_image' => $thumbnailImage,
            'image' => $thumbnailImage,
            'hover_image' => $images[0] ?? $thumbnailImage,
            'images' => $gallery,
            'video_media' => $product->video_media ? api_asset($product->video_media) : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
                'parent' => $product->category->parent ? [
                    'id' => $product->category->parent->id,
                    'name' => $product->category->parent->name,
                    'slug' => $product->category->parent->slug,
                ] : null,
            ] : null,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
                'logo' => $product->brand->logo ? api_asset($product->brand->logo) : null,
            ] : null,
            'vendor' => $product->brand?->name,
            'has_variants' => count($variantGroups) > 0,
            'variants' => $variants,
            'variant_groups' => $variantGroups,
            'customer_reviews' => $reviews,
            'meta' => [
                'title' => $product->meta_title ?: $product->name,
                'description' => $product->meta_description ?: $product->short_description,
                'keywords' => $product->meta_keywords,
                'image' => $product->meta_image ? api_asset($product->meta_image) : $thumbnailImage,
            ],
        ];
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

    private function applyFilters(Builder $query, Request $request): void
    {
        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $query->where(function (Builder $builder) use ($q) {
                $like = '%'.$q.'%';
                $builder->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                    ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));
            });
        }

        $category = trim((string) $request->input('category', ''));
        $sub = trim((string) $request->input('sub', ''));

        if ($sub !== '') {
            $query->whereHas('category', function (Builder $c) use ($sub, $category) {
                $c->where(function (Builder $inner) use ($sub) {
                    $inner->where('slug', $sub)->orWhere('name', $sub);
                });
                if ($category !== '') {
                    $c->whereHas('parent', function (Builder $p) use ($category) {
                        $p->where('slug', $category)->orWhere('name', $category);
                    });
                }
            });
        } elseif ($category !== '') {
            $categoryIds = Category::query()
                ->where(function (Builder $c) use ($category) {
                    $c->where('slug', $category)->orWhere('name', $category);
                })
                ->pluck('id');

            $childIds = Category::query()
                ->whereIn('parent_id', $categoryIds)
                ->pluck('id');

            $ids = $categoryIds->merge($childIds)->unique()->values();

            if ($ids->isNotEmpty()) {
                $query->whereIn('category_id', $ids);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $vendor = trim((string) $request->input('vendor', ''));
        if ($vendor !== '') {
            $query->whereHas('brand', function (Builder $b) use ($vendor) {
                $b->where('slug', $vendor)->orWhere('name', $vendor);
            });
        }

        $rating = (float) $request->input('rating', 0);
        if ($rating > 0) {
            $query->where('reviews_avg', '>=', $rating);
        }

        $min = (float) $request->input('min', 0);
        if ($min > 0) {
            $query->where('price', '>=', $min);
        }

        $max = (float) $request->input('max', 0);
        if ($max > 0) {
            $query->where('price', '<=', $max);
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price-asc' => $query->orderBy('price', 'asc'),
            'price-desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderByDesc('reviews_avg')->orderByDesc('num_of_reviews'),
            'discount' => $query->orderByDesc('discount_percentage')->orderByDesc('discount_amount'),
            'new' => $query->orderByDesc('published_at')->orderByDesc('id'),
            default => $query->orderByDesc('num_of_sale')->orderByDesc('num_of_views'),
        };
    }

    private function publishedProductScope(): \Closure
    {
        return fn (Builder $q) => $q
            ->where('status', 'published')
            ->where('visibility', 'public');
    }

    private function categoryTreeWithCounts(): array
    {
        $publishedScope = $this->publishedProductScope();

        $roots = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) use ($publishedScope) {
                $q->where('is_active', true)
                    ->withCount(['products as products_count' => $publishedScope])
                    ->orderBy('name');
            }])
            ->withCount(['products as products_count' => $publishedScope])
            ->orderBy('name')
            ->get();

        return $roots->map(function (Category $category) {
            $children = $category->children->map(fn (Category $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'products_count' => (int) $child->products_count,
            ])->values()->all();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon ? api_asset($category->icon) : null,
                'icon_class' => $category->icon_class,
                'image' => $category->image ? api_asset($category->image) : null,
                'is_featured' => (bool) $category->is_featured,
                'is_popular' => (bool) $category->is_popular,
                'meta' => [
                    'title' => $category->meta_title ?: $category->name,
                    'description' => $category->meta_description ?: $category->description,
                    'keywords' => $category->meta_keywords,
                    'image' => ($category->meta_image ?: $category->image)
                        ? api_asset($category->meta_image ?: $category->image)
                        : null,
                ],
                'products_count' => (int) $category->products_count + collect($children)->sum('products_count'),
                'subcategories' => $children,
            ];
        })->values()->all();
    }

    private function vendorsWithCounts(): array
    {
        return Brand::query()
            ->where('status', true)
            ->withCount(['products as products_count' => $this->publishedProductScope()])
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'logo'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'logo' => $brand->logo ? api_asset($brand->logo) : null,
                'products_count' => (int) $brand->products_count,
            ])
            ->values()
            ->all();
    }
}
