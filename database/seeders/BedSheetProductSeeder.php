<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BedSheetProductSeeder extends Seeder
{
    private const CATEGORY_ID = 20;

    private const IMAGE = 'uploads/categories/bedding.png';

    private const SHORT_DESCRIPTION = 'আপনার ঘুমকে আরও আরামদায়ক করতে নিয়ে আসুন ১০০% এক্সপোর্ট কোয়ালিটির প্রিমিয়াম বেড শিট। উন্নত মানের ফেব্রিক দিয়ে তৈরি এই চাদরটি অত্যন্ত নরম, ত্বকবান্ধব এবং দীর্ঘদিন ব্যবহারের উপযোগী। প্রতিদিনের ব্যবহারের পাশাপাশি এটি আপনার বেডরুমের সৌন্দর্যও বাড়িয়ে তুলবে।';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::findOrFail(self::CATEGORY_ID);
        $user = User::first();

        $products = [
            [
                'name' => 'প্রিমিয়াম এক্সপোর্ট কোয়ালিটি কটন বেড শিট – নরম, আরামদায়ক ও টেকসই',
                'slug' => 'premium-export-quality-cotton-bed-sheet',
                'description' => $this->firstDescription(),
            ],
            [
                'name' => 'লাক্সারি এক্সপোর্ট গ্রেড বেড শিট – স্টাইলিশ, নরম ও আরামদায়ক',
                'slug' => 'luxury-export-grade-bed-sheet',
                'description' => $this->secondDescription(),
            ],
            [
                'name' => 'প্রিমিয়াম সফট কটন বেড শিট – এক্সপোর্ট কোয়ালিটি কালেকশন',
                'slug' => 'premium-soft-cotton-bed-sheet-export-collection',
                'description' => $this->thirdDescription(),
            ],
            [
                'name' => 'রয়্যাল কমফোর্ট কটন বেড শিট – এক্সপোর্ট কোয়ালিটি ও টেকসই',
                'slug' => 'royal-comfort-cotton-bed-sheet',
                'description' => $this->firstDescription(),
            ],
            [
                'name' => 'হোটেল কোয়ালিটি লাক্সারি বেড শিট – সুপার সফট ও আরামদায়ক',
                'slug' => 'hotel-quality-luxury-bed-sheet',
                'description' => $this->secondDescription(),
            ],
            [
                'name' => 'এলিগ্যান্ট প্রিমিয়াম বেড শিট – আকর্ষণীয় ডিজাইন ও দীর্ঘস্থায়ী রঙ',
                'slug' => 'elegant-premium-bed-sheet',
                'description' => $this->thirdDescription(),
            ],
            [
                'name' => 'অল-সিজন এক্সপোর্ট গ্রেড বেড শিট – নরম ও ত্বকবান্ধব',
                'slug' => 'all-season-export-grade-bed-sheet',
                'description' => $this->firstDescription(),
            ],
            [
                'name' => 'সুপার সফট প্রিমিয়াম কটন বেড শিট – আরামদায়ক ঘুমের জন্য',
                'slug' => 'super-soft-premium-cotton-bed-sheet',
                'description' => $this->secondDescription(),
            ],
            [
                'name' => 'ডিলাক্স এক্সপোর্ট কোয়ালিটি বেড শিট – স্টাইলিশ ও সহজে পরিষ্কারযোগ্য',
                'slug' => 'deluxe-export-quality-bed-sheet',
                'description' => $this->thirdDescription(),
            ],
            [
                'name' => 'এক্সক্লুসিভ প্রিমিয়াম বেড শিট – নরম ফেব্রিক ও প্রিমিয়াম ফিনিশ',
                'slug' => 'exclusive-premium-bed-sheet',
                'description' => $this->firstDescription(),
            ],
        ];

        DB::transaction(function () use ($category, $products, $user): void {
            foreach ($products as $index => $productData) {
                $number = $index + 1;
                $regularPrice = 1800 + ($index * 50);
                $price = 1490 + ($index * 50);
                $discountAmount = $regularPrice - $price;

                $product = Product::updateOrCreate(
                    ['slug' => $productData['slug']],
                    [
                        'name' => $productData['name'],
                        'landing_page_slug' => $productData['slug'].'-deal',
                        'sku' => 'BED-SHEET-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                        'short_description' => self::SHORT_DESCRIPTION,
                        'description' => $productData['description'],
                        'how_to_use' => 'প্রথম ব্যবহারের আগে ধুয়ে নিন। ঠান্ডা বা স্বাভাবিক পানিতে মৃদু ডিটারজেন্ট দিয়ে ধুয়ে ছায়ায় শুকান।',
                        'good_to_know' => 'ত্বকবান্ধব ফেব্রিক। রঙের উজ্জ্বলতা ধরে রাখতে ব্লিচ ব্যবহার করবেন না।',
                        'warranty' => 'কোনো ওয়ারেন্টি নেই',
                        'status' => 'published',
                        'visibility' => 'public',
                        'published_at' => now(),
                        'thumbnail_image' => self::IMAGE,
                        'images' => json_encode([self::IMAGE]),
                        'video_media' => null,
                        'quantity' => 50,
                        'stock_status' => 'in_stock',
                        'product_location' => 'store',
                        'minimum_order_quantity' => 1,
                        'maximum_order_quantity' => 10,
                        'low_stock_alert' => 5,
                        'purchase_price' => 1000 + ($index * 25),
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
                        'meta_keywords' => 'বেড শিট, কটন বেড শিট, এক্সপোর্ট কোয়ালিটি, প্রিমিয়াম বেড শিট',
                        'meta_image' => self::IMAGE,
                    ]
                );

                // These products intentionally have no variants, including after re-seeding.
                $product->variants()->delete();
            }
        });

        $this->command?->info('10 Bed Sheets products seeded without variants.');
    }

    private function firstDescription(): string
    {
        return <<<'HTML'

  <h1>🛏️ ১০০% এক্সপোর্ট কোয়ালিটি প্রিমিয়াম বেড শিট</h1>

  <p>
    আপনার ঘুমের আরাম এবং বেডরুমের সৌন্দর্য—দুটোকেই নতুন মাত্রা দিতে নিয়ে এসেছি
    <span class="highlight">১০০% এক্সপোর্ট কোয়ালিটির প্রিমিয়াম বেড শিট</span>। উন্নত মানের কাপড় দিয়ে তৈরি এই বেড শিট অত্যন্ত নরম, আরামদায়ক এবং দীর্ঘদিন ব্যবহারের উপযোগী। প্রতিটি চাদর তৈরি করা হয়েছে আন্তর্জাতিক মান বজায় রেখে, যাতে আপনি প্রতিদিন পান বিলাসবহুল অনুভূতি।
  </p>

  <p>
    এর মসৃণ ও সফট ফেব্রিক ত্বকের জন্য সম্পূর্ণ আরামদায়ক এবং দীর্ঘ সময় ব্যবহার করলেও কোনো ধরনের অস্বস্তি সৃষ্টি করে না। গরমের দিনে এটি শীতল অনুভূতি দেয় এবং শীতের সময়ও আরাম বজায় রাখে। উন্নত মানের ডাইং প্রযুক্তি ব্যবহারের ফলে রঙ সহজে ফিকে হয় না এবং বারবার ধোয়ার পরও নতুনের মতো আকর্ষণীয় থাকে।
  </p>

  <p>
    এই বেড শিট শুধু একটি দৈনন্দিন ব্যবহার্য পণ্য নয়, বরং আপনার বেডরুমের সৌন্দর্য ও রুচির প্রতিফলন। আধুনিক ও আকর্ষণীয় ডিজাইনের কারণে এটি আপনার ঘরের সাজসজ্জায় এনে দেবে নতুন মাত্রা। নিজের ব্যবহারের পাশাপাশি এটি প্রিয়জনের জন্যও একটি চমৎকার উপহারের পছন্দ হতে পারে।
  </p>

  <h2>✨ প্রোডাক্টের বৈশিষ্ট্য</h2>
  <ul>
    <li>✅ ১০০% এক্সপোর্ট কোয়ালিটি</li>
    <li>✅ প্রিমিয়াম সফট ফেব্রিক</li>
    <li>✅ ত্বকবান্ধব ও আরামদায়ক</li>
    <li>✅ দীর্ঘদিন ব্যবহার উপযোগী</li>
    <li>✅ রঙ দীর্ঘদিন উজ্জ্বল থাকে</li>
    <li>✅ সহজে ধোয়া ও রক্ষণাবেক্ষণ করা যায়</li>
    <li>✅ আকর্ষণীয় ডিজাইন ও নিখুঁত ফিনিশিং</li>
    <li>✅ দৈনন্দিন ব্যবহার এবং বিশেষ উপলক্ষ—উভয়ের জন্য উপযুক্ত</li>
  </ul>

  <h2>💖 কেন আমাদের বেড শিট বেছে নেবেন?</h2>
  <p>
    আমরা মানের সঙ্গে কোনো আপস করি না। প্রতিটি বেড শিট যত্নসহকারে নির্বাচন করা হয়, যাতে আপনি পান সর্বোচ্চ মানের একটি পণ্য। আরাম, সৌন্দর্য এবং দীর্ঘস্থায়িত্ব—এই তিনটির নিখুঁত সমন্বয়ই আমাদের বেড শিটকে অন্যদের থেকে আলাদা করে। আপনার প্রতিটি রাতের ঘুমকে আরও স্বস্তিদায়ক এবং প্রতিটি সকালকে আরও সতেজ করে তুলতেই আমাদের এই প্রিমিয়াম কালেকশন।
  </p>

  <div class="cta">
    আজই অর্ডার করুন এবং ঘরে বসেই উপভোগ করুন এক্সপোর্ট কোয়ালিটির প্রিমিয়াম আরাম।
  </div>

HTML;
    }

    private function secondDescription(): string
    {
        return '<p>প্রিমিয়াম মানের এই বেড শিট আপনার ঘরে এনে দেবে হোটেল-মানের আরাম। উচ্চমানের ফেব্রিকের কারণে এটি গরম ও শীত—দুই মৌসুমেই ব্যবহার উপযোগী। যারা মান ও আরামের সাথে কোনো আপস করেন না, তাদের জন্য এটি একটি আদর্শ পছন্দ।</p>';
    }

    private function thirdDescription(): string
    {
        return <<<'HTML'
<p>আরাম, সৌন্দর্য এবং দীর্ঘস্থায়িত্ব—সবকিছু একসাথে পেতে বেছে নিন আমাদের এক্সপোর্ট কোয়ালিটির বেড শিট। উন্নত মানের কাপড়ে তৈরি হওয়ায় এটি ত্বকে আরামদায়ক অনুভূতি দেয় এবং প্রতিদিনের ব্যবহারে দীর্ঘদিন নতুনের মতো থাকে।</p>
<h3>কেন কিনবেন?</h3>
<ul>
    <li>🌿 ত্বকবান্ধব কাপড়</li>
    <li>🧺 সহজে পরিষ্কার করা যায়</li>
    <li>🎨 আকর্ষণীয় ডিজাইন ও রঙ</li>
    <li>🛌 আরামদায়ক ঘুমের নিশ্চয়তা</li>
    <li>⭐ প্রিমিয়াম এক্সপোর্ট মানের পণ্য</li>
</ul>
HTML;
    }
}
