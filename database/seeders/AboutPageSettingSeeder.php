<?php

namespace Database\Seeders;

use App\Models\AboutPageSetting;
use Illuminate\Database\Seeder;

class AboutPageSettingSeeder extends Seeder
{
    public function run(): void
    {
        AboutPageSetting::updateOrCreate(
            ['id' => 1],
            [
                'page_title' => 'About Us',
                'breadcrumb_title' => 'About us',
                'breadcrumb_subtitle' => 'Learn more about our story and values.',
                'cover_image' => null,

                'section_one_subtitle' => 'About us',
                'section_one_title' => 'We are glamers people',
                'section_one_content' => 'Vestibulum quis lobortis mauris. Donec molestie porta nibh quis tristique. Vivamus pharetra pretium augue a tempus. Nunc eu lorem quis ex vestibulum dignissim accumsan id velit. Pellentesque pretium, mi in posuere euismod, nulla dolor blandit purus, a eleifend velit massa quis nisi. Integer gravida dictum ipsum ac fringilla. Sed non neque est. Fusce faucibus velit ac volutpat faucibus. In sapien tellus, viverra vitae elementum eu, hendrerit id eros. Duis libero turpis, elementum non molestie ornare, dictum et odio. Quisque dui dolor, commodo in malesuada id, porttitor in enim. Suspendisse elementum ante at venenatis tristique. Nam non ex porta, aliquam tellus vitae, vulputate mauris.',
                'section_one_image' => null,

                'section_two_subtitle' => 'our history',
                'section_two_title' => 'Established - 1995',
                'section_two_content' => 'Vestibulum quis lobortis mauris. Donec molestie porta nibh quis tristique. Vivamus pharetra pretium augue a tempus. Nunc eu lorem quis ex vestibulum dignissim accumsan id velit. Pellentesque pretium, mi in posuere euismod, nulla dolor blandit purus, a eleifend velit massa quis nisi. Integer gravida dictum ipsum ac fringilla. Sed non neque est. Fusce faucibus velit ac volutpat faucibus. In sapien tellus, viverra vitae elementum eu, hendrerit id eros. Duis libero turpis, elementum non molestie ornare, dictum et odio. Quisque dui dolor, commodo in malesuada id, porttitor in enim. Suspendisse elementum ante at venenatis tristique. Nam non ex porta, aliquam tellus vitae, vulputate mauris.',
                'section_two_image' => null,

                'features_subtitle' => 'More about us',
                'features_title' => 'Quality is our priority',
                'features_description' => 'Our talented stylists have put together outfits that are perfect for the season. They’ve variety of ways to inspire your next fashion-forward look.',
                'feature_one_title' => 'Rending Design',
                'feature_one_description' => 'Vestibulum quis lobortis mauris. Donec molestie porta nibh quis tristique. Vivamus pharetra pretium augue a tempus. Nunc eu lorem quis ex vestibulum dignissim accumsan id velit.',
                'feature_two_title' => 'Multiple Sizes',
                'feature_two_description' => 'Vestibulum quis lobortis mauris. Donec molestie porta nibh quis tristique. Vivamus pharetra pretium augue a tempus. Nunc eu lorem quis ex vestibulum dignissim accumsan id velit.',
                'feature_three_title' => 'High Quality Matters',
                'feature_three_description' => 'Vestibulum quis lobortis mauris. Donec molestie porta nibh quis tristique. Vivamus pharetra pretium augue a tempus. Nunc eu lorem quis ex vestibulum dignissim accumsan id velit.',

                'reviews_subtitle' => 'Customer Reviews',
                'reviews_title' => 'Product Reviews',
                'reviews_description' => 'Our references are very valuable, the result of a great effort...',
            ]
        );

        $this->command?->info('About page settings seeded successfully!');
    }
}
