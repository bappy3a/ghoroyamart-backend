<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\HomeController;
use App\Models\Product;
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
}
