<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private function frontendLayoutDefaults(): array
    {
        return [
            [
                'key' => 'delivery_charge_inside_dhaka',
                'value' => '80',
                'group' => 'Delivery Charge',
                'type' => 'number',
                'label' => 'Inside Dhaka Delivery Charge',
                'description' => 'Delivery charge applied to checkout and promotion orders inside Dhaka.',
                'sort_order' => 1,
            ],
            [
                'key' => 'delivery_charge_outside_dhaka',
                'value' => '150',
                'group' => 'Delivery Charge',
                'type' => 'number',
                'label' => 'Outside Dhaka Delivery Charge',
                'description' => 'Delivery charge applied to checkout and promotion orders outside Dhaka.',
                'sort_order' => 2,
            ],
            [
                'key' => 'frontend_menu_items',
                'value' => json_encode(frontend_menu_defaults()),
                'group' => 'Menu Settings',
                'type' => 'menu',
                'label' => 'Frontend Header Menu',
                'description' => 'Manage frontend navigation links and dropdown items.',
                'sort_order' => 1,
            ],
            [
                'key' => 'social_facebook',
                'value' => '#',
                'group' => 'Social Media',
                'type' => 'url',
                'label' => 'Facebook',
                'description' => 'Facebook page URL.',
                'sort_order' => 1,
            ],
            [
                'key' => 'social_twitter',
                'value' => '#',
                'group' => 'Social Media',
                'type' => 'url',
                'label' => 'Twitter / X',
                'description' => 'Twitter or X profile URL.',
                'sort_order' => 2,
            ],
            [
                'key' => 'social_linkedin',
                'value' => '#',
                'group' => 'Social Media',
                'type' => 'url',
                'label' => 'LinkedIn',
                'description' => 'LinkedIn page URL.',
                'sort_order' => 3,
            ],
            [
                'key' => 'social_youtube',
                'value' => '#',
                'group' => 'Social Media',
                'type' => 'url',
                'label' => 'YouTube',
                'description' => 'YouTube channel URL.',
                'sort_order' => 4,
            ],
            [
                'key' => 'social_instagram',
                'value' => '#',
                'group' => 'Social Media',
                'type' => 'url',
                'label' => 'Instagram',
                'description' => 'Instagram profile URL.',
                'sort_order' => 5,
            ],
            [
                'key' => 'social_tiktok',
                'value' => '#',
                'group' => 'Social Media',
                'type' => 'url',
                'label' => 'TikTok',
                'description' => 'TikTok profile URL.',
                'sort_order' => 6,
            ],
            [
                'key' => 'home_instagram_images',
                'value' => '[]',
                'group' => 'Image Gallery',
                'type' => 'images',
                'label' => 'Instagram Gallery Images',
                'description' => 'Upload multiple images for the home page Instagram gallery.',
                'sort_order' => 1,
            ],
            [
                'key' => 'contact_address',
                'value' => '2715 Ash Dr. San Jose, South Dakota 83475',
                'group' => 'Contact Us',
                'type' => 'textarea',
                'label' => 'Address',
                'description' => 'Physical address shown on the contact page.',
                'sort_order' => 1,
            ],
            [
                'key' => 'contact_email_1',
                'value' => 'info@ticstube.com',
                'group' => 'Contact Us',
                'type' => 'email',
                'label' => 'Email',
                'description' => 'Contact email address.',
                'sort_order' => 2,
            ],
            [
                'key' => 'contact_phone_1',
                'value' => '(500) 8001 8588',
                'group' => 'Contact Us',
                'type' => 'text',
                'label' => 'Phone',
                'description' => 'Phone number shown on the contact page.',
                'sort_order' => 3,
            ],
            [
                'key' => 'footer_text',
                'value' => '',
                'group' => 'Contact Us',
                'type' => 'textarea',
                'label' => 'Footer Text',
                'description' => 'Text shown in the website footer.',
                'sort_order' => 4,
            ],
            [
                'key' => 'payment_gateway_images',
                'value' => '[]',
                'group' => 'Contact Us',
                'type' => 'images',
                'label' => 'Payment Gateway Images',
                'description' => 'Upload multiple payment gateway / method images.',
                'sort_order' => 5,
            ],
        ];
    }

    private function obsoleteSettingKeys(): array
    {
        return [
            'contact_email_2',
            'contact_phone_2',
            'contact_hours',
            'contact_map_embed',
        ];
    }

    /**
     * Display the settings page (settings always from cache)
     */
    public function index(): View
    {
        foreach ($this->frontendLayoutDefaults() as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        $removed = Setting::whereIn('group', ['Header', 'Footer', 'General'])->delete();
        $removed += Setting::whereIn('key', $this->obsoleteSettingKeys())->delete();

        if ($removed) {
            Setting::clearCache();
        }

        // Keep layout setting metadata in sync (group/type/label) without overwriting values
        foreach ($this->frontendLayoutDefaults() as $setting) {
            Setting::where('key', $setting['key'])->update([
                'group' => $setting['group'],
                'type' => $setting['type'],
                'label' => $setting['label'],
                'description' => $setting['description'],
                'sort_order' => $setting['sort_order'],
            ]);
        }

        $groupOrder = [
            'Menu Settings',
            'Contact Us',
            'Social Media',
            'Image Gallery',
            'Delivery Charge',
            'SEO & Meta',
            'Payment Methods',
        ];

        $settings = Setting::orderBy('group')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->groupBy('group')
            ->sortBy(function ($items, $group) use ($groupOrder) {
                $index = array_search($group, $groupOrder, true);

                return $index === false ? (1000 + strlen($group)) : $index;
            });

        $menuSources = [
            'core' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Products', 'url' => route('products')],
                ['label' => 'Flash Deals', 'url' => route('flash.deals')],
                ['label' => 'Blog', 'url' => route('blog.index')],
                ['label' => 'About Us', 'url' => route('about.us')],
                ['label' => 'Contact Us', 'url' => route('contact.us')],
                ['label' => 'FAQ', 'url' => route('faq')],
                ['label' => 'Reviews', 'url' => route('reviews')],
                ['label' => 'Wishlist', 'url' => route('customer.wishlist')],
                ['label' => 'Cart', 'url' => route('cart.index')],
                ['label' => 'Checkout', 'url' => route('checkout.index')],
                ['label' => 'Customer Login', 'url' => route('customer.login')],
                ['label' => 'Customer Register', 'url' => route('customer.register')],
            ],
            'categories' => Category::where('is_active', true)
                ->orderBy('name')
                ->get(['name', 'slug'])
                ->map(fn (Category $category) => [
                    'label' => $category->name,
                    'url' => route('products', ['category' => $category->slug]),
                ])
                ->values()
                ->all(),
        ];

        return view('admin.settings.index', compact('settings', 'menuSources'));
    }

    /**
     * Update settings
     */
    public function update(SettingsRequest $request): RedirectResponse
    {
        $data = $request->except(['_token', '_method']);

        $this->updateGalleryImagesSetting(
            $request,
            'payment_gateway_images',
            'payment_gateway_existing',
            [
                'group' => 'Contact Us',
                'label' => 'Payment Gateway Images',
                'description' => 'Upload multiple payment gateway / method images.',
                'sort_order' => 5,
            ]
        );
        $this->updateGalleryImagesSetting(
            $request,
            'home_instagram_images',
            'home_instagram_existing',
            [
                'group' => 'Image Gallery',
                'label' => 'Instagram Gallery Images',
                'description' => 'Upload multiple images for the home page Instagram gallery.',
                'sort_order' => 1,
            ]
        );
        unset(
            $data['payment_gateway_images'],
            $data['payment_gateway_existing'],
            $data['home_instagram_images'],
            $data['home_instagram_existing']
        );

        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($request->hasFile($key)) {
                delete_uploaded_file($setting?->value);

                $value = upload_webp_image($request->file($key), 'uploads/settings', 80);
                $type = 'image';
            } else {
                $type = $setting->type ?? 'text';
                if (is_array($value)) {
                    $value = json_encode($value);
                    $type = $type === 'menu' ? 'menu' : 'textarea';
                }
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $value,
                    'group' => $setting->group ?? 'General',
                    'type' => $type,
                    'label' => $setting->label ?? ucwords(str_replace('_', ' ', $key)),
                    'description' => $setting->description ?? null,
                    'sort_order' => $setting->sort_order ?? 0,
                ]
            );
        }

        Setting::clearCache();

        flash_message('Settings updated successfully!');

        return redirect()->route('settings.index');
    }

    private function updateGalleryImagesSetting(Request $request, string $key, string $existingKey, array $meta): void
    {
        $setting = Setting::where('key', $key)->first();

        $images = [];
        foreach ($request->input($existingKey, []) as $img) {
            if (!empty($img)) {
                $images[] = $img;
            }
        }
        $images = array_values(array_unique($images));

        if ($request->hasFile($key)) {
            foreach ($request->file($key) as $imgFile) {
                if ($imgFile) {
                    $images[] = upload_webp_image($imgFile, 'uploads/settings', 80);
                }
            }
        }

        $storedImages = json_decode($setting?->value ?? '[]', true) ?: [];
        foreach (array_diff($storedImages, $images) as $removedImage) {
            delete_uploaded_file($removedImage);
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => json_encode($images),
                'group' => $setting->group ?? ($meta['group'] ?? 'General'),
                'type' => 'images',
                'label' => $setting->label ?? ($meta['label'] ?? ucwords(str_replace('_', ' ', $key))),
                'description' => $setting->description ?? ($meta['description'] ?? null),
                'sort_order' => $setting->sort_order ?? ($meta['sort_order'] ?? 0),
            ]
        );
    }
}
