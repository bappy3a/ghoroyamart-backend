<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;

class SettingController extends Controller
{
    use ApiResponse;

    /**
     * Public storefront settings (menu, contact, social, footer, payments).
     */
    public function index()
    {
        $settings = Setting::getAllKeyValue();

        $paymentImages = json_decode($settings['payment_gateway_images'] ?? '[]', true) ?: [];

        return $this->success([
            'menu' => frontend_menu_items(),
            'contact' => [
                'phone' => $settings['contact_phone_1'] ?? null,
                'email' => $settings['contact_email_1'] ?? null,
                'address' => $settings['contact_address'] ?? null,
                'footer_text' => $settings['footer_text'] ?? null,
            ],
            'social' => [
                'facebook' => $settings['social_facebook'] ?? null,
                'twitter' => $settings['social_twitter'] ?? null,
                'linkedin' => $settings['social_linkedin'] ?? null,
                'youtube' => $settings['social_youtube'] ?? null,
                'instagram' => $settings['social_instagram'] ?? null,
                'tiktok' => $settings['social_tiktok'] ?? null,
            ],
            'payment_gateway_images' => collect($paymentImages)
                ->filter()
                ->map(fn ($path) => api_asset($path))
                ->values()
                ->all(),
            'delivery' => [
                'inside_dhaka' => (float) ($settings['delivery_charge_inside_dhaka'] ?? 80),
                'outside_dhaka' => (float) ($settings['delivery_charge_outside_dhaka'] ?? 150),
            ],
        ], null, 'Settings fetched successfully');
    }
}
