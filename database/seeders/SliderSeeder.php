<?php

namespace Database\Seeders;

use App\Models\Slider;
use App\Models\User;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        // Content mirrors the storefront Hero.tsx defaults.
        $sliders = [
            [
                'subtitle' => 'টেক উইক',
                'title' => 'শব্দ যা মুহূর্তে মিলিয়ে যায়',
                'description' => '১,২০০+ যাচাইকৃত বিক্রেতার অডিওতে সর্বোচ্চ ৪৫% ছাড়।',
                'text' => 'শব্দ যা মুহূর্তে মিলিয়ে যায়',
                'button_text' => 'ইলেকট্রনিক্স কিনুন',
                'button_link' => '/products?category=electronics',
                'image' => '/uploads/sliders/70852c3d-5c66-4981-8a92-6810e4d9241c.webp',
                'alt_text' => 'Tech week audio deals',
                'status' => 'published',
                'is_active' => true,
                'published_at' => now(),
                'sort_order' => 1,
            ],
            [
                'subtitle' => 'স্টাইল এডিট',
                'title' => 'নতুন সিজনের ওয়ারড্রোব, সম্পাদকদের বাছাই',
                'description' => 'স্বাধীন ফ্যাশন হাউসের নতুন কালেকশন।',
                'text' => 'নতুন সিজনের ওয়ারড্রোব, সম্পাদকদের বাছাই',
                'button_text' => 'ফ্যাশন কিনুন',
                'button_link' => '/products?category=fashion',
                'image' => '/uploads/sliders/b868fea6-122c-42cb-955b-622b09360693.webp',
                'alt_text' => 'Season fashion edit',
                'status' => 'published',
                'is_active' => true,
                'published_at' => now(),
                'sort_order' => 2,
            ],
            [
                'subtitle' => 'হোম সিজন',
                'title' => 'ছোট আপগ্রেড, প্রতিদিনের বিলাসিতা',
                'description' => 'রান্নাঘর ও লিভিংয়ের জিনিসপত্রে ফ্রি এক্সপ্রেস ডেলিভারি।',
                'text' => 'ছোট আপগ্রেড, প্রতিদিনের বিলাসিতা',
                'button_text' => 'হোম কিনুন',
                'button_link' => '/products?category=home',
                'image' => '/uploads/sliders/35c7fbfa-5c24-42c3-81f6-481f3bb1d113.webp',
                'alt_text' => 'Home season upgrades',
                'status' => 'published',
                'is_active' => true,
                'published_at' => now(),
                'sort_order' => 3,
            ],
        ];

        foreach ($sliders as $sliderData) {
            Slider::updateOrCreate(
                [
                    'title' => $sliderData['title'],
                    'sort_order' => $sliderData['sort_order'],
                ],
                array_merge($sliderData, [
                    'created_by_id' => $user?->id,
                    'updated_by_id' => $user?->id,
                ])
            );
        }

        $this->command->info('Sliders seeded successfully!');
    }
}
