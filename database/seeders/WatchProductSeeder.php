<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WatchProductSeeder extends Seeder
{
    private const string IMAGE = 'uploads/categories/watches.png';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::where('slug', 'watches')->firstOrFail();
        $user = User::first();

        /**
         * @var list<array{
         *     name: string,
         *     slug: string,
         *     regular_price: int,
         *     price: int,
         *     description: string,
         *     short_description: string,
         *     how_to_use: string,
         *     good_to_know: string,
         *     warranty: string,
         *     meta_keywords: string
         * }> $products
         */
        $products = [
            [
                'name' => 'Royal Kashmiri Kundan Floral Analog Bracelet Watch for Women',
                'slug' => 'royal-kashmiri-kundan-floral-analog-bracelet-watch-for-women',
                'regular_price' => 1300,
                'price' => 999,
                'description' => '<h2>Royal Kashmiri Kundan Floral Analog Bracelet Watch for Women</h2>
                    <p>Make every occasion more elegant with this beautifully designed Kashmiri-inspired bracelet watch. Combining the charm of traditional jewelry with the practicality of an analog wristwatch, this premium accessory is crafted to complement both ethnic and modern outfits.</p>

                    <p>Featuring intricate floral craftsmanship, a luxurious gold-tone finish, and colorful Kundan-style stones, this bracelet watch adds a graceful touch to weddings, parties, festivals, Eid celebrations, and everyday fashion.</p>

                    <p>The lightweight bracelet design ensures all-day comfort, while the reliable quartz analog movement provides accurate timekeeping. Whether paired with a saree, salwar kameez, lehenga, or western attire, this stunning bracelet watch is sure to enhance your style.</p>

                    <h3>Key Features</h3>

                    <ul>
                        <li>Premium gold-tone jewelry bracelet watch</li>
                        <li>Elegant Kashmiri-inspired floral craftsmanship</li>
                        <li>Beautiful Kundan-style decorative stones</li>
                        <li>Reliable quartz analog movement</li>
                        <li>Comfortable and lightweight design</li>
                        <li>Perfect for weddings, festivals, parties, and casual wear</li>
                        <li>An ideal gift for women, girls, wives, sisters, and loved ones</li>
                    </ul>

                    <p><strong>Package Includes:</strong> 1 × Royal Kashmiri Kundan Floral Analog Bracelet Watch</p>',
                'short_description' => 'A premium Kashmiri-inspired bracelet watch featuring elegant floral craftsmanship, gold-tone finish, colorful Kundan-style stones, and a reliable analog quartz movement. Perfect for weddings, parties, festivals, gifting, and everyday elegant wear.',
                'how_to_use' => 'Pull the crown gently to set the correct time, then push it back into place. Wear the bracelet comfortably and use the adjustable chain for a secure fit. Remove the watch before bathing, swimming, or applying perfume.',
                'good_to_know' => 'The Kundan-style stones and floral details are decorative and should be handled like jewelry. Keep the watch dry and away from chemicals, direct perfume, strong magnets, and hard impacts to preserve its finish.',
                'warranty' => '6 Months Service Warranty',
                'meta_keywords' => 'Kashmiri Kundan watch, floral bracelet watch, women\'s jewelry watch, gold-tone watch, gift watch for women',
            ],
            [
                'name' => 'Elegant Rose Gold Floral Strap Women\'s Analog Wrist Watch',
                'slug' => 'elegant-rose-gold-floral-strap-womens-analog-wrist-watch',
                'regular_price' => 1300,
                'price' => 999,
                'description' => '<p>Add a touch of elegance to your everyday style with this beautiful Rose Gold Floral Strap Analog Wrist Watch. Designed for modern women, this watch combines a premium rose gold metal case with a vibrant teal dial and a stylish printed strap inspired by traditional patterns. Its lightweight construction and comfortable fit make it ideal for daily wear, office use, casual outings, parties, and special occasions.</p>

<p>The high-quality quartz movement ensures accurate timekeeping, while the durable stainless steel back provides long-lasting performance. The soft printed strap offers excellent comfort for all-day wear, making this watch both fashionable and practical.</p>

<p>Whether you are looking for a stylish accessory for yourself or a thoughtful gift for someone special, this elegant wristwatch is the perfect choice. It complements both traditional and western outfits, adding a premium touch to every look.</p>

<h3>Key Features</h3>
<ul>
<li>Premium Rose Gold Metal Case</li>
<li>Beautiful Teal Analog Dial</li>
<li>Unique Printed Fashion Strap</li>
<li>Reliable Quartz Movement</li>
<li>Soft & Comfortable Strap</li>
<li>Stainless Steel Back Cover</li>
<li>Lightweight and Comfortable Design</li>
<li>Suitable for Daily Wear, Office & Parties</li>
<li>Perfect Gift for Women & Girls</li>
</ul>

<h3>Package Includes</h3>
<ul>
<li>1 × Women\'s Analog Wrist Watch</li>
<li>1 × Premium Gift Box</li>
</ul>',
                'short_description' => 'Elegant women\'s analog wrist watch featuring a premium rose gold case, teal dial, stylish printed floral strap, quartz movement, stainless steel back, and a comfortable lightweight design. Perfect for everyday wear, parties, office use, and gifting.',
                'how_to_use' => 'Set the time with the side crown, fasten the printed strap using the buckle, and adjust it so the watch sits comfortably on your wrist. Remove it before bathing, swimming, or washing with water.',
                'good_to_know' => 'The printed strap and rose-gold finish last longer when kept away from moisture, perfume, lotion, and direct sunlight. Clean the case and strap gently with a soft, dry cloth after use.',
                'warranty' => '6 Months Service Warranty',
                'meta_keywords' => 'rose gold women\'s watch, teal dial watch, floral strap watch, analog wrist watch, gift for women',
            ],
            [
                'name' => 'Elegant Elephant Rose Gold Women\'s Analog Wrist Watch',
                'slug' => 'elegant-elephant-rose-gold-womens-analog-wrist-watch',
                'regular_price' => 1300,
                'price' => 999,
                'description' => '<p>Celebrate timeless elegance with this beautifully crafted Elephant Rose Gold Analog Wrist Watch for Women. Designed with a graceful elephant motif on a sophisticated grey dial, this watch symbolizes wisdom, luck, and prosperity while adding a fashionable touch to your everyday style.</p>

<p>The premium rose gold metal case perfectly complements the artistic printed strap featuring colorful ethnic-inspired patterns. Powered by a reliable quartz movement, this watch delivers accurate timekeeping while maintaining a lightweight and comfortable fit for all-day wear. The durable strap and stainless steel back ensure lasting performance, making it suitable for work, casual outings, parties, and festive occasions.</p>

<p>Whether paired with western outfits or traditional attire, this elegant timepiece instantly enhances your look. It also makes a thoughtful gift for birthdays, anniversaries, festivals, Valentine\'s Day, Mother\'s Day, or any special occasion.</p>

<h3>Key Features</h3>
<ul>
<li>Elegant Elephant Artwork Dial Design</li>
<li>Premium Rose Gold Metal Case</li>
<li>Stylish Ethnic Printed Fashion Strap</li>
<li>High Precision Quartz Movement</li>
<li>Water Resistant for Everyday Use</li>
<li>Stainless Steel Back Cover</li>
<li>Soft, Durable & Comfortable Strap</li>
<li>Lightweight Design for Daily Wear</li>
<li>Perfect for Casual, Office & Party Wear</li>
<li>Ideal Gift for Women & Girls</li>
</ul>

<h3>Package Includes</h3>
<ul>
<li>1 × Women\'s Analog Wrist Watch</li>
<li>1 × Premium Gift Box</li>
</ul>',
                'short_description' => 'Stylish women\'s analog wrist watch featuring an elegant elephant dial, premium rose gold case, artistic ethnic printed strap, quartz movement, water-resistant design, and comfortable lightweight fit. Perfect for everyday wear, office, parties, and gifting.',
                'how_to_use' => 'Use the side crown to set the time and secure the printed strap with the buckle. The watch can handle light everyday splashes, but remove it before bathing, swimming, or prolonged contact with water.',
                'good_to_know' => 'This watch is water-resistant for normal daily splashes, not waterproof. Keep the crown closed and avoid perfume, chemicals, strong magnets, and heavy impact to protect the movement and rose-gold finish.',
                'warranty' => '6 Months Service Warranty',
                'meta_keywords' => 'elephant dial watch, rose gold women\'s watch, ethnic strap watch, water-resistant analog watch, gift watch',
            ],
            [
                'name' => 'Modern Silver Diamond Dial Women\'s Analog Wrist Watch',
                'slug' => 'modern-silver-diamond-dial-womens-analog-wrist-watch',
                'regular_price' => 1300,
                'price' => 999,
                'description' => '<p>Upgrade your everyday style with this Modern Silver Diamond Dial Analog Wrist Watch for Women. Featuring a unique diamond-shaped dial and a sleek silver-tone metal case, this elegant timepiece blends contemporary fashion with timeless sophistication. The minimalist grey dial and premium brown leather-style strap create a refined look that pairs beautifully with both casual and formal outfits.</p>

<p>Powered by a reliable quartz movement, this watch offers accurate timekeeping while remaining lightweight and comfortable throughout the day. The durable stainless steel back and soft leather-style strap ensure long-lasting performance and a secure fit, making it an excellent choice for office wear, parties, travel, or everyday use.</p>

<p>Its modern geometric design makes it a standout accessory and a thoughtful gift for birthdays, anniversaries, Valentine\'s Day, Mother\'s Day, or any special occasion.</p>

<h3>Key Features</h3>
<ul>
<li>Unique Diamond-Shaped Analog Dial</li>
<li>Premium Silver-Tone Metal Case</li>
<li>Elegant Minimalist Grey Dial</li>
<li>Soft & Comfortable Leather-Style Strap</li>
<li>Reliable Quartz Movement</li>
<li>Stainless Steel Back Cover</li>
<li>Lightweight & Comfortable for Daily Wear</li>
<li>Classic Buckle Closure</li>
<li>Suitable for Office, Casual & Party Wear</li>
<li>Perfect Gift for Women & Girls</li>
</ul>

<h3>Package Includes</h3>
<ul>
<li>1 × Women\'s Analog Wrist Watch</li>
<li>1 × Premium Gift Box</li>
</ul>',
                'short_description' => 'Elegant women\'s analog wrist watch featuring a unique diamond-shaped grey dial, silver-tone metal case, premium brown leather-style strap, quartz movement, stainless steel back, and lightweight design. Perfect for everyday wear, office, parties, and gifting.',
                'how_to_use' => 'Pull the side crown to set the time, push it back firmly, and fasten the leather-style strap with the buckle. Store the watch flat in its box when it is not being worn.',
                'good_to_know' => 'The leather-style strap should be kept dry and cleaned only with a soft cloth. Avoid soaking, perfume, excessive heat, strong magnets, and sharp bending to maintain the strap and silver-tone case.',
                'warranty' => '6 Months Service Warranty',
                'meta_keywords' => 'diamond dial watch, silver women\'s watch, brown strap watch, geometric analog watch, office watch for women',
            ],
            [
                'name' => 'Elegant Purple Silicone Strap Women\'s Analog Wrist Watch',
                'slug' => 'elegant-purple-silicone-strap-womens-analog-wrist-watch',
                'regular_price' => 1300,
                'price' => 999,
                'description' => '<p>Refresh your everyday style with this Elegant Purple Silicone Strap Analog Wrist Watch for Women. Designed with a soft purple dial, matching silicone strap, and a lightweight white case, this fashionable timepiece offers the perfect blend of comfort and elegance. The minimalist dial with stylish numeric markers makes it easy to read while adding a modern touch to your outfit.</p>

<p>Powered by a reliable quartz movement, this watch provides precise timekeeping for daily use. The premium silicone strap is soft, flexible, and skin-friendly, ensuring maximum comfort throughout the day. Its lightweight construction makes it ideal for office wear, college, casual outings, travel, and everyday activities.</p>

<p>Whether you\'re dressing up for work or enjoying a weekend outing, this elegant wristwatch complements any style. It also makes a wonderful gift for birthdays, anniversaries, Valentine\'s Day, Mother\'s Day, or other special occasions.</p>

<h3>Key Features</h3>
<ul>
<li>Elegant Purple Analog Dial</li>
<li>Premium Lightweight White Case</li>
<li>Soft & Comfortable Silicone Strap</li>
<li>Accurate Quartz Movement</li>
<li>Easy-to-Read Stylish Number Markers</li>
<li>Durable & Lightweight Construction</li>
<li>Comfortable for All-Day Wear</li>
<li>Classic Buckle Closure</li>
<li>Perfect for Daily, Office & Casual Wear</li>
<li>Ideal Gift for Women & Girls</li>
</ul>

<h3>Package Includes</h3>
<ul>
<li>1 × Women\'s Analog Wrist Watch</li>
<li>1 × Premium Gift Box</li>
</ul>',
                'short_description' => 'Elegant women\'s analog wrist watch featuring a stylish purple dial, lightweight white case, soft silicone strap, reliable quartz movement, and comfortable everyday design. Perfect for office, college, casual wear, and gifting.',
                'how_to_use' => 'Set the time using the side crown and secure the silicone strap with the buckle at a comfortable position. Wipe the strap after daily use and remove the watch before bathing or swimming.',
                'good_to_know' => 'The soft silicone strap is suitable for regular wear and can be cleaned with a slightly damp cloth, then dried immediately. Avoid harsh cleaners, perfume, direct heat, and prolonged water exposure.',
                'warranty' => '6 Months Service Warranty',
                'meta_keywords' => 'purple women\'s watch, silicone strap watch, lightweight analog watch, casual watch for women, college watch',
            ],
            [
                'name' => 'Luxury Pearl Bracelet Analog Watch for Women',
                'slug' => 'luxury-pearl-bracelet-analog-watch-for-women',
                'regular_price' => 1300,
                'price' => 999,
                'description' => '<p>Experience the perfect combination of luxury jewelry and everyday functionality with this Luxury Pearl Bracelet Analog Watch for Women. Designed to look like an elegant bracelet while offering reliable timekeeping, this beautiful timepiece features a sparkling crystal-studded bezel, premium pearl-textured bracelet, and stunning blue enamel bird accents that create a sophisticated and fashionable look.</p>

<p>The precision quartz movement ensures accurate timekeeping, while the lightweight construction and adjustable bracelet provide exceptional comfort throughout the day. Crafted with premium materials and finished in an elegant gold-tone design, this watch effortlessly complements traditional attire, western outfits, party dresses, and formal wear.</p>

<p>Whether you\'re attending a wedding, celebrating a special occasion, or looking for the perfect gift, this bracelet watch adds timeless elegance to every outfit. Its luxurious appearance makes it an excellent gift for birthdays, anniversaries, Valentine\'s Day, Mother\'s Day, Eid, or any memorable celebration.</p>

<h3>Key Features</h3>
<ul>
<li>Elegant Bracelet Watch Design</li>
<li>Sparkling Crystal-Studded Bezel</li>
<li>Premium Pearl-Textured Bracelet</li>
<li>Beautiful Blue Enamel Bird Detailing</li>
<li>Reliable Quartz Movement</li>
<li>Adjustable Chain for Comfortable Fit</li>
<li>Lightweight & Comfortable for All-Day Wear</li>
<li>Luxury Gold-Tone Finish</li>
<li>Perfect for Weddings, Parties & Daily Wear</li>
<li>Ideal Gift for Women & Girls</li>
</ul>

<h3>Package Includes</h3>
<ul>
<li>1 × Women\'s Bracelet Analog Watch</li>
<li>1 × Premium Gift Box</li>
</ul>',
                'short_description' => 'Luxury women\'s bracelet analog watch featuring a crystal-studded bezel, pearl-textured bracelet, elegant blue enamel bird accents, quartz movement, adjustable chain, and lightweight premium design. Perfect for parties, weddings, daily wear, and gifting.',
                'how_to_use' => 'Set the time gently with the crown, place the bracelet around your wrist, and secure the adjustable chain without pulling the pearl or bird details. Put it back in the gift box after use.',
                'good_to_know' => 'The pearl-textured bracelet, crystals, and enamel bird accents are delicate decorative elements. Keep them away from water, perfume, lotion, chemicals, and friction to protect their color and shine.',
                'warranty' => '6 Months Service Warranty',
                'meta_keywords' => 'pearl bracelet watch, blue bird watch, crystal women\'s watch, luxury jewelry watch, wedding gift watch',
            ],
            [
                'name' => 'Royal Kundan Floral Bracelet Watch for Women',
                'slug' => 'royal-kundan-floral-bracelet-watch-for-women',
                'regular_price' => 1300,
                'price' => 999,
                'description' => '<p>Celebrate timeless elegance with this Royal Kundan Floral Bracelet Watch for Women. Beautifully crafted to resemble a luxurious traditional bracelet, this exquisite timepiece combines classic jewelry with reliable timekeeping. Featuring dazzling Kundan-inspired floral motifs, sparkling crystal accents, and a premium gold-tone finish, this watch is the perfect accessory for weddings, festivals, and special occasions.</p>

<p>The precision quartz movement ensures accurate timekeeping, while the lightweight bracelet design offers exceptional comfort for all-day wear. Delicate pearl drop charms and adjustable chain detailing enhance its graceful appearance, making it a perfect match for sarees, lehengas, salwar suits, gowns, and other ethnic or party outfits.</p>

<p>Whether you are attending a wedding, celebrating Eid, Puja, Diwali, or looking for a memorable gift, this elegant bracelet watch adds a touch of royal charm to every look. It is an excellent gift choice for wives, sisters, mothers, daughters, or friends.</p>

<h3>Key Features</h3>
<ul>
<li>Elegant Bracelet Watch Design</li>
<li>Premium Gold-Tone Finish</li>
<li>Beautiful Kundan Floral Motifs</li>
<li>Sparkling Crystal-Studded Dial</li>
<li>Reliable Quartz Movement</li>
<li>Adjustable Chain for Perfect Fit</li>
<li>Lightweight & Comfortable Design</li>
<li>Decorative Pearl Drop Charms</li>
<li>Ideal for Weddings, Festivals & Parties</li>
<li>Perfect Gift for Women & Girls</li>
</ul>

<h3>Package Includes</h3>
<ul>
<li>1 × Women\'s Bracelet Analog Watch</li>
<li>1 × Premium Gift Box</li>
</ul>',
                'short_description' => 'Elegant women\'s bracelet watch featuring premium Kundan floral detailing, crystal-studded dial, gold-tone finish, quartz movement, adjustable chain, and pearl drop charms. Perfect for weddings, festivals, parties, and gifting.',
                'how_to_use' => 'Set the time with the crown, wrap the bracelet around your wrist, and fasten the adjustable chain securely. Handle the pearl drop charms gently and store the watch separately after wearing it.',
                'good_to_know' => 'The Kundan floral motifs, crystals, and pearl charms require jewelry-style care. Avoid water, perfume, lotion, chemicals, pulling, and hard impact so the gold-tone finish and decorative pieces remain intact.',
                'warranty' => '6 Months Service Warranty',
                'meta_keywords' => 'Kundan bracelet watch, floral jewelry watch, pearl charm watch, gold-tone women\'s watch, bridal gift watch',
            ],

        ];

        DB::transaction(function () use ($category, $products, $user): void {
            foreach ($products as $index => $productData) {
                $number = $index + 1;
                $discountAmount = $productData['regular_price'] - $productData['price'];

                $product = Product::updateOrCreate(
                    ['slug' => $productData['slug']],
                    [
                        'name' => $productData['name'],
                        'landing_page_slug' => $productData['slug'].'-deal',
                        'sku' => 'WATCH-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                        'short_description' => $productData['short_description'],
                        'description' => $productData['description'],
                        'how_to_use' => $productData['how_to_use'],
                        'good_to_know' => $productData['good_to_know'],
                        'warranty' => $productData['warranty'],
                        'status' => 'published',
                        'visibility' => 'public',
                        'published_at' => now(),
                        'thumbnail_image' => self::IMAGE,
                        'images' => json_encode([self::IMAGE], JSON_THROW_ON_ERROR),
                        'video_media' => null,
                        'quantity' => 30,
                        'stock_status' => 'in_stock',
                        'product_location' => 'store',
                        'minimum_order_quantity' => 1,
                        'maximum_order_quantity' => 5,
                        'low_stock_alert' => 5,
                        'purchase_price' => round($productData['price'] * 0.65, 2),
                        'regular_price' => $productData['regular_price'],
                        'price' => $productData['price'],
                        'discount_amount' => $discountAmount,
                        'discount_percentage' => round(($discountAmount / $productData['regular_price']) * 100, 2),
                        'discount_start_date' => now()->toDateString(),
                        'discount_end_date' => now()->addMonths(3)->toDateString(),
                        'is_discounted' => true,
                        'is_featured' => $index < 3,
                        'is_new' => true,
                        'is_best_seller' => in_array($index, [0, 2, 4, 9], true),
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
                        'meta_description' => $productData['short_description'],
                        'meta_keywords' => $productData['meta_keywords'],
                        'meta_image' => self::IMAGE,
                    ]
                );

                // These products intentionally have no variants, including after re-seeding.
                $product->variants()->delete();
            }
        });

        $this->command?->info(count($products).' watch products seeded without variants.');
    }
}
