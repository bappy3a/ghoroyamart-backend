<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'brand'])
            ->withCount('variants')
            ->withSum('variants as variant_quantity', 'quantity')
            ->withSum('variants as variant_purchase_value', 'purchase_price')
            ->latest()
            ->get()
            ->map(function (Product $product) {
                $stock = $product->variants_count > 0
                    ? (int) ($product->variant_quantity ?? 0)
                    : (int) ($product->quantity ?? 0);

                $alertLevel = (int) ($product->low_stock_alert ?: 5);
                $purchasePrice = (float) ($product->purchase_price ?? 0);
                $sellingPrice = (float) ($product->price ?? 0);

                $product->inventory_stock = $stock;
                $product->inventory_alert_level = $alertLevel;
                $product->inventory_cost_value = $stock * $purchasePrice;
                $product->inventory_retail_value = $stock * $sellingPrice;

                return $product;
            });

        $totalProducts = $products->count();
        $publishedProducts = $products->where('status', 'published')->count();
        $websiteProducts = $products
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where('product_location', 'store')
            ->count();
        $storeProducts = $products->where('product_location', 'store')->count();
        $warehouseProducts = $products->where('product_location', 'warehouse')->count();
        $hiddenProducts = $products->where('visibility', 'hidden')->count();
        $totalVariants = ProductVariant::count();
        $activeVariants = ProductVariant::where('is_active', true)->count();
        $totalStock = $products->sum('inventory_stock');
        $storeStock = $products->where('product_location', 'store')->sum('inventory_stock');
        $warehouseStock = $products->where('product_location', 'warehouse')->sum('inventory_stock');
        $outOfStockCount = $products->where('inventory_stock', '<=', 0)->count();
        $lowStockProducts = $products
            ->filter(fn (Product $product) => $product->inventory_stock > 0 && $product->inventory_stock <= $product->inventory_alert_level)
            ->sortBy('inventory_stock')
            ->values();
        $outOfStockProducts = $products
            ->where('inventory_stock', '<=', 0)
            ->sortBy('name')
            ->values();

        $inventoryCostValue = $products->sum('inventory_cost_value');
        $inventoryRetailValue = $products->sum('inventory_retail_value');
        $potentialMargin = $inventoryRetailValue - $inventoryCostValue;
        $lowStockRetailValue = $lowStockProducts->sum('inventory_retail_value');

        $categoryStock = $this->stockBy($products, 'category.name', 'Uncategorized');
        $brandStock = $this->stockBy($products, 'brand.name', 'No Brand');

        $topStockProducts = $products
            ->sortByDesc('inventory_stock')
            ->take(8)
            ->values();

        $recentSoldItems = OrderItem::with(['product', 'productVariant.values.value'])
            ->latest()
            ->take(8)
            ->get();

        return view('admin.inventory.dashboard', compact(
            'totalProducts',
            'publishedProducts',
            'websiteProducts',
            'storeProducts',
            'warehouseProducts',
            'hiddenProducts',
            'totalVariants',
            'activeVariants',
            'totalStock',
            'storeStock',
            'warehouseStock',
            'outOfStockCount',
            'lowStockProducts',
            'outOfStockProducts',
            'inventoryCostValue',
            'inventoryRetailValue',
            'potentialMargin',
            'lowStockRetailValue',
            'categoryStock',
            'brandStock',
            'topStockProducts',
            'recentSoldItems'
        ));
    }

    protected function stockBy(Collection $products, string $path, string $fallback): Collection
    {
        return $products
            ->groupBy(fn (Product $product) => data_get($product, $path) ?: $fallback)
            ->map(fn (Collection $items, string $name) => (object) [
                'name' => $name,
                'products_count' => $items->count(),
                'stock' => $items->sum('inventory_stock'),
                'retail_value' => $items->sum('inventory_retail_value'),
            ])
            ->sortByDesc('stock')
            ->take(8)
            ->values();
    }
}
