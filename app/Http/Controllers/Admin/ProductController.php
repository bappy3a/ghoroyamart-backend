<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\VariantAttribute;
use App\Models\VariantAttributeValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private const HOME_CACHE_KEY = 'api.home';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = Category::where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $brands = Brand::where('status', true)
            ->orderBy('name')
            ->get();

        // Get total counts for badges
        $totalCount = Product::count();
        $publishedCount = Product::where('status', 'published')->count();
        $draftCount = Product::where('status', 'draft')->count();
        $warehouseCount = Product::where('product_location', 'warehouse')->count();
        $storeCount = Product::where('product_location', 'store')->count();
        $lowStockCount = $this->inventoryQuery()
            ->whereRaw($this->stockExpression().' > 0')
            ->whereRaw($this->stockExpression().' <= COALESCE(NULLIF(products.low_stock_alert, 0), 5)')
            ->count();

        $products = $this->inventoryQuery()
            ->with('category', 'brand')
            ->withCount('variants')
            ->when($request->input('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->input('search').'%');
            })
            ->when($request->input('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })
            ->when($request->input('brand_id'), function ($query) use ($request) {
                $query->where('brand_id', $request->input('brand_id'));
            })
            ->when($request->input('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->input('visibility'), function ($query) use ($request) {
                $query->where('visibility', $request->input('visibility'));
            })
            ->when($request->input('product_location'), function ($query) use ($request) {
                $query->where('product_location', $request->input('product_location'));
            })
            ->when($request->input('stock_filter'), function ($query) use ($request) {
                $stockExpression = $this->stockExpression();

                match ($request->input('stock_filter')) {
                    'low_stock' => $query
                        ->whereRaw($stockExpression.' > 0')
                        ->whereRaw($stockExpression.' <= COALESCE(NULLIF(products.low_stock_alert, 0), 5)'),
                    'out_of_stock' => $query->whereRaw($stockExpression.' <= 0'),
                    'in_stock' => $query->whereRaw($stockExpression.' > 0'),
                    default => null,
                };
            })
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        return view('admin.products.index', compact(
            'categories',
            'brands',
            'totalCount',
            'publishedCount',
            'draftCount',
            'warehouseCount',
            'storeCount',
            'lowStockCount',
            'products'
        ));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.products.create', [
            'brands' => Brand::where('status', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
            'variantAttributes' => $this->variantAttributes(),
            'inlineVariants' => collect(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $product = Product::create($this->payloadFrom($request));
            $this->syncProductVariants($product, $request->input('variants', []), $request);
        });

        Cache::forget(self::HOME_CACHE_KEY);
        flash_message('Product created successfully!');

        return redirect()->route('products.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load('variants.values.attribute', 'variants.values.value');

        return view('admin.products.edit', [
            'product' => $product,
            'brands' => Brand::where('status', true)->orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'variantAttributes' => $this->variantAttributes(),
            'inlineVariants' => $this->inlineVariantPayload($product),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($this->payloadFrom($request, $product));
            $this->syncProductVariants($product, $request->input('variants', []), $request);
        });

        Cache::forget(self::HOME_CACHE_KEY);
        flash_message('Product updated successfully!');

        return redirect()->route('products.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $product->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            flash_message('This product cannot be deleted because it is used in existing orders.', 'error');

            return redirect()->route('products.index');
        }

        Cache::forget(self::HOME_CACHE_KEY);
        flash_message('Product deleted successfully!');

        return redirect()->route('products.index');
    }

    public function bulkUpdateLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'location' => ['required', 'string', 'in:store,warehouse'],
        ], [
            'product_ids.required' => 'Please select at least one product.',
            'product_ids.min' => 'Please select at least one product.',
            'location.required' => 'Please choose a location.',
        ]);

        $productIds = collect($data['product_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $updates = [
            'product_location' => $data['location'],
            'visibility' => $data['location'] === 'warehouse' ? 'hidden' : 'public',
        ];

        $productCount = Product::whereKey($productIds)->count();
        Product::whereKey($productIds)->update($updates);

        Cache::forget(self::HOME_CACHE_KEY);

        $locationLabel = $data['location'] === 'warehouse' ? 'warehouse' : 'store';
        flash_message($productCount.' product'.($productCount === 1 ? '' : 's').' moved to '.$locationLabel.' successfully!');

        return back();
    }

    /**
     * Duplicate a product and open the edit page.
     */
    public function duplicate(Product $product): RedirectResponse
    {
        $duplicate = $product->replicate();

        $duplicateName = $this->buildDuplicateName($product->name);
        $duplicate->name = $duplicateName;
        $duplicate->slug = $this->uniqueSlug(Str::slug($duplicateName));
        $duplicate->sku = $this->uniqueSku($product->sku, $duplicateName);

        $duplicate->status = 'draft';
        $duplicate->published_at = null;
        $duplicate->num_of_sale = 0;
        $duplicate->num_of_views = 0;
        $duplicate->num_of_reviews = 0;
        $duplicate->reviews_avg = 0;
        $duplicate->approved_by_id = null;
        $duplicate->deleted_by_id = null;
        $duplicate->updated_by_id = null;

        if (Auth::check()) {
            $duplicate->created_by_id = Auth::id();
        }

        $duplicate->save();

        $product->load('variants.values');

        foreach ($product->variants as $variant) {
            $duplicateVariant = $variant->replicate();
            $duplicateVariant->product_id = $duplicate->id;
            $duplicateVariant->sku = $this->uniqueVariantSku($variant->sku);
            $duplicateVariant->save();

            foreach ($variant->values as $variantValue) {
                $duplicateVariant->values()->create([
                    'variant_attribute_id' => $variantValue->variant_attribute_id,
                    'variant_attribute_value_id' => $variantValue->variant_attribute_value_id,
                ]);
            }
        }

        flash_message('Product duplicated successfully!');

        return redirect()->route('products.edit', $duplicate->id);
    }

    /**
     * Prepare payload from request data
     */
    protected function payloadFrom(ProductRequest $request, ?Product $product = null): array
    {
        $data = [];

        // Basic information
        $data['name'] = $request->input('name');
        $baseSlug = $request->filled('slug') ? $request->input('slug') : Str::slug($data['name']);
        $data['slug'] = $this->uniqueSlug($baseSlug, $product?->id);
        $data['sku'] = $this->skuFromRequest($request, $product);

        // Description fields
        $data['short_description'] = $request->input('short_description');
        $data['description'] = $request->input('description');

        // Status mapping (form uses 'Published', 'Draft', 'Scheduled')
        $status = $request->input('status', 'Published');
        $statusMap = [
            'Published' => 'published',
            'Draft' => 'draft',
            'Archived' => 'archived',
            'Scheduled' => 'scheduled',
            'published' => 'published',
            'draft' => 'draft',
            'archived' => 'archived',
            'scheduled' => 'scheduled',
        ];
        $data['status'] = $statusMap[$status] ?? 'draft';

        // Visibility mapping (form uses 'Public', 'Hidden')
        $visibility = $request->input('visibility', 'Public');
        $visibilityMap = [
            'Public' => 'public',
            'Hidden' => 'hidden',
            'public' => 'public',
            'hidden' => 'hidden',
        ];
        $data['visibility'] = $visibilityMap[$visibility] ?? 'public';

        // Handle published_at date
        if ($request->filled('published_at')) {
            $data['published_at'] = $request->input('published_at');
        } elseif ($data['status'] === 'scheduled') {
            // If scheduled but no date provided, set to now
            $data['published_at'] = now();
        } else {
            $data['published_at'] = null;
        }

        // Handle thumbnail image upload
        if ($request->hasFile('thumbnail_image')) {
            $data['thumbnail_image'] = upload_webp_image($request->file('thumbnail_image'), 'uploads/products', 80, true);
            // Copy thumbnail_image to meta_image
            $data['meta_image'] = $data['thumbnail_image'];
        } elseif ($product && $product->getRawOriginal('thumbnail_image')) {
            // Keep existing thumbnail_image if not uploading new one
            $data['thumbnail_image'] = $product->getRawOriginal('thumbnail_image');
            $data['meta_image'] = $product->getRawOriginal('meta_image') ?? $product->getRawOriginal('thumbnail_image');
        }

        // Handle gallery images upload + keep/delete existing images on edit
        $galleryImages = [];
        if ($product) {
            $existingImages = $request->input('existing_gallery_images', []);
            $galleryImages = is_array($existingImages) ? array_values(array_filter($existingImages)) : [];

            $oldImages = $product->gallery_images ?? [];
            $removedImages = array_diff($oldImages, $galleryImages);
            foreach ($removedImages as $removedImage) {
                delete_uploaded_file($removedImage);
            }
        }

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $galleryImages[] = upload_webp_image($image, 'uploads/products/gallery', 80, true);
            }
        }

        if (!empty($galleryImages)) {
            $data['images'] = json_encode(array_values($galleryImages));
        } elseif ($product) {
            $data['images'] = json_encode([]);
        }

        // Inventory/Stock fields
        $data['quantity'] = (int) $request->input('quantity', 0);
        $data['stock_status'] = $request->input('stock_status', $data['quantity'] > 0 ? 'in_stock' : 'out_of_stock');
        $data['product_location'] = $request->input('product_location', 'store');

        if ($data['product_location'] === 'warehouse') {
            $data['visibility'] = 'hidden';
        }

        // Pricing fields
        $regularPrice = (float) $request->input('regular_price', 0);
        $data['regular_price'] = $regularPrice;

        $discountPercentage = (float) $request->input('discount_percentage', 0);
        $data['discount_percentage'] = $discountPercentage;

        // Calculate price after discount
        if ($discountPercentage > 0) {
            $data['price'] = $regularPrice - ($regularPrice * $discountPercentage / 100);
            $data['discount_amount'] = $regularPrice * $discountPercentage / 100;
            $data['is_discounted'] = true;
        } else {
            $data['price'] = $regularPrice;
            $data['discount_amount'] = 0;
            $data['is_discounted'] = false;
        }

        // Statistics fields - preserve existing values on update
        if ($product) {
            $data['num_of_sale'] = (int) $request->input('num_of_sale', $product->num_of_sale ?? 0);
            $data['num_of_views'] = $product->num_of_views ?? 0;
            $data['num_of_reviews'] = $product->num_of_reviews ?? 0;
            $data['reviews_avg'] = $product->reviews_avg ?? 0;
        } else {
            $data['num_of_sale'] = (int) $request->input('num_of_sale', 0);
            $data['num_of_views'] = 0;
            $data['num_of_reviews'] = 0;
            $data['reviews_avg'] = 0;
        }

        // Category and Brand
        $data['category_id'] = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $data['brand_id'] = $request->filled('brand_id') ? (int) $request->input('brand_id') : null;

        // Unit
        $data['unit'] = $request->input('unit', 'pcs');

        // Inventory fields
        $data['minimum_order_quantity'] = (int) $request->input('minimum_order_quantity', 0);
        $data['maximum_order_quantity'] = (int) $request->input('maximum_order_quantity', 0);
        $data['low_stock_alert'] = (int) $request->input('low_stock_alert', 0);

        // Pricing fields
        $data['purchase_price'] = $request->filled('purchase_price') ? (float) $request->input('purchase_price') : null;
        $data['tax_rate'] = $request->filled('tax_rate') ? (float) $request->input('tax_rate') : null;
        $data['discount_start_date'] = $request->filled('discount_start_date') ? $request->input('discount_start_date') : null;
        $data['discount_end_date'] = $request->filled('discount_end_date') ? $request->input('discount_end_date') : null;

        // Media fields
        $data['video_media'] = $request->input('video_media');

        // Additional content fields
        $data['how_to_use'] = $request->input('how_to_use');
        $data['good_to_know'] = $request->input('good_to_know');
        $data['warranty'] = $request->input('warranty');

        // Feature flags
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_new'] = $request->boolean('is_new', false);
        $data['is_best_seller'] = $request->boolean('is_best_seller', false);

        // SEO Meta fields
        $data['meta_title'] = $request->input('meta_title');
        $data['meta_description'] = $request->input('meta_description');
        $data['meta_keywords'] = $request->input('meta_keywords');

        // Set created_by_id if user is authenticated (only on create)
        if (!$product && Auth::check()) {
            $data['created_by_id'] = Auth::id();
        }

        // Set updated_by_id if user is authenticated (on update)
        if ($product && Auth::check()) {
            $data['updated_by_id'] = Auth::id();
        }

        return $data;
    }

    protected function inventoryQuery()
    {
        $variantInventory = ProductVariant::query()
            ->select('product_id')
            ->selectRaw('SUM(quantity) as variant_quantity')
            ->selectRaw('COUNT(*) as variant_inventory_count')
            ->groupBy('product_id');

        return Product::query()
            ->leftJoinSub($variantInventory, 'variant_inventory', function ($join) {
                $join->on('products.id', '=', 'variant_inventory.product_id');
            })
            ->select('products.*')
            ->selectRaw($this->stockExpression().' as inventory_stock');
    }

    protected function stockExpression(): string
    {
        return 'CASE WHEN COALESCE(variant_inventory.variant_inventory_count, 0) > 0 THEN COALESCE(variant_inventory.variant_quantity, 0) ELSE products.quantity END';
    }

    protected function buildDuplicateName(string $name): string
    {
        $baseName = $name;
        $candidate = $baseName.' (Copy)';
        $counter = 2;

        while (Product::where('name', $candidate)->exists()) {
            $candidate = $baseName.' (Copy '.$counter.')';
            $counter++;
        }

        return $candidate;
    }

    protected function uniqueSlug(string $baseSlug, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::substr($baseSlug, 0, 191);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)
            ->when($ignoreProductId !== null, fn ($query) => $query->whereKeyNot($ignoreProductId))
            ->exists()) {
            $suffix = '-'.str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
            $slug = Str::substr($baseSlug, 0, 191 - strlen($suffix)).$suffix;
            $counter++;
        }

        return $slug;
    }

    protected function skuFromRequest(ProductRequest $request, ?Product $product = null): string
    {
        $sku = trim((string) $request->input('sku', ''));

        if ($sku !== '') {
            return $sku;
        }

        return $this->generateUniqueSku($request->input('name'), $product?->id);
    }

    protected function generateUniqueSku(?string $source, ?int $ignoreProductId = null): string
    {
        $base = $this->skuBaseFrom($source);
        $candidate = $base;
        $counter = 1;

        while ($this->productSkuExists($candidate, $ignoreProductId)) {
            $suffix = '-'.$counter;
            $candidate = substr($base, 0, 191 - strlen($suffix)).$suffix;
            $counter++;
        }

        return $candidate;
    }

    protected function skuBaseFrom(?string $value): string
    {
        $base = Str::upper(Str::slug((string) $value));

        return $base !== '' ? substr($base, 0, 180) : 'PRODUCT';
    }

    protected function productSkuExists(string $sku, ?int $ignoreProductId = null): bool
    {
        return Product::where('sku', $sku)
            ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
            ->exists();
    }

    protected function uniqueSku(?string $sku, ?string $fallbackSource = null): string
    {
        $baseSku = $this->skuBaseFrom($sku);

        if (trim((string) $sku) === '') {
            return $this->generateUniqueSku($fallbackSource);
        }

        $base = substr($baseSku, 0, 185).'-copy';
        $candidate = $base;
        $counter = 1;

        while ($this->productSkuExists($candidate)) {
            $suffix = '-'.$counter;
            $candidate = substr($base, 0, 191 - strlen($suffix)).$suffix;
            $counter++;
        }

        return $candidate;
    }

    protected function uniqueVariantSku(string $sku): string
    {
        $base = $sku.'-copy';
        $candidate = $base;
        $counter = 1;

        while (ProductVariant::where('sku', $candidate)->exists()) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    protected function variantAttributes(): Collection
    {
        return VariantAttribute::where('is_active', true)
            ->with('values')
            ->orderBy('name')
            ->get();
    }

    protected function inlineVariantPayload(Product $product): Collection
    {
        return $product->variants
            ->sortBy('sort_order')
            ->values()
            ->map(function (ProductVariant $variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'image' => $variant->image,
                    'quantity' => $variant->quantity,
                    'selling_price' => $variant->selling_price,
                    'purchase_price' => $variant->purchase_price,
                    'is_active' => $variant->is_active,
                    'attribute_value_ids' => $variant->values
                        ->pluck('variant_attribute_value_id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all(),
                ];
            });
    }

    protected function syncProductVariants(Product $product, array $variants, Request $request): void
    {
        $submittedIds = [];
        $seenHashes = [];
        $sortOrder = 0;

        foreach ($variants as $variantKey => $variantData) {
            $variantId = filled($variantData['id'] ?? null) ? (int) $variantData['id'] : null;

            if ($variantId && ! $product->variants()->whereKey($variantId)->exists()) {
                throw ValidationException::withMessages([
                    'variants' => 'Invalid product variant submitted.',
                ]);
            }

            $sku = trim((string) ($variantData['sku'] ?? ''));

            if (ProductVariant::where('sku', $sku)->when($variantId, fn ($query) => $query->where('id', '!=', $variantId))->exists()) {
                throw ValidationException::withMessages([
                    "variants.{$variantKey}.sku" => 'This variant SKU is already used.',
                ]);
            }

            $attributeValues = $this->loadAttributeValues($variantData['attribute_value_ids'] ?? []);
            $combinationHash = $this->combinationHash($attributeValues);

            if (in_array($combinationHash, $seenHashes, true)) {
                throw ValidationException::withMessages([
                    'variants' => 'Duplicate variant combinations were submitted.',
                ]);
            }

            if (ProductVariant::where('product_id', $product->id)
                ->where('combination_hash', $combinationHash)
                ->when($variantId, fn ($query) => $query->where('id', '!=', $variantId))
                ->exists()) {
                throw ValidationException::withMessages([
                    'variants' => 'One or more variant combinations already exist for this product.',
                ]);
            }

            $seenHashes[] = $combinationHash;

            $payload = [
                'sku' => $sku,
                'combination_hash' => $combinationHash,
                'quantity' => (int) ($variantData['quantity'] ?? 0),
                'selling_price' => (float) ($variantData['selling_price'] ?? 0),
                'purchase_price' => ($variantData['purchase_price'] ?? null) !== null && ($variantData['purchase_price'] ?? '') !== ''
                    ? (float) $variantData['purchase_price']
                    : null,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ];

            if ($request->hasFile("variants.{$variantKey}.image")) {
                $payload['image'] = upload_webp_image($request->file("variants.{$variantKey}.image"), 'uploads/products/variants', 80, true);
            } elseif (! empty($variantData['existing_image'])) {
                $payload['image'] = $variantData['existing_image'];
            }

            if ($variantId) {
                $variant = $product->variants()->whereKey($variantId)->firstOrFail();
                $variant->update($payload);
            } else {
                $variant = $product->variants()->create($payload);
            }

            $submittedIds[] = $variant->id;
            $this->syncVariantValues($variant, $attributeValues);
            $sortOrder++;
        }

        $product->variants()
            ->when(! empty($submittedIds), fn ($query) => $query->whereNotIn('id', $submittedIds))
            ->delete();
    }

    protected function loadAttributeValues(array $valueIds): Collection
    {
        $ids = collect($valueIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $values = VariantAttributeValue::with('attribute')->whereIn('id', $ids)->get();

        if ($values->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'variants' => 'Invalid variant attribute value selected.',
            ]);
        }

        if ($values->pluck('variant_attribute_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'Select only one value for each variant attribute per variant.',
            ]);
        }

        return $values->sortBy('variant_attribute_id')->values();
    }

    protected function combinationHash(Collection $attributeValues): string
    {
        return $attributeValues
            ->map(fn (VariantAttributeValue $value) => $value->variant_attribute_id.':'.$value->id)
            ->implode('|');
    }

    protected function syncVariantValues(ProductVariant $variant, Collection $attributeValues): void
    {
        $variant->values()->delete();

        foreach ($attributeValues as $attributeValue) {
            $variant->values()->create([
                'variant_attribute_id' => $attributeValue->variant_attribute_id,
                'variant_attribute_value_id' => $attributeValue->id,
            ]);
        }
    }
}
