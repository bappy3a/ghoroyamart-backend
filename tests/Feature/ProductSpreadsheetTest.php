<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use App\Models\VariantAttributeValue;
use App\Services\ProductSpreadsheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ProductSpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    protected function seedAttributes(): void
    {
        $color = VariantAttribute::create(['name' => 'Color', 'slug' => 'color']);
        $size = VariantAttribute::create(['name' => 'Size', 'slug' => 'size']);
        foreach (['Red', 'Blue'] as $i => $v) {
            VariantAttributeValue::create(['variant_attribute_id' => $color->id, 'value' => $v, 'slug' => strtolower($v), 'sort_order' => $i]);
        }
        foreach (['M', 'L'] as $i => $v) {
            VariantAttributeValue::create(['variant_attribute_id' => $size->id, 'value' => $v, 'slug' => strtolower($v), 'sort_order' => $i]);
        }
        Category::create(['name' => 'Fashion', 'slug' => 'fashion', 'is_active' => true]);
    }

    protected function fileWith(array $rows): string
    {
        $svc = new ProductSpreadsheet();
        $book = $svc->template();
        $sheet = $book->getSheetByName('Products');
        $sheet->removeRow(2, 10);
        foreach ($rows as $r => $cells) {
            foreach ($cells as $c => $v) {
                $sheet->setCellValue([$c + 1, $r + 2], $v);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        IOFactory::createWriter($book, 'Xlsx')->save($path);

        return $path;
    }

    public function test_template_imports_as_is_and_exports_back(): void
    {
        $this->seedAttributes();
        $svc = new ProductSpreadsheet();
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        IOFactory::createWriter($svc->template(), 'Xlsx')->save($path);

        $result = $svc->import($path);

        $this->assertSame([], $result['errors']);
        $this->assertSame(2, $result['created']);
        $this->assertSame(2, ProductVariant::count());
        $variant = ProductVariant::where('sku', 'SAMPLE-002-A')->first();
        $this->assertEquals(1200, $variant->regular_price);
        $this->assertEquals(1100, $variant->selling_price);
        $this->assertEquals(25, Product::where('sku', 'SAMPLE-001')->value('quantity'));
        $this->assertEquals(450, Product::where('sku', 'SAMPLE-001')->value('price'));

        // Re-importing the export updates instead of duplicating.
        $out = tempnam(sys_get_temp_dir(), 'exp').'.xlsx';
        IOFactory::createWriter($svc->export(), 'Xlsx')->save($out);
        $again = $svc->import($out);
        $this->assertSame([], $again['errors']);
        $this->assertSame(2, $again['updated']);
        $this->assertSame(2, Product::count());
        $this->assertSame(2, ProductVariant::count());
    }

    public function test_invalid_rows_store_nothing(): void
    {
        $this->seedAttributes();
        $svc = new ProductSpreadsheet();
        $path = $this->fileWith([
            ['GOOD-1', 'Good product', null, null, null, null, null, null, 100],
            ['BAD-1', '', null, null, null, 'bogus', null, null, 'abc', 'amount', 5000],
            ['BAD-2', 'Bad variant', null, null, null, null, null, null, 100, null, null, null, null, null, null, 'Green'],
        ]);

        $result = $svc->import($path);

        $this->assertArrayHasKey(3, $result['errors']);
        $this->assertArrayHasKey(4, $result['errors']);
        $this->assertArrayNotHasKey(2, $result['errors']);
        $this->assertSame(0, Product::count());
    }

    public function test_admin_pages_and_upload_flow(): void
    {
        $this->seed(PermissionSeeder::class);
        $admin = User::factory()->create(['user_type' => 'admin']);
        $this->seedAttributes();

        $this->actingAs($admin)->get(route('products.import-export'))->assertOk()->assertSee('Download Template');
        $this->actingAs($admin)->get(route('products.import-template'))->assertOk();
        $this->actingAs($admin)->get(route('products.export'))->assertOk();

        $bad = $this->fileWith([['X-1', '', null, null, null, null, null, null, 10]]);
        $this->actingAs($admin)->post(route('products.import-create'), ['file' => new UploadedFile($bad, 'p.xlsx', null, null, true)])
            ->assertSessionHas('import_errors');
        $this->assertSame(0, Product::count());

        $good = $this->fileWith([['X-1', 'Ok', null, null, null, null, null, null, 10]]);
        $this->actingAs($admin)->post(route('products.import-create'), ['file' => new UploadedFile($good, 'p.xlsx', null, null, true)])
            ->assertRedirect(route('products.index'));
        $this->assertSame(1, Product::count());
    }
}
