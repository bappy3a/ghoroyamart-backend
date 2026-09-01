<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'icon' => 'MonitorSmartphone',
                'image' => 'uploads/categories/electronics.png',
            ],
            [
                'name' => 'Fashion',
                'icon' => 'ShoppingBag',
                'image' => 'uploads/categories/fashion.png',
                'children' => [
                    [
                        'name' => "Men's Clothing",
                        'icon' => 'Mars',
                        'image' => 'uploads/categories/mens-clothing.png',
                        'children' => [
                            [
                                'name' => 'Punjabi',
                                'icon' => 'Shirt',
                                'image' => 'uploads/categories/panjabi.png',
                            ],
                        ],
                    ],
                    [
                        'name' => "Women's Clothing",
                        'icon' => 'Venus',
                        'image' => 'uploads/categories/womens-clothing.png',
                    ],
                    [
                        'name' => "Kids' Fashion",
                        'icon' => 'PersonStanding',
                        'image' => 'uploads/categories/kids-fashion.png',
                    ],
                    [
                        'name' => 'Bags',
                        'icon' => 'Backpack',
                        'image' => 'uploads/categories/bags.png',
                    ],
                    [
                        'name' => 'Watches',
                        'icon' => 'Watch',
                        'image' => 'uploads/categories/watches.png',
                    ],
                    [
                        'name' => 'Sunglasses',
                        'icon' => 'Glasses',
                        'image' => 'uploads/categories/sunglasses.png',
                    ],
                    [
                        'name' => 'Jewelry',
                        'icon' => 'Gem',
                        'image' => 'uploads/categories/jewelry.png',
                    ],
                ],
            ],
            [
                'name' => 'Beauty & Personal Care',
                'icon' => 'Sparkles',
                'image' => 'uploads/categories/beauty.png',
                'children' => [
                    [
                        'name' => 'Skincare',
                        'icon' => 'Droplet',
                        'image' => 'uploads/categories/skincare.png',
                    ],
                    [
                        'name' => 'Makeup',
                        'icon' => 'Paintbrush',
                        'image' => 'uploads/categories/makeup.png',
                    ],
                    [
                        'name' => 'Hair Care',
                        'icon' => 'Scissors',
                        'image' => 'uploads/categories/hair-care.png',
                    ],
                    [
                        'name' => "Men's Grooming",
                        'icon' => 'SprayCan',
                        'image' => 'uploads/categories/mens-grooming.png',
                    ],
                    [
                        'name' => 'Personal Hygiene',
                        'icon' => 'ShowerHead',
                        'image' => 'uploads/categories/personal-hygiene.png',
                    ],
                ],
            ],
            [
                'name' => 'Home & Living',
                'icon' => 'House',
                'image' => 'uploads/categories/home-living.png',
                'children' => [
                    [
                        'name' => 'Home Decor',
                        'icon' => 'Armchair',
                        'image' => 'uploads/categories/home-decor.png',
                    ],
                    [
                        'name' => 'Kitchen & Dining',
                        'icon' => 'CookingPot',
                        'image' => 'uploads/categories/kitchen-dining.png',
                    ],
                    [
                        'name' => 'Bed Sheets',
                        'icon' => 'BedDouble',
                        'image' => 'uploads/categories/bedding.png',
                    ],
                    [
                        'name' => 'Katha',
                        'icon' => 'Layers3',
                        'image' => 'uploads/categories/katha.png',
                    ],
                    [
                        'name' => 'Lighting',
                        'icon' => 'LampCeiling',
                        'image' => 'uploads/categories/lighting.png',
                    ],
                ],
            ],
            [
                'name' => 'Baby & Kids',
                'icon' => 'Baby',
                'image' => 'uploads/categories/baby-kids.png',
                'children' => [
                    [
                        'name' => 'Baby Care',
                        'icon' => 'HeartHandshake',
                        'image' => 'uploads/categories/baby-care.png',
                    ],
                    [
                        'name' => 'Toys & Games',
                        'icon' => 'Puzzle',
                        'image' => 'uploads/categories/toys-games.png',
                    ],
                    [
                        'name' => 'Baby Clothing',
                        'icon' => 'Shirt',
                        'image' => 'uploads/categories/baby-clothing.png',
                    ],
                ],
            ],
            [
                'name' => 'Gifts & Lifestyle',
                'icon' => 'Gift',
                'image' => 'uploads/categories/gifts-lifestyle.png',
            ],
        ];

        $this->seedCategories($categories);
    }

    private function seedCategories(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $category) {
            $savedCategory = Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'parent_id'         => $parentId,
                    'name'              => $category['name'],
                    'slug'              => Str::slug($category['name']),
                    'icon'              => $category['icon'],
                    'image'             => $category['image'],
                    'icon_class'        => null,
                    'description'       => $category['name'] . ' category.',
                    'meta_title'        => $category['name'],
                    'meta_description'  => 'Browse the best ' . strtolower($category['name']) . ' products.',
                    'meta_keywords'     => strtolower(str_replace(' ', ',', $category['name'])),
                    'meta_image'        => $category['image'],
                    'is_active'         => true,
                    'is_featured'       => false,
                    'is_popular'        => false,
                ]
            );

            $this->seedCategories($category['children'] ?? [], $savedCategory->id);
        }
    }
}
