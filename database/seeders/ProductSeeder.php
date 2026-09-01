<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VariantAttribute;
use App\Models\VariantAttributeValue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private const IMAGE = 'images/categroy.webp';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        $brands = $this->seedBrands();
        $categories = $this->seedCategories();
        $attributes = $this->seedVariantAttributes();

        $products = [
            [
                'name' => 'Aether Wireless Headphones',
                'brand' => 'sony',
                'category' => 'headphones',
                'regular_price' => 129.00,
                'price' => 99.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => '1 Year Manufacturer Warranty',
                'how_to_use' => 'Charge fully before first use. Pair via Bluetooth from your device settings.',
                'good_to_know' => 'Supports ANC mode and multipoint pairing with two devices.',
                'num_of_reviews' => 128,
                'reviews_avg' => 4.6,
                'short_description' => 'Noise-cancelling over-ear headphones with 30-hour battery life.',
                'description' => 'Aether Wireless Headphones deliver deep bass, clear mids, and active noise cancellation for commute and studio use.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'Standard'],
                    ['Color' => 'White', 'Size' => 'Standard'],
                    ['Color' => 'Blue', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Nova Pro Smartphone',
                'brand' => 'samsung',
                'category' => 'mobile-phones',
                'regular_price' => 899.00,
                'price' => 849.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 7.5,
                'product_location' => 'store',
                'warranty' => '2 Years Official Warranty',
                'how_to_use' => 'Insert SIM, power on, and complete the on-screen setup wizard.',
                'good_to_know' => 'Includes charger and USB-C cable. Dual SIM supported.',
                'num_of_reviews' => 312,
                'reviews_avg' => 4.8,
                'short_description' => 'Flagship smartphone with OLED display and triple camera.',
                'description' => 'Nova Pro Smartphone features a bright OLED panel, fast charging, and an all-day battery for work and play.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => '128GB'],
                    ['Color' => 'Black', 'Size' => '256GB'],
                    ['Color' => 'Blue', 'Size' => '128GB'],
                    ['Color' => 'White', 'Size' => '256GB'],
                ],
            ],
            [
                'name' => 'Pulse Running Shoes',
                'brand' => 'nike',
                'category' => 'shoes',
                'regular_price' => 140.00,
                'price' => 140.00,
                'is_discounted' => false,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => '6 Months Quality Guarantee',
                'how_to_use' => 'Wear with athletic socks. Allow shoes to air-dry after wet runs.',
                'good_to_know' => 'True to size. Suitable for road running and gym workouts.',
                'num_of_reviews' => 87,
                'reviews_avg' => 4.4,
                'short_description' => 'Lightweight running shoes built for daily training.',
                'description' => 'Pulse Running Shoes combine cushioned midsoles and breathable mesh for comfort on long runs.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'S'],
                    ['Color' => 'Black', 'Size' => 'M'],
                    ['Color' => 'Black', 'Size' => 'L'],
                    ['Color' => 'Red', 'Size' => 'M'],
                    ['Color' => 'Red', 'Size' => 'L'],
                ],
            ],
            [
                'name' => 'Urban Cotton Tee',
                'brand' => 'adidas',
                'category' => 'men-fashion',
                'regular_price' => 35.00,
                'price' => 29.00,
                'is_discounted' => true,
                'is_new' => false,
                'is_featured' => false,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'store',
                'warranty' => 'No Warranty',
                'how_to_use' => 'Machine wash cold. Do not bleach. Tumble dry low.',
                'good_to_know' => '100% cotton. Slight shrinkage may occur after first wash.',
                'num_of_reviews' => 54,
                'reviews_avg' => 4.2,
                'short_description' => 'Soft cotton t-shirt with a relaxed everyday fit.',
                'description' => 'Urban Cotton Tee is made from breathable cotton and designed for all-day comfort.',
                'variants' => [
                    ['Color' => 'White', 'Size' => 'S'],
                    ['Color' => 'White', 'Size' => 'M'],
                    ['Color' => 'White', 'Size' => 'L'],
                    ['Color' => 'Black', 'Size' => 'M'],
                    ['Color' => 'Black', 'Size' => 'L'],
                    ['Color' => 'Blue', 'Size' => 'M'],
                ],
            ],
            [
                'name' => 'Lumen Smart Watch',
                'brand' => 'apple',
                'category' => 'watches',
                'regular_price' => 399.00,
                'price' => 399.00,
                'is_discounted' => false,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 7.5,
                'product_location' => 'store',
                'warranty' => '1 Year Limited Warranty',
                'how_to_use' => 'Charge overnight, pair with the companion app, and set your fitness goals.',
                'good_to_know' => 'Water resistant up to 50m. Heart-rate and SpO2 sensors included.',
                'num_of_reviews' => 201,
                'reviews_avg' => 4.7,
                'short_description' => 'Fitness-focused smartwatch with heart-rate tracking.',
                'description' => 'Lumen Smart Watch tracks workouts, sleep, and notifications from a bright always-on display.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'S'],
                    ['Color' => 'Black', 'Size' => 'M'],
                    ['Color' => 'White', 'Size' => 'M'],
                    ['Color' => 'Blue', 'Size' => 'L'],
                ],
            ],
            [
                'name' => 'Orbit Ultrabook 14',
                'brand' => 'apple',
                'category' => 'laptops',
                'regular_price' => 1299.00,
                'price' => 1199.00,
                'is_discounted' => true,
                'is_new' => false,
                'is_featured' => true,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 10,
                'product_location' => 'warehouse',
                'warranty' => '1 Year International Warranty',
                'how_to_use' => 'Charge before first boot. Sign in with your account and restore from backup if needed.',
                'good_to_know' => 'Includes USB-C charger. External display support via USB-C/Thunderbolt.',
                'num_of_reviews' => 96,
                'reviews_avg' => 4.5,
                'short_description' => 'Thin 14-inch laptop for creators and professionals.',
                'description' => 'Orbit Ultrabook 14 pairs a sharp display with long battery life and silent cooling for focused work.',
                'variants' => [
                    ['Color' => 'Silver', 'Size' => '256GB'],
                    ['Color' => 'Silver', 'Size' => '512GB'],
                    ['Color' => 'Black', 'Size' => '512GB'],
                ],
            ],
            [
                'name' => 'Glow Serum Set',
                'brand' => 'loreal',
                'category' => 'beauty',
                'regular_price' => 48.00,
                'price' => 48.00,
                'is_discounted' => false,
                'is_new' => true,
                'is_featured' => false,
                'is_best_seller' => false,
                'unit' => 'Ml',
                'tax_rate' => 5,
                'product_location' => 'store',
                'warranty' => 'No Warranty',
                'how_to_use' => 'Apply 2-3 drops on clean face morning and night. Follow with moisturizer.',
                'good_to_know' => 'Patch test recommended. Avoid contact with eyes.',
                'num_of_reviews' => 63,
                'reviews_avg' => 4.3,
                'short_description' => 'Hydrating serum set for daily skincare routines.',
                'description' => 'Glow Serum Set nourishes skin with lightweight hydration and a soft finish.',
                'variants' => [
                    ['Color' => 'Clear', 'Size' => '30ml'],
                    ['Color' => 'Clear', 'Size' => '50ml'],
                ],
            ],
            [
                'name' => 'Trail Daypack',
                'brand' => 'nike',
                'category' => 'bags',
                'regular_price' => 79.00,
                'price' => 69.00,
                'is_discounted' => true,
                'is_new' => false,
                'is_featured' => false,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => '6 Months Against Manufacturing Defects',
                'how_to_use' => 'Adjust shoulder straps for a snug fit. Wipe clean with a damp cloth.',
                'good_to_know' => 'Fits up to 15-inch laptops. Water-resistant outer shell.',
                'num_of_reviews' => 41,
                'reviews_avg' => 4.1,
                'short_description' => 'Durable daypack with padded laptop sleeve.',
                'description' => 'Trail Daypack keeps gear organized with multiple pockets and water-resistant fabric.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'Standard'],
                    ['Color' => 'Green', 'Size' => 'Standard'],
                    ['Color' => 'Blue', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Studio Mirrorless Camera',
                'brand' => 'sony',
                'category' => 'cameras',
                'regular_price' => 1499.00,
                'price' => 1399.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 10,
                'product_location' => 'store',
                'warranty' => '2 Years Official Warranty',
                'how_to_use' => 'Insert battery and memory card, attach lens, then set shooting mode.',
                'good_to_know' => 'Body only. Lens sold separately. 4K video capable.',
                'num_of_reviews' => 74,
                'reviews_avg' => 4.7,
                'short_description' => 'Compact mirrorless camera for photo and video creators.',
                'description' => 'Studio Mirrorless Camera offers fast autofocus, high resolution stills, and reliable low-light performance.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'Standard'],
                    ['Color' => 'Silver', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Apex Wireless Controller',
                'brand' => 'samsung',
                'category' => 'gaming',
                'regular_price' => 69.00,
                'price' => 59.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => false,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => '1 Year Warranty',
                'how_to_use' => 'Charge via USB-C, pair in Bluetooth mode, and map buttons in the companion app.',
                'good_to_know' => 'Compatible with PC, Android, and selected consoles.',
                'num_of_reviews' => 156,
                'reviews_avg' => 4.5,
                'short_description' => 'Responsive wireless game controller with haptic feedback.',
                'description' => 'Apex Wireless Controller delivers precise analog sticks, programmable buttons, and long battery life.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'Standard'],
                    ['Color' => 'White', 'Size' => 'Standard'],
                    ['Color' => 'Red', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Silk Blouse Classic',
                'brand' => 'adidas',
                'category' => 'women-fashion',
                'regular_price' => 85.00,
                'price' => 72.00,
                'is_discounted' => true,
                'is_new' => false,
                'is_featured' => true,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'store',
                'warranty' => 'No Warranty',
                'how_to_use' => 'Dry clean only. Iron on low heat with a pressing cloth.',
                'good_to_know' => 'Lightweight silk blend. Fits true to size.',
                'num_of_reviews' => 38,
                'reviews_avg' => 4.4,
                'short_description' => 'Elegant silk-blend blouse for office and evening wear.',
                'description' => 'Silk Blouse Classic features a soft drape, button front, and breathable fabric for all-day comfort.',
                'variants' => [
                    ['Color' => 'White', 'Size' => 'S'],
                    ['Color' => 'White', 'Size' => 'M'],
                    ['Color' => 'Black', 'Size' => 'M'],
                    ['Color' => 'Black', 'Size' => 'L'],
                    ['Color' => 'Blue', 'Size' => 'M'],
                ],
            ],
            [
                'name' => 'Aura Gold Pendant',
                'brand' => 'loreal',
                'category' => 'jewelry',
                'regular_price' => 220.00,
                'price' => 199.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 7.5,
                'product_location' => 'store',
                'warranty' => 'Lifetime Lifetime Guarantee',
                'how_to_use' => 'Keep dry. Store in the provided pouch when not worn.',
                'good_to_know' => 'Hypoallergenic plating. Chain length adjustable.',
                'num_of_reviews' => 29,
                'reviews_avg' => 4.6,
                'short_description' => 'Minimal gold-tone pendant with adjustable chain.',
                'description' => 'Aura Gold Pendant adds a refined everyday accent with durable plating and a secure clasp.',
                'variants' => [
                    ['Color' => 'Gold', 'Size' => 'Standard'],
                    ['Color' => 'Silver', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Flex Resistance Band Set',
                'brand' => 'nike',
                'category' => 'sports',
                'regular_price' => 45.00,
                'price' => 45.00,
                'is_discounted' => false,
                'is_new' => false,
                'is_featured' => false,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => '6 Months Against Tears',
                'how_to_use' => 'Anchor securely before stretching. Start with the lightest band.',
                'good_to_know' => 'Includes light, medium, and heavy bands with carrying pouch.',
                'num_of_reviews' => 112,
                'reviews_avg' => 4.3,
                'short_description' => 'Multi-level resistance bands for home workouts.',
                'description' => 'Flex Resistance Band Set helps build strength and mobility with progressive tension levels.',
                'variants' => [
                    ['Color' => 'Green', 'Size' => 'Standard'],
                    ['Color' => 'Blue', 'Size' => 'Standard'],
                    ['Color' => 'Red', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Nest Ceramic Cookware Set',
                'brand' => 'samsung',
                'category' => 'home-kitchen',
                'regular_price' => 189.00,
                'price' => 159.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => '2 Years Against Manufacturing Defects',
                'how_to_use' => 'Hand wash recommended. Avoid metal utensils on ceramic coating.',
                'good_to_know' => 'Oven-safe up to 200°C. Compatible with gas and induction.',
                'num_of_reviews' => 67,
                'reviews_avg' => 4.5,
                'short_description' => 'Non-stick ceramic cookware set for everyday cooking.',
                'description' => 'Nest Ceramic Cookware Set includes pans and pots with even heat distribution and easy cleanup.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'Standard'],
                    ['Color' => 'White', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Cloud Lounge Chair',
                'brand' => 'apple',
                'category' => 'furniture',
                'regular_price' => 349.00,
                'price' => 299.00,
                'is_discounted' => true,
                'is_new' => false,
                'is_featured' => true,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 7.5,
                'product_location' => 'warehouse',
                'warranty' => '3 Years Frame Warranty',
                'how_to_use' => 'Assemble base and seat following the included guide. Tighten bolts after 48 hours.',
                'good_to_know' => 'Supports up to 120kg. Soft fabric cover is removable.',
                'num_of_reviews' => 44,
                'reviews_avg' => 4.4,
                'short_description' => 'Comfortable lounge chair with supportive cushioning.',
                'description' => 'Cloud Lounge Chair brings soft seating and a stable frame for living rooms and reading corners.',
                'variants' => [
                    ['Color' => 'Grey', 'Size' => 'Standard'],
                    ['Color' => 'Beige', 'Size' => 'Standard'],
                    ['Color' => 'Black', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'PrintStream Desk Printer',
                'brand' => 'sony',
                'category' => 'office',
                'regular_price' => 129.00,
                'price' => 119.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => false,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'store',
                'warranty' => '1 Year Service Warranty',
                'how_to_use' => 'Install ink cartridges, load paper, then connect via Wi-Fi setup.',
                'good_to_know' => 'Prints up to A4. Mobile printing supported.',
                'num_of_reviews' => 58,
                'reviews_avg' => 4.1,
                'short_description' => 'Compact wireless printer for home and small offices.',
                'description' => 'PrintStream Desk Printer delivers crisp documents with quiet operation and easy wireless setup.',
                'variants' => [
                    ['Color' => 'White', 'Size' => 'Standard'],
                    ['Color' => 'Black', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'Summit Camping Tent',
                'brand' => 'nike',
                'category' => 'outdoor',
                'regular_price' => 249.00,
                'price' => 219.00,
                'is_discounted' => true,
                'is_new' => false,
                'is_featured' => true,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => '1 Year Against Material Defects',
                'how_to_use' => 'Lay out footprint, assemble poles, stake corners, and tension guy lines.',
                'good_to_know' => 'Sleeps 3. Waterproof rating 3000mm. Includes carry bag.',
                'num_of_reviews' => 91,
                'reviews_avg' => 4.6,
                'short_description' => 'Weather-ready 3-person tent for weekend camping.',
                'description' => 'Summit Camping Tent sets up quickly and keeps gear dry with a sealed rainfly and reinforced seams.',
                'variants' => [
                    ['Color' => 'Green', 'Size' => 'Standard'],
                    ['Color' => 'Blue', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'DriveCare Car Vacuum',
                'brand' => 'samsung',
                'category' => 'automotive',
                'regular_price' => 59.00,
                'price' => 49.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => false,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'store',
                'warranty' => '1 Year Warranty',
                'how_to_use' => 'Plug into 12V socket, select nozzle, and vacuum seats and floor mats.',
                'good_to_know' => 'Includes crevice and brush attachments. Empty dust cup after each use.',
                'num_of_reviews' => 77,
                'reviews_avg' => 4.2,
                'short_description' => 'Portable car vacuum for quick interior cleaning.',
                'description' => 'DriveCare Car Vacuum is compact, powerful, and designed for glove-box storage between trips.',
                'variants' => [
                    ['Color' => 'Black', 'Size' => 'Standard'],
                    ['Color' => 'Red', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'LittleNest Baby Carrier',
                'brand' => 'adidas',
                'category' => 'baby',
                'regular_price' => 110.00,
                'price' => 99.00,
                'is_discounted' => true,
                'is_new' => true,
                'is_featured' => true,
                'is_best_seller' => true,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'store',
                'warranty' => '1 Year Safety Warranty',
                'how_to_use' => 'Adjust straps to fit, secure baby facing in or out, and tighten waist belt.',
                'good_to_know' => 'Suitable from 3.5kg to 15kg. Machine-washable soft panel.',
                'num_of_reviews' => 83,
                'reviews_avg' => 4.7,
                'short_description' => 'Ergonomic baby carrier for comfortable everyday carry.',
                'description' => 'LittleNest Baby Carrier supports healthy hip positioning with padded straps and breathable mesh.',
                'variants' => [
                    ['Color' => 'Grey', 'Size' => 'Standard'],
                    ['Color' => 'Blue', 'Size' => 'Standard'],
                    ['Color' => 'Black', 'Size' => 'Standard'],
                ],
            ],
            [
                'name' => 'PetGo Travel Bowl Set',
                'brand' => 'nike',
                'category' => 'pet',
                'regular_price' => 28.00,
                'price' => 24.00,
                'is_discounted' => true,
                'is_new' => false,
                'is_featured' => false,
                'is_best_seller' => false,
                'unit' => 'Pcs',
                'tax_rate' => 5,
                'product_location' => 'warehouse',
                'warranty' => 'No Warranty',
                'how_to_use' => 'Unfold bowls, fill with food or water, and rinse after use.',
                'good_to_know' => 'Food-grade silicone. Collapses flat for bags and backpacks.',
                'num_of_reviews' => 35,
                'reviews_avg' => 4.0,
                'short_description' => 'Collapsible silicone bowls for pets on the go.',
                'description' => 'PetGo Travel Bowl Set makes feeding and watering pets simple during walks and trips.',
                'variants' => [
                    ['Color' => 'Blue', 'Size' => 'Standard'],
                    ['Color' => 'Green', 'Size' => 'Standard'],
                    ['Color' => 'Pink', 'Size' => 'Standard'],
                ],
            ],
        ];

        foreach ($products as $index => $productData) {
            $brand = $brands[$productData['brand']] ?? $brands->first();
            $category = $categories[$productData['category']] ?? $categories->first();
            $slug = Str::slug($productData['name']);
            $sku = 'PROD-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $landingPageSlug = $slug . '-deal';

            $isDiscounted = (bool) ($productData['is_discounted'] ?? false);
            $discountAmount = $isDiscounted
                ? round($productData['regular_price'] - $productData['price'], 2)
                : 0;
            $discountPercentage = $isDiscounted && $productData['regular_price'] > 0
                ? round(($discountAmount / $productData['regular_price']) * 100, 2)
                : 0;

            $galleryImages = [
                self::IMAGE,
                self::IMAGE,
                self::IMAGE,
            ];

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    // Basic
                    'name' => $productData['name'],
                    'landing_page_slug' => $landingPageSlug,
                    'sku' => $sku,
                    'short_description' => $productData['short_description'],
                    'description' => $productData['description'],
                    'how_to_use' => $productData['how_to_use'],
                    'good_to_know' => $productData['good_to_know'],
                    'warranty' => $productData['warranty'],

                    // Status
                    'status' => 'published',
                    'visibility' => 'public',
                    'published_at' => now()->subDays(rand(1, 45)),

                    // Media
                    'thumbnail_image' => self::IMAGE,
                    'images' => json_encode($galleryImages),
                    'video_media' => null,

                    // Inventory
                    'quantity' => 0,
                    'stock_status' => 'in_stock',
                    'product_location' => $productData['product_location'] ?? 'warehouse',
                    'minimum_order_quantity' => 1,
                    'maximum_order_quantity' => rand(5, 20),
                    'low_stock_alert' => rand(3, 10),

                    // Pricing
                    'purchase_price' => round($productData['price'] * 0.6, 2),
                    'regular_price' => $productData['regular_price'],
                    'price' => $productData['price'],
                    'discount_amount' => $discountAmount,
                    'discount_percentage' => $discountPercentage,
                    'discount_start_date' => $isDiscounted ? Carbon::now()->subDays(rand(1, 14)) : null,
                    'discount_end_date' => $isDiscounted ? Carbon::now()->addDays(rand(30, 90)) : null,
                    'is_discounted' => $isDiscounted,

                    // Flags
                    'is_featured' => $productData['is_featured'] ?? false,
                    'is_new' => $productData['is_new'] ?? false,
                    'is_best_seller' => $productData['is_best_seller'] ?? false,

                    // Relations
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'unit' => $productData['unit'] ?? 'Pcs',
                    'tax_rate' => $productData['tax_rate'] ?? 5,

                    // Users
                    'created_by_id' => $user?->id,
                    'approved_by_id' => $user?->id,
                    'updated_by_id' => $user?->id,
                    'deleted_by_id' => null,

                    // Stats
                    'num_of_sale' => rand(20, 400),
                    'num_of_views' => rand(200, 3500),
                    'num_of_reviews' => $productData['num_of_reviews'] ?? 0,
                    'reviews_avg' => $productData['reviews_avg'] ?? 0,

                    // SEO
                    'meta_title' => $productData['name'] . ' | Agonito',
                    'meta_description' => $productData['short_description'],
                    'meta_keywords' => implode(', ', array_filter(explode(' ', strtolower($productData['name'])))),
                    'meta_image' => self::IMAGE,
                ]
            );

            $this->seedProductVariants(
                $product,
                $productData['variants'] ?? [],
                $attributes,
                $productData['price'],
                $sku
            );
        }

        $this->command?->info('20 products seeded with brands, categories, variants, and all product fields.');
    }

    /**
     * @return Collection<string, Brand>
     */
    protected function seedBrands(): Collection
    {
        $items = [
            ['name' => 'Sony', 'slug' => 'sony', 'is_featured' => true],
            ['name' => 'Samsung', 'slug' => 'samsung', 'is_featured' => true],
            ['name' => 'Nike', 'slug' => 'nike', 'is_featured' => true],
            ['name' => 'Adidas', 'slug' => 'adidas', 'is_featured' => false],
            ['name' => 'Apple', 'slug' => 'apple', 'is_featured' => true],
            ['name' => "L'Oreal", 'slug' => 'loreal', 'is_featured' => false],
        ];

        return collect($items)->mapWithKeys(function (array $item, int $index) {
            $brand = Brand::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['name'] . ' brand products.',
                    'logo' => self::IMAGE,
                    'status' => true,
                    'sort_order' => $index + 1,
                    'is_featured' => $item['is_featured'],
                ]
            );

            return [$item['slug'] => $brand];
        });
    }

    /**
     * @return Collection<string, Category>
     */
    protected function seedCategories(): Collection
    {
        $names = [
            'Mobile Phones',
            'Laptops',
            'Headphones',
            'Cameras',
            'Gaming',
            'Watches',
            'Men Fashion',
            'Women Fashion',
            'Shoes',
            'Bags',
            'Jewelry',
            'Beauty',
            'Home & Kitchen',
            'Furniture',
            'Office',
            'Sports',
            'Outdoor',
            'Automotive',
            'Baby',
            'Pet',
        ];

        return collect($names)->mapWithKeys(function (string $name) {
            $slug = Str::slug($name);
            $category = Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'parent_id' => null,
                    'name' => $name,
                    'image' => self::IMAGE,
                    'description' => $name . ' category.',
                    'meta_title' => $name,
                    'meta_description' => 'Browse the best ' . strtolower($name) . ' products.',
                    'meta_keywords' => strtolower(str_replace(' ', ',', $name)),
                    'meta_image' => self::IMAGE,
                    'is_active' => true,
                    'is_featured' => true,
                    'is_popular' => true,
                ]
            );

            return [$slug => $category];
        });
    }

    /**
     * @return array{Color: VariantAttribute, Size: VariantAttribute}
     */
    protected function seedVariantAttributes(): array
    {
        $color = VariantAttribute::updateOrCreate(
            ['slug' => 'color'],
            ['name' => 'Color', 'is_active' => true]
        );

        $size = VariantAttribute::updateOrCreate(
            ['slug' => 'size'],
            ['name' => 'Size', 'is_active' => true]
        );

        $colors = [
            ['value' => 'Black', 'color_code' => '#111111'],
            ['value' => 'White', 'color_code' => '#FFFFFF'],
            ['value' => 'Blue', 'color_code' => '#2563EB'],
            ['value' => 'Red', 'color_code' => '#DC2626'],
            ['value' => 'Green', 'color_code' => '#16A34A'],
            ['value' => 'Silver', 'color_code' => '#C0C0C0'],
            ['value' => 'Clear', 'color_code' => '#F3F4F6'],
            ['value' => 'Gold', 'color_code' => '#D4AF37'],
            ['value' => 'Grey', 'color_code' => '#6B7280'],
            ['value' => 'Beige', 'color_code' => '#D6C6A8'],
            ['value' => 'Pink', 'color_code' => '#EC4899'],
        ];

        foreach ($colors as $index => $colorValue) {
            VariantAttributeValue::updateOrCreate(
                [
                    'variant_attribute_id' => $color->id,
                    'slug' => Str::slug($colorValue['value']),
                ],
                [
                    'value' => $colorValue['value'],
                    'color_code' => $colorValue['color_code'],
                    'sort_order' => $index + 1,
                ]
            );
        }

        $sizes = ['S', 'M', 'L', 'XL', 'Standard', '30ml', '50ml', '128GB', '256GB', '512GB'];

        foreach ($sizes as $index => $sizeValue) {
            VariantAttributeValue::updateOrCreate(
                [
                    'variant_attribute_id' => $size->id,
                    'slug' => Str::slug($sizeValue),
                ],
                [
                    'value' => $sizeValue,
                    'color_code' => null,
                    'sort_order' => $index + 1,
                ]
            );
        }

        $color->load('values');
        $size->load('values');

        return [
            'Color' => $color,
            'Size' => $size,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $variantRows
     * @param  array{Color: VariantAttribute, Size: VariantAttribute}  $attributes
     */
    protected function seedProductVariants(
        Product $product,
        array $variantRows,
        array $attributes,
        float $basePrice,
        string $productSku
    ): void {
        $product->variants()->each(function (ProductVariant $variant) {
            $variant->values()->delete();
            $variant->delete();
        });

        $totalQuantity = 0;

        foreach ($variantRows as $sortOrder => $row) {
            $attributeValues = collect($row)
                ->map(function (string $valueLabel, string $attributeName) use ($attributes) {
                    $attribute = $attributes[$attributeName] ?? null;

                    if (! $attribute) {
                        return null;
                    }

                    return $attribute->values->firstWhere('value', $valueLabel);
                })
                ->filter()
                ->sortBy(fn(VariantAttributeValue $value) => $value->variant_attribute_id)
                ->values();

            if ($attributeValues->isEmpty()) {
                continue;
            }

            $combinationHash = $attributeValues
                ->map(fn(VariantAttributeValue $value) => $value->variant_attribute_id . ':' . $value->id)
                ->implode('|');

            $skuSuffix = $attributeValues
                ->map(fn(VariantAttributeValue $value) => Str::upper(Str::slug($value->value, '')))
                ->implode('-');

            $quantity = rand(15, 80);
            $priceOffset = ($sortOrder % 3) * 5;
            $sellingPrice = round($basePrice + $priceOffset, 2);
            $totalQuantity += $quantity;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'image' => self::IMAGE,
                'sku' => $productSku . '-' . $skuSuffix,
                'combination_hash' => $combinationHash,
                'quantity' => $quantity,
                'selling_price' => $sellingPrice,
                'purchase_price' => round($sellingPrice * 0.6, 2),
                'sort_order' => $sortOrder + 1,
                'is_active' => true,
            ]);

            foreach ($attributeValues as $attributeValue) {
                $variant->values()->create([
                    'variant_attribute_id' => $attributeValue->variant_attribute_id,
                    'variant_attribute_value_id' => $attributeValue->id,
                ]);
            }
        }

        $product->update([
            'quantity' => $totalQuantity,
            'stock_status' => $totalQuantity > 0 ? 'in_stock' : 'out_of_stock',
        ]);
    }
}
