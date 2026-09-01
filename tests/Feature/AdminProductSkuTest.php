<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AdminProductSkuTest extends TestCase
{
    use RefreshDatabase;

    public function test_blank_product_sku_generates_unique_sku_from_name(): void
    {
        Product::create([
            'name' => 'Glow Serum',
            'sku' => 'GLOW-SERUM',
        ]);

        $payload = $this->controller()->payload($this->productRequest([
            'name' => 'Glow Serum',
            'sku' => '',
        ]));

        $this->assertSame('GLOW-SERUM-1', $payload['sku']);
    }

    public function test_update_ignores_current_product_when_generating_blank_sku(): void
    {
        $product = Product::create([
            'name' => 'Glow Serum',
            'sku' => 'GLOW-SERUM',
        ]);

        $payload = $this->controller()->payload($this->productRequest([
            'name' => 'Glow Serum',
            'sku' => '',
        ]), $product);

        $this->assertSame('GLOW-SERUM', $payload['sku']);
    }

    public function test_product_sku_cannot_contain_spaces(): void
    {
        $request = $this->productRequest([
            'name' => 'Glow Serum',
            'sku' => 'GLOW SERUM',
        ]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame('The SKU must not contain spaces.', $validator->errors()->first('sku'));
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
        return new class extends ProductController {
            public function payload(ProductRequest $request, ?Product $product = null): array
            {
                return $this->payloadFrom($request, $product);
            }
        };
    }
}
