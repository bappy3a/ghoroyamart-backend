<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use App\Models\VariantAttributeValue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'sku']);

        $variants = ProductVariant::with(['product', 'values.attribute', 'values.value'])
            ->when($request->input('product_id'), fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('sku', 'like', '%'.$search.'%')
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20)
            ->appends($request->query());

        return view('admin.product-variants.index', compact('products', 'variants'));
    }

    public function create(Request $request): View
    {
        return view('admin.product-variants.create', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'regular_price', 'purchase_price']),
            'attributes' => VariantAttribute::where('is_active', true)->with('values')->orderBy('name')->get(),
            'selectedProductId' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:191', 'not_regex:/\s/', 'distinct', 'unique:product_variants,sku'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'variants.*.selling_price' => ['required', 'numeric', 'min:0'],
            'variants.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:5120'],
            'variants.*.attribute_value_ids' => ['required', 'array', 'min:1'],
            'variants.*.attribute_value_ids.*' => ['required', 'integer', 'exists:variant_attribute_values,id'],
        ], [
            'variants.*.sku.not_regex' => 'The variant SKU must not contain spaces.',
        ]);

        DB::transaction(function () use ($data, $request) {
            $seenHashes = [];

            foreach ($data['variants'] as $variantKey => $variantData) {
                $attributeValues = $this->loadAttributeValues($variantData['attribute_value_ids']);
                $combinationHash = $this->combinationHash($attributeValues);

                if (in_array($combinationHash, $seenHashes, true)) {
                    throw ValidationException::withMessages([
                        'variants' => 'Duplicate variant combinations were submitted.',
                    ]);
                }

                if (ProductVariant::where('product_id', $data['product_id'])->where('combination_hash', $combinationHash)->exists()) {
                    throw ValidationException::withMessages([
                        'variants' => 'One or more selected combinations already exist for this product.',
                    ]);
                }

                $seenHashes[] = $combinationHash;

                $variant = ProductVariant::create([
                    'product_id' => $data['product_id'],
                    'sku' => $variantData['sku'],
                    'combination_hash' => $combinationHash,
                    'quantity' => (int) $variantData['quantity'],
                    'selling_price' => (float) $variantData['selling_price'],
                    'purchase_price' => ($variantData['purchase_price'] ?? null) !== null && ($variantData['purchase_price'] ?? '') !== ''
                        ? (float) $variantData['purchase_price']
                        : null,
                    'image' => $request->hasFile("variants.{$variantKey}.image")
                        ? upload_webp_image($request->file("variants.{$variantKey}.image"), 'uploads/products/variants', 80, true)
                        : null,
                    'is_active' => true,
                ]);

                $this->syncVariantValues($variant, $attributeValues);
            }
        });

        flash_message('Product variants created successfully!');

        return redirect()->route('product-variants.index', ['product_id' => $data['product_id']]);
    }

    public function edit(ProductVariant $productVariant): View
    {
        $productVariant->load(['product', 'values.attribute', 'values.value']);

        return view('admin.product-variants.edit', [
            'productVariant' => $productVariant,
            'attributes' => VariantAttribute::where('is_active', true)->with('values')->orderBy('name')->get(),
            'selectedValueIds' => $productVariant->values->pluck('variant_attribute_value_id')->map(fn ($id) => (int) $id)->all(),
        ]);
    }

    public function update(Request $request, ProductVariant $productVariant): RedirectResponse
    {
        $request->merge([
            'attribute_value_ids' => collect($request->input('attribute_value_ids', []))
                ->filter(fn ($valueId) => $valueId !== null && $valueId !== '')
                ->values()
                ->all(),
        ]);

        $data = $request->validate([
            'sku' => ['required', 'string', 'max:191', 'not_regex:/\s/', Rule::unique('product_variants', 'sku')->ignore($productVariant->id)],
            'quantity' => ['required', 'integer', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
            'attribute_value_ids' => ['required', 'array', 'min:1'],
            'attribute_value_ids.*' => ['required', 'integer', 'exists:variant_attribute_values,id'],
        ], [
            'sku.not_regex' => 'The variant SKU must not contain spaces.',
        ]);

        DB::transaction(function () use ($data, $request, $productVariant) {
            $attributeValues = $this->loadAttributeValues($data['attribute_value_ids']);
            $combinationHash = $this->combinationHash($attributeValues);

            if (ProductVariant::where('product_id', $productVariant->product_id)
                ->where('combination_hash', $combinationHash)
                ->where('id', '!=', $productVariant->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'attribute_value_ids' => 'This variant combination already exists for the product.',
                ]);
            }

            $payload = [
                'sku' => $data['sku'],
                'combination_hash' => $combinationHash,
                'quantity' => (int) $data['quantity'],
                'selling_price' => (float) $data['selling_price'],
                'purchase_price' => ($data['purchase_price'] ?? null) !== null && ($data['purchase_price'] ?? '') !== ''
                    ? (float) $data['purchase_price']
                    : null,
                'is_active' => $request->boolean('is_active'),
            ];

            if ($request->hasFile('image')) {
                $payload['image'] = upload_webp_image($request->file('image'), 'uploads/products/variants', 80, true);
            }

            $productVariant->update($payload);

            $this->syncVariantValues($productVariant, $attributeValues);
        });

        flash_message('Product variant updated successfully!');

        return redirect()->route('product-variants.index', ['product_id' => $productVariant->product_id]);
    }

    public function destroy(ProductVariant $productVariant): RedirectResponse
    {
        $productId = $productVariant->product_id;
        $productVariant->delete();

        flash_message('Product variant deleted successfully!');

        return redirect()->route('product-variants.index', ['product_id' => $productId]);
    }

    protected function loadAttributeValues(array $valueIds): Collection
    {
        $ids = collect($valueIds)->map(fn ($id) => (int) $id)->unique()->values();
        $values = VariantAttributeValue::with('attribute')->whereIn('id', $ids)->get();

        if ($values->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => 'Invalid variant attribute value selected.',
            ]);
        }

        if ($values->pluck('variant_attribute_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => 'Select only one value for each variant attribute per variant.',
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
