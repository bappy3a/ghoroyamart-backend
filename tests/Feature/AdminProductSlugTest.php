<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_generated_slug_gets_zero_padded_increment(): void
    {
        Product::create([
            'name' => 'Glow Serum',
            'slug' => 'glow-serum',
            'sku' => 'GLOW-SERUM',
        ]);

        Product::create([
            'name' => 'Glow Serum 001',
            'slug' => 'glow-serum-001',
            'sku' => 'GLOW-SERUM-001',
        ]);

        $payload = $this->controller()->payload($this->productRequest([
            'name' => 'Glow Serum',
            'slug' => '',
            'sku' => 'GLOW-SERUM-002',
        ]));

        $this->assertSame('glow-serum-002', $payload['slug']);
    }

    public function test_update_ignores_the_current_products_slug(): void
    {
        $product = Product::create([
            'name' => 'Glow Serum',
            'slug' => 'glow-serum',
            'sku' => 'GLOW-SERUM',
        ]);

        $payload = $this->controller()->payload($this->productRequest([
            'name' => 'Glow Serum',
            'slug' => '',
            'sku' => 'GLOW-SERUM',
        ]), $product);

        $this->assertSame('glow-serum', $payload['slug']);
    }

    public function test_update_increments_a_slug_owned_by_another_product(): void
    {
        Product::create([
            'name' => 'Glow Serum',
            'slug' => 'glow-serum',
            'sku' => 'GLOW-SERUM',
        ]);

        $product = Product::create([
            'name' => 'Night Cream',
            'slug' => 'night-cream',
            'sku' => 'NIGHT-CREAM',
        ]);

        $payload = $this->controller()->payload($this->productRequest([
            'name' => 'Glow Serum',
            'slug' => '',
            'sku' => 'NIGHT-CREAM',
        ]), $product);

        $this->assertSame('glow-serum-001', $payload['slug']);
    }

    private function productRequest(array $overrides = []): ProductRequest
    {
        return ProductRequest::create('/backend/products', 'POST', array_merge([
            'name' => 'Test Product',
            'status' => 'Published',
            'regular_price' => 100,
        ], $overrides));
    }

    private function controller(): object
    {
        return new class extends ProductController
        {
            public function payload(ProductRequest $request, ?Product $product = null): array
            {
                return $this->payloadFrom($request, $product);
            }
        };
    }
}
