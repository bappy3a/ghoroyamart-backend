<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

class OrderStockService
{
    public function deduct(int $productId, ?int $variantId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();
        $variant = null;

        if ($variantId) {
            $variant = ProductVariant::whereKey($variantId)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                throw new \RuntimeException("The selected variant for {$product->name} is no longer available.");
            }

            if ($variant->quantity > 0 && $variant->quantity < $quantity) {
                throw new \RuntimeException("Only {$variant->quantity} item(s) of {$product->name} are available.");
            }
        } elseif ($product->quantity > 0 && $product->quantity < $quantity) {
            throw new \RuntimeException("Only {$product->quantity} item(s) of {$product->name} are available.");
        }

        // A zero quantity follows the application's existing unlimited-stock convention.
        if ($product->quantity > 0) {
            $product->decrement('quantity', min($quantity, $product->quantity));
            $product->refresh();

            if ($product->quantity <= 0 && $product->stock_status !== 'out_of_stock') {
                $product->update(['stock_status' => 'out_of_stock']);
            }
        }

        if ($variant && $variant->quantity > 0) {
            $variant->decrement('quantity', $quantity);
        }
    }
}
