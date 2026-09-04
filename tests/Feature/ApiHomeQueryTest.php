<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\HomeController;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiHomeQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_payload_is_reused_instead_of_rebuilt_on_every_request(): void
    {
        Cache::flush();

        $phase = 'first';
        $queryCounts = [
            'first' => 0,
            'cached' => 0,
            'after-clear' => 0,
        ];

        DB::listen(function (QueryExecuted $query) use (&$phase, &$queryCounts): void {
            $queryCounts[$phase]++;
        });

        $firstResponse = $this->getJson(route('api.home.index'))
            ->assertOk()
            ->assertJsonPath('data.popular_categories.title', 'Popular Categories');

        $phase = 'cached';

        $this->getJson(route('api.home.index'))
            ->assertOk()
            ->assertExactJson($firstResponse->json());

        HomeController::clearCache();
        $phase = 'after-clear';

        $this->getJson(route('api.home.index'))->assertOk();

        $this->assertGreaterThan(0, $queryCounts['first']);
        $this->assertSame(0, $queryCounts['cached']);
        $this->assertGreaterThan(0, $queryCounts['after-clear']);
    }

    public function test_trending_products_only_include_featured_products(): void
    {
        Cache::flush();

        $featured = Product::query()->create([
            'name' => 'Featured product',
            'slug' => 'featured-product',
            'is_featured' => true,
            'num_of_views' => 10,
        ]);

        Product::query()->create([
            'name' => 'Popular non-featured product',
            'slug' => 'popular-non-featured-product',
            'is_featured' => false,
            'num_of_views' => 1000,
        ]);

        $response = $this->getJson(route('api.home.index'))->assertOk();
        $trendingProducts = collect($response->json('data.rails'))
            ->firstWhere('key', 'trending')['products'];

        $this->assertSame([$featured->id], collect($trendingProducts)->pluck('id')->all());
        $this->assertTrue(collect($trendingProducts)->every('is_featured'));
    }

    public function test_configured_price_deals_return_at_most_eight_eligible_products(): void
    {
        Cache::flush();

        Setting::query()->create([
            'key' => 'home_deal_section_items',
            'value' => json_encode([
                [
                    'title' => 'Everything from 1 to 99 Tk',
                    'min_price' => 1,
                    'max_price' => 99,
                    'banner_image' => 'uploads/settings/home/deal.webp',
                ],
            ]),
            'group' => 'Home Page',
            'type' => 'textarea',
            'label' => 'Home Deal Section Items',
        ]);

        foreach (range(1, 10) as $number) {
            Product::query()->create([
                'name' => "Eligible deal product {$number}",
                'slug' => "eligible-deal-product-{$number}",
                'price' => 99,
                'num_of_sale' => $number,
            ]);
        }

        Product::query()->create([
            'name' => 'Below minimum price',
            'slug' => 'below-minimum-price-for-deal',
            'price' => 0.50,
            'num_of_sale' => 101,
        ]);

        Product::query()->create([
            'name' => 'Too expensive',
            'slug' => 'too-expensive-for-deal',
            'price' => 100,
            'num_of_sale' => 100,
        ]);

        $response = $this->getJson(route('api.home.index'))
            ->assertOk()
            ->assertJsonPath('data.deals.0.title', 'Everything from 1 to 99 Tk')
            ->assertJsonPath('data.deals.0.min_price', 1)
            ->assertJsonPath('data.deals.0.max_price', 99)
            ->assertJsonCount(8, 'data.deals.0.products');

        $payloadKeys = array_keys($response->json('data'));
        $this->assertSame(
            array_search('flash_sale', $payloadKeys, true) + 1,
            array_search('deals', $payloadKeys, true)
        );

        $this->assertTrue(
            collect($response->json('data.deals.0.products'))->every(
                fn (array $product) => $product['price'] >= 1 && $product['price'] <= 99
            )
        );
    }
}
