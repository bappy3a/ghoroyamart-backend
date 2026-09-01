<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SliderRequest extends FormRequest
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
     *
     * Field mapping (admin → storefront Hero):
     * subtitle → eyebrow, title → title, description → copy,
     * button_text → cta, button_link → cta href, image → image
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'subtitle' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'button_text' => ['nullable', 'string', 'max:150'],
            'button_link' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'image' => [$isCreate ? 'required' : 'nullable', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subtitle' => 'eyebrow',
            'description' => 'copy',
            'button_text' => 'CTA text',
            'button_link' => 'CTA link',
            'image' => 'hero image',
        ];
    }
}
