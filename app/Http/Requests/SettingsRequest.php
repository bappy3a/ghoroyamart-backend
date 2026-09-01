<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        // Get all settings keys from request
        $settings = $this->except(['_token', '_method']);

        foreach ($settings as $key => $value) {
            if (in_array($key, ['payment_gateway_existing', 'home_instagram_existing'], true)) {
                $rules[$key] = ['nullable', 'array'];
                $rules[$key . '.*'] = ['nullable', 'string', 'max:500'];
                continue;
            }

            if (in_array($key, ['payment_gateway_images', 'home_instagram_images'], true)) {
                $rules[$key] = ['nullable', 'array'];
                $rules[$key . '.*'] = ['nullable', 'image', 'max:2048'];
                continue;
            }

            // Check if it's a file upload
            if ($this->hasFile($key)) {
                $rules[$key] = ['nullable', 'image', 'max:2048']; // Max 2MB
            } elseif ($key === 'frontend_menu_items') {
                $rules[$key] = ['nullable', 'json'];
            } elseif (str_contains($key, 'email')) {
                $rules[$key] = ['nullable', 'email'];
            } elseif (str_contains($key, 'url') || str_starts_with($key, 'social_')) {
                // Allow empty strings for URLs
                $rules[$key] = ['nullable', 'string', function ($attribute, $value, $fail) {
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('The ' . $attribute . ' must be a valid URL.');
                    }
                }];
            } elseif (str_contains($key, 'delivery_charge')) {
                // Validation for delivery charge fields
                $rules[$key] = ['nullable', 'numeric', 'min:0'];
            } else {
                // Basic validation - can be extended based on setting type
                $rules[$key] = ['nullable'];
            }
        }

        return $rules;
    }
}
