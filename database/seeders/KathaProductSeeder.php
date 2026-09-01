<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KathaProductSeeder extends Seeder
{
    private const string IMAGE = 'uploads/categories/katha.png';

    private const string SHORT_DESCRIPTION = 'ঐতিহ্যবাহী হাতে সেলাই করা নকশি কাঁথা—নান্দনিক নকশা, সূক্ষ্ম কারুকাজ ও বাংলার সংস্কৃতির অপূর্ব সমন্বয়। ঘর সাজানো কিংবা প্রিয়জনকে উপহার দেওয়ার জন্য দারুণ একটি পছন্দ।';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::where('slug', 'katha')->firstOrFail();
        $user = User::first();

        $products = [
            [
                'name' => 'জামালপুরী নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'jamalpuri-nakshi-katha',
                'purchase_price' => 1700,
                'regular_price' => 2350,
                'discount_amount' => 250,
            ],
            [
                'name' => 'জামালপুরী নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'jamalpuri-nakshi-katha-01',
                'purchase_price' => 1700,
                'regular_price' => 2350,
                'discount_amount' => 250,
            ],
            [
                'name' => 'সাজনিগাথা নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'storytelling-nakshi-katha',
                'purchase_price' => 950,
                'regular_price' => 1500,
                'discount_amount' => 250,
            ],
            [
                'name' => 'সাজনিগাথা নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'storytelling-nakshi-katha-01',
                'purchase_price' => 950,
                'regular_price' => 1500,
                'discount_amount' => 250,
            ],
            [
                'name' => 'খাজুরপাত নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'date-palm-nakshi-katha',
                'purchase_price' => 1370,
                'regular_price' => 2000,
                'discount_amount' => 200,
            ],
            [
                'name' => 'বকুল নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'bakul-nakshi-katha-01',
                'purchase_price' => 1300,
                'regular_price' => 1900,
                'discount_amount' => 200,
            ],
            [
                'name' => 'বকুল নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'bakul-nakshi-katha',
                'purchase_price' => 1300,
                'regular_price' => 1900,
                'discount_amount' => 200,
            ],
            [
                'name' => 'নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'folk-design-premium-nakshi-katha',
                'purchase_price' => 1400,
                'regular_price' => 2250,
                'discount_amount' => 250,
            ],
            [
                'name' => 'নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'nakshi-katha-02',
                'purchase_price' => 1400,
                'regular_price' => 2250,
                'discount_amount' => 250,
            ],
            [
                'name' => 'নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'nakshi-katha-01',
                'purchase_price' => 1400,
                'regular_price' => 2250,
                'discount_amount' => 250,
            ],
            [
                'name' => 'বাগান বিলাস নকশি কাঁথা (কিং সাইজ ৭/৮ ফুট প্রায় )',
                'slug' => 'bagan-bilas-nakshi-katha',
                'purchase_price' => 1400,
                'regular_price' => 1900,
                'discount_amount' => 150,
            ],
        ];

        DB::transaction(function () use ($category, $products, $user): void {
            foreach ($products as $index => $productData) {
                $number = $index + 1;
                $sku = 'KATHA-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT);
                $regularPrice = $productData['regular_price'];
                $discountAmount = $productData['discount_amount'];
                $price = $regularPrice - $discountAmount;

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'name' => $productData['name'],
                        'slug' => $productData['slug'],
                        'landing_page_slug' => $productData['slug'].'-deal',
                        'short_description' => self::SHORT_DESCRIPTION,
                        'description' => $this->description(),
                        'how_to_use' => 'ব্যবহারের আগে হালকা ঝেড়ে নিন। প্রয়োজন হলে ঠান্ডা পানিতে মৃদু ডিটারজেন্ট দিয়ে আলতো হাতে ধুয়ে ছায়ায় সমতলভাবে শুকান।',
                        'good_to_know' => 'সম্পূর্ণ হাতে তৈরি হওয়ায় প্রতিটি কাঁথার নকশা ও সেলাইয়ে সামান্য ভিন্নতা থাকতে পারে—এটিই প্রতিটি পণ্যের স্বকীয়তা। ব্লিচ ও কড়া ডিটারজেন্ট ব্যবহার করবেন না।',
                        'warranty' => 'কোনো ওয়ারেন্টি নেই',
                        'status' => 'published',
                        'visibility' => 'public',
                        'published_at' => now(),
                        'thumbnail_image' => self::IMAGE,
                        'images' => json_encode([self::IMAGE], JSON_THROW_ON_ERROR),
                        'video_media' => null,
                        'quantity' => 10,
                        'stock_status' => 'in_stock',
                        'product_location' => 'store',
                        'minimum_order_quantity' => 1,
                        'maximum_order_quantity' => 10,
                        'low_stock_alert' => 1,
                        'purchase_price' => $productData['purchase_price'],
                        'regular_price' => $regularPrice,
                        'price' => $price,
                        'discount_amount' => $discountAmount,
                        'discount_percentage' => round(($discountAmount / $regularPrice) * 100, 2),
                        'discount_start_date' => now()->toDateString(),
                        'discount_end_date' => now()->addMonths(3)->toDateString(),
                        'is_discounted' => true,
                        'is_featured' => $index < 3,
                        'is_new' => true,
                        'is_best_seller' => in_array($index, [0, 1, 4], true),
                        'brand_id' => null,
                        'category_id' => $category->id,
                        'unit' => 'Pcs',
                        'tax_rate' => 0,
                        'created_by_id' => $user?->id,
                        'approved_by_id' => $user?->id,
                        'updated_by_id' => $user?->id,
                        'deleted_by_id' => null,
                        'num_of_sale' => 0,
                        'num_of_views' => 0,
                        'num_of_reviews' => 0,
                        'reviews_avg' => 0,
                        'meta_title' => $productData['name'].' | Agonito',
                        'meta_description' => self::SHORT_DESCRIPTION,
                        'meta_keywords' => 'নকশি কাঁথা, হাতের কাজের কাঁথা, দেশি কারুশিল্প, ঐতিহ্যবাহী কাঁথা, Agonito',
                        'meta_image' => self::IMAGE,
                    ]
                );

                // These products intentionally have no variants, including after re-seeding.
                $product->variants()->delete();
            }
        });

        $this->command?->info(count($products).' Katha products seeded without variants.');
    }

    private function description(): string
    {
        return <<<'HTML'
<p>বাংলার ঐতিহ্য ও কারুশিল্পের সৌন্দর্যকে ধারণ করে তৈরি আমাদের <strong>হাতের কাজের নকশি কাঁথা</strong>। প্রতিটি কাঁথায় রয়েছে যত্নসহকারে করা সূক্ষ্ম হাতের সেলাই ও আকর্ষণীয় নকশা, যা একে করে তুলেছে অনন্য।</p>

<p>শুধু একটি কাঁথা নয়—এটি বাংলার লোকজ সংস্কৃতি, কারিগরের দক্ষতা এবং প্রজন্মের পর প্রজন্ম ধরে চলে আসা ঐতিহ্যের একটি সুন্দর নিদর্শন।</p>

<h2>✨ বিশেষত্ব</h2>

<ul>
    <li>মাপ: ৭ ফুট x ৮ ফুট প্রায়</li>
    <li>খাঁটি কটন কাপড়</li>
    <li>কটন কাপড়ের ওপর হাতে সুতার নকশা করা</li>
    <li>সম্পূর্ণ হাতে সেলাই করা নকশি কাঁথা</li>
    <li>বড় সাইজের কাঁথা</li>
    <li>ঐতিহ্যবাহী বাঙালি নকশা ও কারুকাজ</li>
    <li>উচ্চমানের সূক্ষ্ম কাপড় ও সুতা ব্যবহার</li>
</ul>

<h2>✨ কেন পছন্দ করবেন?</h2>

<ul>
    <li>মাপ: ৭ ফুট x ৮ ফুট প্রায়</li>
    <li>সম্পূর্ণ হাতে করা নকশি ও কারুকাজ</li>
    <li>ঐতিহ্যবাহী বাঙালি ডিজাইন</li>
    <li>ঘর সাজাতে আকর্ষণীয়</li>
    <li>ব্যবহার ও উপহার—দুই ক্ষেত্রেই উপযোগী</li>
    <li>প্রতিটি কাঁথার ডিজাইনে রয়েছে নিজস্ব স্বকীয়তা</li>
</ul>

<p>আপনার ঘরে বাঙালিয়ানার সৌন্দর্য ও ঐতিহ্যের ছোঁয়া যোগ করতে বেছে নিন <strong>Agonito-এর নকশি কাঁথা</strong>।</p>
HTML;
    }
}
