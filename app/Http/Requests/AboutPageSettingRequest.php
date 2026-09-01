<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AboutPageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_title' => ['required', 'string', 'max:255'],
            'breadcrumb_title' => ['required', 'string', 'max:255'],
            'breadcrumb_subtitle' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'max:4096'],

            'section_one_subtitle' => ['nullable', 'string', 'max:255'],
            'section_one_title' => ['nullable', 'string', 'max:255'],
            'section_one_content' => ['nullable', 'string'],
            'section_one_image' => ['nullable', 'image', 'max:4096'],

            'section_two_subtitle' => ['nullable', 'string', 'max:255'],
            'section_two_title' => ['nullable', 'string', 'max:255'],
            'section_two_content' => ['nullable', 'string'],
            'section_two_image' => ['nullable', 'image', 'max:4096'],

            'features_subtitle' => ['nullable', 'string', 'max:255'],
            'features_title' => ['nullable', 'string', 'max:255'],
            'features_description' => ['nullable', 'string'],
            'feature_one_title' => ['nullable', 'string', 'max:255'],
            'feature_one_description' => ['nullable', 'string'],
            'feature_two_title' => ['nullable', 'string', 'max:255'],
            'feature_two_description' => ['nullable', 'string'],
            'feature_three_title' => ['nullable', 'string', 'max:255'],
            'feature_three_description' => ['nullable', 'string'],

            'reviews_subtitle' => ['nullable', 'string', 'max:255'],
            'reviews_title' => ['nullable', 'string', 'max:255'],
            'reviews_description' => ['nullable', 'string'],
        ];
    }
}
