<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => [
                'nullable',
                'string',
                'max:191',
            ],
            'landing_page_slug' => [
                'nullable',
                'string',
                'max:191',
                'alpha_dash',
                Rule::unique('products', 'landing_page_slug')->ignore($productId),
            ],
            'sku' => [
                'nullable',
                'string',
                'max:191',
                'not_regex:/\s/',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'how_to_use' => ['nullable', 'string'],
            'good_to_know' => ['nullable', 'string'],
            'warranty' => ['nullable', 'string', 'max:191'],
            'status' => ['required', 'string', Rule::in(['Published', 'Draft', 'Archived', 'Scheduled', 'draft', 'published', 'archived', 'scheduled'])],
            'visibility' => ['nullable', 'string', Rule::in(['Public', 'Hidden', 'public', 'hidden'])],
            'published_at' => ['nullable', 'date'],
            'thumbnail_image' => ['nullable', 'image', 'max:5120'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'max:5120'],
            'existing_gallery_images' => ['nullable', 'array'],
            'existing_gallery_images.*' => ['string', 'max:500'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'stock_status' => ['nullable', 'string', Rule::in(['in_stock', 'out_of_stock', 'pre_order'])],
            'product_location' => ['nullable', 'string', Rule::in(['store', 'warehouse'])],
            'unit' => ['nullable', 'string', 'max:20'],
            'regular_price' => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'num_of_sale' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'meta_title' => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'video_media' => ['nullable', 'url', 'max:500'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:0'],
            'maximum_order_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_start_date' => ['nullable', 'date'],
            'discount_end_date' => ['nullable', 'date', 'after_or_equal:discount_start_date'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_new' => ['sometimes', 'boolean'],
            'is_best_seller' => ['sometimes', 'boolean'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:191', 'not_regex:/\s/', 'distinct'],
            'variants.*.quantity' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.selling_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:5120'],
            'variants.*.attribute_value_ids' => ['required_with:variants', 'array', 'min:1'],
            'variants.*.attribute_value_ids.*' => ['required', 'integer', 'exists:variant_attribute_values,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.not_regex' => 'The SKU must not contain spaces.',
            'variants.*.sku.not_regex' => 'The variant SKU must not contain spaces.',
        ];
    }
}
