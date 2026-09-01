<?php

namespace App\Services\AiMessage\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Read-only product lookups for the AI message service. Only queries
 * published/public products so the assistant can never surface drafts
 * or hidden catalogue items.
 */
class ProductLookupService
{
    /**
     * Search the storefront catalogue by name, SKU, brand, or category.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $like = '%'.$query.'%';

        // Product names are stored in Bangla but slugs are Latin/Banglish
        // (e.g. name "নকশি কাঁথা" -> slug "nakshi-katha"). Customers often
        // type Banglish or English, so also match against the slug and
        // against individual words so a partial/translated term still hits.
        $slug = Str::slug($query);
        $tokens = array_values(array_unique(array_filter(
            [...preg_split('/[\s,-]+/u', $query) ?: [], ...explode('-', $slug)],
            fn ($token) => mb_strlen((string) $token) >= 3
        )));

        $matcher = function (Builder $builder) use ($like, $slug, $tokens) {
            $builder->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('short_description', 'like', $like)
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like));

            if ($slug !== '') {
                $builder->orWhere('slug', 'like', '%'.$slug.'%');
            }

            foreach ($tokens as $token) {
                $tokenLike = '%'.$token.'%';
                $builder->orWhere('name', 'like', $tokenLike)
                    ->orWhere('slug', 'like', $tokenLike);
            }
        };

        $total = $this->storefrontQuery()->where($matcher)->count();

        if ($total === 0) {
            return collect();
        }

        // 10 or fewer matches: return the full list. More than that: trim
        // to the best sellers that are actually in stock, so the AI shows
        // the customer options they can actually buy right now.
        $products = $this->storefrontQuery()
            ->where($matcher)
            ->when(
                $total > $limit,
                fn (Builder $q) => $q->orderByRaw("(stock_status = 'out_of_stock') asc")
            )
            ->orderByDesc('num_of_sale')
            ->orderByDesc('num_of_views')
            ->limit(min($total, $limit))
            ->get();

        return $products->map(fn (Product $product) => $this->present($product))->values();
    }

    /**
     * Look up a single product by numeric ID or slug.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $product = $this->storefrontQuery()
            ->where(function (Builder $q) use ($identifier) {
                if (ctype_digit($identifier)) {
                    $q->where('id', (int) $identifier);
                } else {
                    $q->where('slug', $identifier);
                }
            })
            ->first();

        return $product ? $this->present($product) : null;
    }

    private function storefrontQuery(): Builder
    {
        return Product::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->with([
                'brand:id,name',
                'category:id,name',
                'variants' => fn ($q) => $q->where('is_active', true)->with('values.value'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Product $product): array
    {
        $variants = $product->variants;
        $hasVariants = $variants->isNotEmpty();

        $stockQuantity = $hasVariants
            ? (int) $variants->sum('quantity')
            : (int) $product->quantity;

        $available = $product->stock_status !== 'out_of_stock' && $stockQuantity > 0;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'image' => $product->thumbnail_image,
            'sku' => $product->sku,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'price' => (float) $product->price,
            'regular_price' => (float) $product->regular_price,
            'is_discounted' => (bool) $product->is_discounted,
            'discount_percentage' => (float) $product->discount_percentage,
            'stock_status' => $product->stock_status,
            'available' => $available,
            'stock_quantity' => $stockQuantity,
            'has_variants' => $hasVariants,
            'variants' => $hasVariants
                ? $variants->take(8)->map(fn (ProductVariant $variant) => [
                    'name' => $variant->name,
                    'price' => (float) $variant->selling_price,
                    'quantity' => (int) $variant->quantity,
                    'in_stock' => (int) $variant->quantity > 0,
                ])->values()->all()
                : [],
            'short_description' => $this->plainText($product->short_description, 220),
            'warranty' => $product->warranty ? $this->plainText($product->warranty, 160) : null,
            'url' => rtrim((string) config('app.product_url'), '/').'/'.($product->slug ?: $product->id),
        ];
    }

    private function plainText(?string $html, int $limit): ?string
    {
        if (! $html) {
            return null;
        }

        return Str::of($html)->stripTags()->squish()->limit($limit)->toString();
    }
}
