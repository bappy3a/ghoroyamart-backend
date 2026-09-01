<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductsCollection;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FlashSaleController extends Controller
{
    use ApiResponse;

    /**
     * Full flash-sale page payload: live + upcoming rounds with products.
     * Shape mirrors agonito-frontend/src/app/flash-sale.
     */
    public function index()
    {
        $deals = FlashDeal::query()
            ->where('is_active', true)
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('sort_order')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $rounds = $deals
            ->map(fn (FlashDeal $deal) => $this->transformDeal($deal))
            ->values()
            ->all();

        // Banner "up to X% off" uses the flash-deal discount from admin, not each product's own sale %.
        $maxDiscount = collect($rounds)
            ->max(fn (array $round) => (float) ($round['discount_percentage'] ?? 0)) ?? 0;

        return $this->success([
            'title' => 'Flash Sale',
            'description' => 'Hand-picked deals, ranked by savings. Once stock is gone, it\'s gone.',
            'max_discount' => (int) round($maxDiscount),
            'sorts' => [
                ['id' => 'discount', 'label' => 'Biggest discount'],
                ['id' => 'ending', 'label' => 'Almost gone'],
                ['id' => 'price-asc', 'label' => 'Price: low to high'],
                ['id' => 'price-desc', 'label' => 'Price: high to low'],
            ],
            'rounds' => $rounds,
        ], null, 'Flash sale page fetched successfully');
    }

    /**
     * Single flash deal by slug or id, with products.
     */
    public function show(string $id)
    {
        $query = FlashDeal::query()->where('is_active', true);

        $deal = ctype_digit($id)
            ? $query->where('id', (int) $id)->first()
            : $query->where('slug', $id)->first();

        if (! $deal) {
            return $this->error('Flash sale not found', null, null, 404);
        }

        return $this->success(
            $this->transformDeal($deal),
            null,
            'Flash sale fetched successfully'
        );
    }

    private function transformDeal(FlashDeal $deal): array
    {
        $now = now()->startOfDay();
        $start = optional($deal->start_date)?->copy()->startOfDay();
        $end = optional($deal->end_date)?->copy()->startOfDay();

        $status = 'ended';
        if ($start && $end) {
            if ($now->lt($start)) {
                $status = 'upcoming';
            } elseif ($now->lte($end)) {
                $status = 'live';
            }
        }

        $products = $this->productsForDeal($deal);

        return [
            'id' => (string) $deal->id,
            'slug' => $deal->slug,
            'label' => $status === 'live' ? 'Live now' : ($deal->title ?: 'Coming soon'),
            'note' => match ($status) {
                'live' => 'Ends soon',
                'upcoming' => 'Starts '.$start?->format('M j'),
                default => 'Ended',
            },
            'status' => $status,
            'title' => $deal->title,
            'description' => $deal->description,
            'banner_image' => $this->assetOrNull($deal->banner_image),
            'background_color' => $deal->background_color,
            'text_color' => $deal->text_color,
            'discount_percentage' => (float) $deal->discount_percentage,
            'start_date' => optional($deal->start_date)?->toDateString(),
            'end_date' => optional($deal->end_date)?->toDateString(),
            'products_count' => count($products),
            'products' => $products,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productsForDeal(FlashDeal $deal): array
    {
        $productIds = is_array($deal->product_ids) ? $deal->product_ids : [];
        if ($productIds === []) {
            return [];
        }

        /** @var Collection<int, Product> $products */
        $products = $this->storefrontQuery()
            ->with(['category.parent', 'brand'])
            ->whereIn('id', $productIds)
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $productIds, true))
            ->values();

        $dealDiscount = (float) $deal->discount_percentage;

        return collect((new ProductsCollection($products))->resolve())
            ->map(function (array $p) use ($dealDiscount) {
                $sold = (int) ($p['sold'] ?? 0);
                $stock = (int) ($p['stock'] ?? 0);
                $total = $sold + $stock;
                $p['claimed'] = $total === 0 ? 100 : min(97, (int) round(($sold / $total) * 100));

                // Flash-deal % from admin always wins for products in this round.
                if ($dealDiscount > 0) {
                    $regular = (float) ($p['regular_price'] ?? $p['price'] ?? 0);
                    if ($regular <= 0) {
                        $regular = (float) ($p['price'] ?? 0);
                    }
                    $p['regular_price'] = $regular;
                    $p['discount_percentage'] = $dealDiscount;
                    $p['price'] = round($regular * (1 - $dealDiscount / 100), 2);
                    $p['is_discounted'] = true;
                }

                return $p;
            })
            ->values()
            ->all();
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
}
