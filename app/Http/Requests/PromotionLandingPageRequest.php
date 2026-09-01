<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $landingPageId = $this->route('promotion_landing_page')?->id;

        return [
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer', 'exists:products,id'],
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', 'alpha_dash', Rule::unique('promotion_landing_pages', 'slug')->ignore($landingPageId)],
            'headline' => ['nullable', 'string', 'max:191'],
            'subheadline' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['nullable', 'image', 'max:5120'],
            'gallery_alt_text' => ['nullable', 'array'],
            'gallery_alt_text.*' => ['nullable', 'string', 'max:255'],
            'existing_gallery_ids' => ['nullable', 'array'],
            'existing_gallery_ids.*' => ['integer', 'exists:promotion_gallery_images,id'],
            'existing_gallery_alt_text' => ['nullable', 'array'],
            'existing_gallery_alt_text.*' => ['nullable', 'string', 'max:255'],
            'delete_gallery_ids' => ['nullable', 'array'],
            'delete_gallery_ids.*' => ['integer', 'exists:promotion_gallery_images,id'],
        ];
    }
}
