<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductsCollection extends ResourceCollection
{
    public static $wrap = null;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request)
    {
        return $this->collection->map(function ($item) {
            $category = $item->category;
            $brand = $item->brand;
            $images = collect(is_array($item->images) ? $item->images : [])
                ->filter()
                ->map(fn ($image) => api_asset($image))
                ->values()
                ->all();
            $thumbnailImage = $item->thumbnail_image ? api_asset($item->thumbnail_image) : null;
            $variantGroups = product_variant_groups($item);

            return [
                'id' => $item->id,
                'slug' => $item->slug,
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon ? api_asset($category->icon) : null,
                    'parent' => $category->relationLoaded('parent') && $category->parent ? [
                        'id' => $category->parent->id,
                        'name' => $category->parent->name,
                        'slug' => $category->parent->slug,
                    ] : null,
                ] : null,
                'brand' => $brand ? [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'logo' => $brand->logo ? api_asset($brand->logo) : null,
                ] : null,
                'name' => $item->name,
                'sku' => $item->sku,
                'price' => $item->price,
                'regular_price' => $item->regular_price,
                'discount_percentage' => $item->discount_percentage,
                'discount_amount' => $item->discount_amount,
                'is_discounted' => $item->is_discounted,
                'is_featured' => $item->is_featured,
                'is_new' => $item->is_new,
                'is_best_seller' => $item->is_best_seller,
                'reviews_avg' => $item->reviews_avg,
                'reviews_count' => $item->num_of_reviews,
                'sold' => $item->num_of_sale,
                'stock' => $item->quantity,
                'stock_status' => $item->stock_status,
                'thumbnail_image' => $thumbnailImage,
                'hover_image' => $images[0] ?? $thumbnailImage,
                'images' => $images,
                'has_variants' => count($variantGroups) > 0,
                'variant_groups' => $variantGroups,
                'variants' => $item->variants
                    ->where('is_active', true)
                    ->values()
                    ->map(function ($variant) {
                        $attributes = $variant->relationLoaded('values')
                            ? $variant->values
                            : $variant->values()->with(['attribute', 'value'])->get();

                        return [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'name' => $variant->name,
                            'image' => $variant->image ? api_asset($variant->image) : null,
                            'selling_price' => $variant->selling_price,
                            'quantity' => $variant->quantity,
                            'attributes' => $attributes->map(fn ($row) => [
                                'attribute' => $row->attribute?->name,
                                'value' => $row->value?->value,
                            ])->values()->all(),
                        ];
                    })->all(),
            ];
        })->values()->all();
    }
}
