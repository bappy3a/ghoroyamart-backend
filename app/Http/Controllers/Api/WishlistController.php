<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductsCollection;
use App\Models\Product;
use App\Models\Wishlist;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    use ApiResponse;

    /**
     * List the authenticated user's wishlist products.
     */
    public function index(Request $request)
    {
        $productIds = $request->user()
            ->wishlists()
            ->orderByDesc('id')
            ->pluck('product_id');

        $products = $this->storefrontQuery()
            ->whereIn('id', $productIds)
            ->get()
            ->sortBy(fn (Product $product) => $productIds->search($product->id))
            ->values();

        return $this->success([
            'product_ids' => $productIds->map(fn ($id) => (int) $id)->values()->all(),
            'products' => (new ProductsCollection($products))->resolve(),
        ], null, 'Wishlist fetched successfully.');
    }

    /**
     * Add a product to the authenticated user's wishlist.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide a valid product.', $validator->errors(), null, 422);
        }

        $productId = (int) $request->input('product_id');
        $product = $this->storefrontQuery()->whereKey($productId)->first();

        if (! $product) {
            return $this->error('Product not found.', null, null, 404);
        }

        $wishlist = Wishlist::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return $this->success([
            'product_id' => (int) $product->id,
            'wishlisted' => true,
            'created' => $wishlist->wasRecentlyCreated,
        ], null, $wishlist->wasRecentlyCreated
            ? 'Product added to wishlist.'
            : 'Product is already in your wishlist.', $wishlist->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Toggle a product in the authenticated user's wishlist.
     */
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please provide a valid product.', $validator->errors(), null, 422);
        }

        $productId = (int) $request->input('product_id');
        $product = $this->storefrontQuery()->whereKey($productId)->first();

        if (! $product) {
            return $this->error('Product not found.', null, null, 404);
        }

        $existing = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return $this->success([
                'product_id' => (int) $product->id,
                'wishlisted' => false,
            ], null, 'Product removed from wishlist.');
        }

        Wishlist::query()->create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return $this->success([
            'product_id' => (int) $product->id,
            'wishlisted' => true,
        ], null, 'Product added to wishlist.', 201);
    }

    /**
     * Remove a product from the authenticated user's wishlist.
     */
    public function destroy(Request $request, int $productId)
    {
        $deleted = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->delete();

        if (! $deleted) {
            return $this->error('Wishlist item not found.', null, null, 404);
        }

        return $this->success([
            'product_id' => $productId,
            'wishlisted' => false,
        ], null, 'Product removed from wishlist.');
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
}
