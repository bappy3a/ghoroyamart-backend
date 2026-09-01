<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
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
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in(['product_wise', 'order_based'])],
            'discount_type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after_or_equal:valid_from'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'product_ids' => ['required_if:type,product_wise', 'array', 'min:1'],
            'product_ids.*' => ['exists:products,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'The code must contain only uppercase letters, numbers, hyphens, and underscores.',
            'valid_to.after_or_equal' => 'The valid to date must be after or equal to valid from date.',
            'product_ids.required_if' => 'Please select at least one product for product-wise coupon.',
            'product_ids.min' => 'Please select at least one product for product-wise coupon.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert discount_value based on discount_type
        if ($this->has('discount_value')) {
            $value = (float) $this->input('discount_value');
            
            if ($this->input('discount_type') === 'percentage') {
                // Ensure percentage is between 0 and 100
                if ($value > 100) {
                    $this->merge(['discount_value' => 100]);
                }
            }
        }

        // Set minimum_order_amount to 0 if not provided for order_based type
        if ($this->input('type') === 'order_based' && !$this->has('minimum_order_amount')) {
            $this->merge(['minimum_order_amount' => 0]);
        }
    }
}
