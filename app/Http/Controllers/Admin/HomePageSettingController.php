<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\HomeController as ApiHomeController;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomePageSettingController extends Controller
{
    public function index(): View
    {
        $settings = $this->settingsMap();
        $settings = array_merge([
            'home_slider_count' => '6',
            'home_popular_categories_count' => '12',
            'home_featured_products_count' => '8',
            'home_best_selling_products_count' => '10',
            'home_trending_products_count' => '10',
            'home_new_arrival_products_count' => '10',
            'home_popular_products_count' => '10',
            'home_diamond_products_count' => '10',
            'home_brands_count' => '7',
            'home_video_promotions_count' => '6',
            'home_popular_categories_title' => 'Popular Categories',
            'home_featured_section_subtitle' => 'Summer collection',
            'home_featured_section_title' => 'Shopping Every Day',
            'home_shopping_banner_title' => 'Trending Now Only This Weekend!',
            'home_shopping_category_id' => '',
            'home_shopping_products_limit' => '8',
            'home_shopping_section_items' => [],
            'home_top_selling_title' => 'Top selling Categories This Week',
            'home_video_link' => '',
            'home_video_banner' => '',
            'home_video_banner_text' => '',
            'home_trending_banner_image' => '',
            'home_trending_banner_text' => '',
            'home_trending_banner_subtitle' => '',
            'home_trending_banner_link' => '',
            'home_trending_banner_link_text' => '',
            'home_product_review_subtitle' => '',
            'home_product_review_title' => '',
            'home_product_review_description' => '',
            'home_category_banners' => [],
            'home_instagram_images' => [],
        ], $settings);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.home-page-settings.index', compact('settings', 'categories'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'home_slider_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'home_popular_categories_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'home_featured_products_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'home_best_selling_products_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'home_trending_products_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'home_new_arrival_products_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'home_popular_products_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'home_diamond_products_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'home_brands_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'home_video_promotions_count' => ['nullable', 'integer', 'min:1', 'max:30'],

            'home_popular_categories_title' => ['nullable', 'string', 'max:255'],
            'home_featured_section_subtitle' => ['nullable', 'string', 'max:255'],
            'home_featured_section_title' => ['nullable', 'string', 'max:255'],
            'home_shopping_banner_title' => ['nullable', 'string', 'max:255'],
            'home_shopping_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'home_shopping_products_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'home_top_selling_title' => ['nullable', 'string', 'max:255'],

            'home_video_link' => ['nullable', 'url', 'max:255'],
            'home_video_banner' => ['nullable', 'image', 'max:2048'],
            'home_video_banner_text' => ['nullable', 'string', 'max:255'],

            'home_trending_banner_image' => ['nullable', 'image', 'max:2048'],
            'home_trending_banner_text' => ['nullable', 'string', 'max:255'],
            'home_trending_banner_subtitle' => ['nullable', 'string', 'max:255'],
            'home_trending_banner_link' => ['nullable', 'url', 'max:255'],
            'home_trending_banner_link_text' => ['nullable', 'string', 'max:100'],

            'home_product_review_subtitle' => ['nullable', 'string', 'max:255'],
            'home_product_review_title' => ['nullable', 'string', 'max:255'],
            'home_product_review_description' => ['nullable', 'string', 'max:1000'],

            'home_instagram_existing' => ['nullable', 'array'],
            'home_instagram_existing.*' => ['nullable', 'string'],
            'home_instagram_images' => ['nullable', 'array'],
            'home_instagram_images.*' => ['nullable', 'image', 'max:2048'],

            'category_banners' => ['nullable', 'array'],
            'category_banners.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_banners.*.products_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'category_banners.*.text' => ['nullable', 'string', 'max:255'],
            'category_banners.*.discount_text' => ['nullable', 'string', 'max:255'],
            'category_banners.*.link' => ['nullable', 'url', 'max:255'],
            'category_banners.*.link_text' => ['nullable', 'string', 'max:100'],
            'category_banners.*.existing_image' => ['nullable', 'string'],
            'category_banners_images' => ['nullable', 'array'],
            'category_banners_images.*' => ['nullable', 'image', 'max:2048'],

            'shopping_section_items' => ['nullable', 'array'],
            'shopping_section_items.*.title' => ['nullable', 'string', 'max:255'],
            'shopping_section_items.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'shopping_section_items.*.products_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'shopping_section_items.*.link' => ['nullable', 'url', 'max:255'],
            'shopping_section_items.*.link_text' => ['nullable', 'string', 'max:100'],
            'shopping_section_items.*.existing_image' => ['nullable', 'string'],
            'shopping_section_item_images' => ['nullable', 'array'],
            'shopping_section_item_images.*' => ['nullable', 'image', 'max:2048'],
        ]);

        $fields = [
            'home_slider_count',
            'home_popular_categories_count',
            'home_featured_products_count',
            'home_best_selling_products_count',
            'home_trending_products_count',
            'home_new_arrival_products_count',
            'home_popular_products_count',
            'home_diamond_products_count',
            'home_brands_count',
            'home_video_promotions_count',
            'home_popular_categories_title',
            'home_featured_section_subtitle',
            'home_featured_section_title',
            'home_shopping_banner_title',
            'home_shopping_category_id',
            'home_shopping_products_limit',
            'home_top_selling_title',
            'home_video_link',
            'home_video_banner',
            'home_video_banner_text',
            'home_trending_banner_image',
            'home_trending_banner_text',
            'home_trending_banner_subtitle',
            'home_trending_banner_link',
            'home_trending_banner_link_text',
            'home_product_review_subtitle',
            'home_product_review_title',
            'home_product_review_description',
        ];

        foreach ($fields as $key) {
            $setting = Setting::where('key', $key)->first();
            $value = $data[$key] ?? null;

            if ($request->hasFile($key)) {
                delete_uploaded_file($setting?->value);

                $value = upload_webp_image($request->file($key), 'uploads/settings/home', 80);
            } elseif ($value === null) {
                $value = $setting?->value ?? '';
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $value,
                    'group' => 'Home Page',
                    'type' => $request->hasFile($key) || str_contains($key, 'image') ? 'image' : 'text',
                    'label' => ucwords(str_replace('_', ' ', str_replace('home_', '', $key))),
                    'description' => 'Home page setting',
                ]
            );
        }

        $categoryBanners = [];
        $bannerRows = $data['category_banners'] ?? [];
        foreach ($bannerRows as $index => $bannerRow) {
            $imagePath = $bannerRow['existing_image'] ?? '';
            if ($request->hasFile("category_banners_images.{$index}")) {
                delete_uploaded_file($imagePath);
                $imagePath = upload_webp_image($request->file("category_banners_images.{$index}"), 'uploads/settings/home', 80);
            }

            if (
                empty($bannerRow['category_id']) &&
                empty($bannerRow['products_limit']) &&
                empty($bannerRow['text']) &&
                empty($bannerRow['discount_text']) &&
                empty($bannerRow['link']) &&
                empty($bannerRow['link_text']) &&
                empty($imagePath)
            ) {
                continue;
            }

            $categoryBanners[] = [
                'category_id' => (string) ($bannerRow['category_id'] ?? ''),
                'products_limit' => (int) ($bannerRow['products_limit'] ?? 8),
                'text' => $bannerRow['text'] ?? '',
                'discount_text' => $bannerRow['discount_text'] ?? '',
                'link' => $bannerRow['link'] ?? '#',
                'link_text' => $bannerRow['link_text'] ?? 'Check Discount',
                'banner_image' => $imagePath,
            ];
        }

        Setting::updateOrCreate(
            ['key' => 'home_category_banners'],
            [
                'value' => json_encode($categoryBanners),
                'group' => 'Home Page',
                'type' => 'textarea',
                'label' => 'Home Category Banners',
                'description' => 'Dynamic category banners',
            ]
        );

        $instagramImages = [];
        $existingInstagram = $data['home_instagram_existing'] ?? [];
        foreach ($existingInstagram as $img) {
            if (!empty($img)) {
                $instagramImages[] = $img;
            }
        }
        $instagramImages = array_values(array_unique($instagramImages));

        if ($request->hasFile('home_instagram_images')) {
            foreach ($request->file('home_instagram_images') as $imgFile) {
                if ($imgFile) {
                    $instagramImages[] = upload_webp_image($imgFile, 'uploads/settings/home', 80);
                }
            }
        }

        $storedInstagramImages = json_decode(Setting::get('home_instagram_images', '[]'), true) ?: [];
        foreach (array_diff($storedInstagramImages, $instagramImages) as $removedImage) {
            delete_uploaded_file($removedImage);
        }

        Setting::updateOrCreate(
            ['key' => 'home_instagram_images'],
            [
                'value' => json_encode($instagramImages),
                'group' => 'Home Page',
                'type' => 'textarea',
                'label' => 'Home Instagram Images',
                'description' => 'Dynamic instagram gallery images',
            ]
        );

        $shoppingSectionItems = [];
        $shoppingRows = $data['shopping_section_items'] ?? [];
        foreach ($shoppingRows as $index => $row) {
            $imagePath = $row['existing_image'] ?? '';
            if ($request->hasFile("shopping_section_item_images.{$index}")) {
                delete_uploaded_file($imagePath);
                $imagePath = upload_webp_image($request->file("shopping_section_item_images.{$index}"), 'uploads/settings/home', 80);
            }

            if (
                empty($row['title']) &&
                empty($row['category_id']) &&
                empty($row['products_limit']) &&
                empty($row['link']) &&
                empty($row['link_text']) &&
                empty($imagePath)
            ) {
                continue;
            }

            $shoppingSectionItems[] = [
                'title' => $row['title'] ?? 'Trending Now Only This Weekend!',
                'category_id' => (string) ($row['category_id'] ?? ''),
                'products_limit' => (int) ($row['products_limit'] ?? 8),
                'link' => $row['link'] ?? '#',
                'link_text' => $row['link_text'] ?? 'Shop Now',
                'banner_image' => $imagePath,
            ];
        }

        Setting::updateOrCreate(
            ['key' => 'home_shopping_section_items'],
            [
                'value' => json_encode($shoppingSectionItems),
                'group' => 'Home Page',
                'type' => 'textarea',
                'label' => 'Home Shopping Section Items',
                'description' => 'Dynamic shopping section banner items',
            ]
        );

        Setting::clearCache();
        ApiHomeController::clearCache();
        flash_message('Home page settings updated successfully!');

        return redirect()->route('home-page-settings.index');
    }

    private function settingsMap(): array
    {
        return [
            'home_slider_count' => Setting::get('home_slider_count', '6'),
            'home_popular_categories_count' => Setting::get('home_popular_categories_count', '12'),
            'home_featured_products_count' => Setting::get('home_featured_products_count', '8'),
            'home_best_selling_products_count' => Setting::get('home_best_selling_products_count', '10'),
            'home_trending_products_count' => Setting::get('home_trending_products_count', '10'),
            'home_new_arrival_products_count' => Setting::get('home_new_arrival_products_count', '10'),
            'home_popular_products_count' => Setting::get('home_popular_products_count', '10'),
            'home_diamond_products_count' => Setting::get('home_diamond_products_count', '10'),
            'home_brands_count' => Setting::get('home_brands_count', '7'),
            'home_video_promotions_count' => Setting::get('home_video_promotions_count', '6'),
            'home_popular_categories_title' => Setting::get('home_popular_categories_title', 'Popular Categories'),
            'home_featured_section_subtitle' => Setting::get('home_featured_section_subtitle', 'Summer collection'),
            'home_featured_section_title' => Setting::get('home_featured_section_title', 'Shopping Every Day'),
            'home_shopping_banner_title' => Setting::get('home_shopping_banner_title', 'Trending Now Only This Weekend!'),
            'home_shopping_category_id' => Setting::get('home_shopping_category_id', ''),
            'home_shopping_products_limit' => Setting::get('home_shopping_products_limit', '8'),
            'home_shopping_section_items' => json_decode(Setting::get('home_shopping_section_items', '[]'), true) ?: [],
            'home_top_selling_title' => Setting::get('home_top_selling_title', 'Top selling Categories This Week'),
            'home_video_link' => Setting::get('home_video_link', 'https://youtu.be/cNOKQIw81SE?si=iwUyBvpTD3h8DpFK'),
            'home_video_banner' => Setting::get('home_video_banner', ''),
            'home_video_banner_text' => Setting::get('home_video_banner_text', 'Watch our latest collection video'),
            'home_trending_banner_image' => Setting::get('home_trending_banner_image', ''),
            'home_trending_banner_text' => Setting::get('home_trending_banner_text', "Women's collections"),
            'home_trending_banner_subtitle' => Setting::get('home_trending_banner_subtitle', 'Trending Products'),
            'home_trending_banner_link' => Setting::get('home_trending_banner_link', '#'),
            'home_trending_banner_link_text' => Setting::get('home_trending_banner_link_text', 'Collection'),
            'home_product_review_subtitle' => Setting::get('home_product_review_subtitle', 'Customer Reviews'),
            'home_product_review_title' => Setting::get('home_product_review_title', 'Product Reviews'),
            'home_product_review_description' => Setting::get('home_product_review_description', 'Our references are very valuable, the result of a great effort...'),
            'home_category_banners' => json_decode(Setting::get('home_category_banners', '[]'), true) ?: [],
            'home_instagram_images' => json_decode(Setting::get('home_instagram_images', '[]'), true) ?: [],
        ];
    }
}
