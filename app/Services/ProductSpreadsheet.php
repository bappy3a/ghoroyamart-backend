<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\VariantAttribute;
use App\Models\VariantAttributeValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel import/export for products and their variants.
 *
 * One row = one product (no variants) or one variant of a product. Rows sharing the
 * same `product_sku` belong to the same product; the first row supplies product data.
 * The import is all-or-nothing: if any row is invalid nothing is stored.
 */
class ProductSpreadsheet
{
    public const MAX_ROWS = 2000;

    protected const PRODUCT_COLUMNS = [
        'product_sku' => 'Product SKU *',
        'name' => 'Product Name *',
        'category' => 'Category',
        'brand' => 'Brand',
        'unit' => 'Unit',
        'status' => 'Status',
        'visibility' => 'Visibility',
        'location' => 'Location',
        'regular_price' => 'Regular Price *',
        'discount_type' => 'Discount Type',
        'discount_value' => 'Discount Value',
        'purchase_price' => 'Purchase Price',
        'quantity' => 'Quantity',
        'short_description' => 'Short Description',
        'description' => 'Description',
    ];

    protected const VARIANT_COLUMNS = [
        'variant_sku' => 'Variant SKU',
        'variant_regular_price' => 'Variant Regular Price',
        'variant_discount_type' => 'Variant Discount Type',
        'variant_discount_value' => 'Variant Discount Value',
        'variant_purchase_price' => 'Variant Purchase Price',
        'variant_quantity' => 'Variant Quantity',
    ];

    public function template(): Spreadsheet
    {
        $attributes = $this->attributes();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Products');

        $this->writeHeader($sheet, $attributes);

        $color = $attributes->first();
        $size = $attributes->skip(1)->first();
        $colorValues = $color?->values->pluck('value')->values() ?? collect();
        $sizeValues = $size?->values->pluck('value')->values() ?? collect();
        $category = Category::where('is_active', true)->orderBy('name')->value('name');
        $brand = Brand::where('status', true)->orderBy('name')->value('name');
        $unit = Unit::orderBy('name')->value('name');

        $rows = [
            // Simple product, no variants.
            ['SAMPLE-001', 'Sample Simple Product', $category, $brand, $unit, 'published', 'public', 'store', 500, 'percentage', 10, 300, 25, 'Short text', 'Full description'],
        ];
        foreach ($rows as $i => $row) {
            $this->writeRow($sheet, $i + 2, $row, $attributes->count());
        }

        // Product with variants: first row carries product data, the rest repeat the SKU.
        $row = 3;
        $variantRows = [
            [$colorValues[0] ?? null, $sizeValues[0] ?? null, 'SAMPLE-002-A', 1200, 'amount', 100, 800, 10],
            [$colorValues[1] ?? $colorValues[0] ?? null, $sizeValues[1] ?? $sizeValues[0] ?? null, 'SAMPLE-002-B', 1300, 'percentage', 5, 900, 15],
        ];
        foreach ($variantRows as $n => $variant) {
            $base = $n === 0
                ? ['SAMPLE-002', 'Sample Variant Product', $category, $brand, $unit, 'published', 'public', 'store', 1200, null, null, null, null, 'Short text', 'Full description']
                : ['SAMPLE-002'];
            $this->writeRow($sheet, $row++, $base, $attributes->count(), [
                'attributes' => array_slice($variant, 0, 2),
                'variant' => array_slice($variant, 2),
            ]);
        }

        $this->addReferenceSheet($spreadsheet, $attributes);
        $this->addInstructionSheet($spreadsheet, $attributes);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function export(): Spreadsheet
    {
        $attributes = $this->attributes();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Products');
        $this->writeHeader($sheet, $attributes);

        $row = 2;
        Product::with(['category:id,name', 'brand:id,name', 'variants.values'])
            ->orderBy('id')
            ->chunk(200, function ($products) use ($sheet, $attributes, &$row) {
                foreach ($products as $product) {
                    $base = [
                        $product->sku, $product->name, $product->category?->name, $product->brand?->name,
                        $product->unit, $product->status, $product->visibility, $product->product_location,
                        $product->regular_price, $product->discount_type, $this->discountValue($product->discount_type, $product->discount_amount, $product->discount_percentage),
                        $product->purchase_price, $product->quantity, $product->short_description, $product->description,
                    ];

                    if ($product->variants->isEmpty()) {
                        $this->writeRow($sheet, $row++, $base, $attributes->count());

                        continue;
                    }

                    foreach ($product->variants->sortBy('sort_order')->values() as $n => $variant) {
                        $byAttribute = $variant->values->pluck('variant_attribute_value_id', 'variant_attribute_id');
                        $cells = $attributes->map(fn ($attribute) => $attribute->values->firstWhere('id', $byAttribute[$attribute->id] ?? null)?->value)->all();

                        $this->writeRow($sheet, $row++, $n === 0 ? $base : [$product->sku], $attributes->count(), [
                            'attributes' => $cells,
                            'variant' => [
                                $variant->sku, $variant->regular_price, $variant->discount_type,
                                $variant->discount_value, $variant->purchase_price, $variant->quantity,
                            ],
                        ]);
                    }
                }
            });

        return $spreadsheet;
    }

    /**
     * Validate every row and, only if all are valid, store the products and variants.
     *
     * @return array{errors: array<int, string[]>, created: int, updated: int, variants: int}
     */
    public function import(string $path, ?int $userId = null): array
    {
        $result = ['errors' => [], 'created' => 0, 'updated' => 0, 'variants' => 0];

        $workbook = IOFactory::load($path);
        $sheet = $workbook->getSheetByName('Products') ?? $workbook->getSheet(0);
        $attributes = $this->attributes();
        $parsed = $this->readRows($sheet, $attributes, $result['errors']);

        if ($result['errors']) {
            return $result;
        }

        if (empty($parsed)) {
            $result['errors'][0] = ['The file has no data rows.'];

            return $result;
        }

        $lookups = [
            'categories' => Category::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [Str::lower(trim($name)) => $id]),
            'brands' => Brand::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [Str::lower(trim($name)) => $id]),
            'units' => Unit::pluck('name')->push('pcs')->mapWithKeys(fn ($name) => [Str::lower(trim($name)) => $name]),
        ];

        $plans = $this->validateRows($parsed, $attributes, $lookups, $result['errors']);

        if ($result['errors']) {
            ksort($result['errors']);

            return $result;
        }

        DB::transaction(function () use ($plans, $userId, &$result) {
            foreach ($plans as $plan) {
                $product = Product::where('sku', $plan['product']['sku'])->first();

                if ($product) {
                    $product->update($plan['product'] + ['updated_by_id' => $userId]);
                    $result['updated']++;
                } else {
                    $product = Product::create($plan['product'] + [
                        'slug' => $this->uniqueSlug($plan['product']['name']),
                        'created_by_id' => $userId,
                    ]);
                    $result['created']++;
                }

                $sort = (int) $product->variants()->max('sort_order');

                foreach ($plan['variants'] as $variantPlan) {
                    $variant = $product->variants()->where('combination_hash', $variantPlan['hash'])->first();
                    $payload = $variantPlan['data'] + ['combination_hash' => $variantPlan['hash'], 'is_active' => true];

                    if ($variant) {
                        $variant->update($payload);
                    } else {
                        $variant = $product->variants()->create($payload + ['sort_order' => ++$sort]);
                    }

                    $variant->values()->delete();
                    foreach ($variantPlan['values'] as $value) {
                        $variant->values()->create([
                            'variant_attribute_id' => $value->variant_attribute_id,
                            'variant_attribute_value_id' => $value->id,
                        ]);
                    }
                    $result['variants']++;
                }

                if ($plan['variants']) {
                    $product->update(['quantity' => (int) $product->variants()->sum('quantity')]);
                }
            }
        });

        return $result;
    }

    // ---------------------------------------------------------------- reading

    protected function readRows(Worksheet $sheet, Collection $attributes, array &$errors): array
    {
        $headerRow = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1', null, true, false)[0];
        $expected = array_keys($this->columnMap($attributes));
        $labels = array_values($this->columnMap($attributes));

        $index = [];
        foreach ($headerRow as $col => $header) {
            $key = $this->headerKey((string) $header, $labels, $expected);
            if ($key !== null) {
                $index[$key] = $col;
            }
        }

        $missing = collect($expected)
            ->filter(fn ($key) => ! str_starts_with($key, 'attr:') && ! isset($index[$key]))
            ->map(fn ($key) => $this->columnMap($attributes)[$key]);

        if ($missing->isNotEmpty()) {
            $errors[1] = ['Missing column(s): '.$missing->implode(', ').'. Please use the downloaded template.'];

            return [];
        }

        $highest = $sheet->getHighestDataRow();
        if ($highest - 1 > self::MAX_ROWS) {
            $errors[1] = ['Too many rows. Import at most '.self::MAX_ROWS.' rows at a time.'];

            return [];
        }

        $rows = [];
        for ($r = 2; $r <= $highest; $r++) {
            $cells = $sheet->rangeToArray('A'.$r.':'.$sheet->getHighestColumn().$r, null, true, false)[0];
            $row = ['_line' => $r];
            foreach ($index as $key => $col) {
                $value = $cells[$col] ?? null;
                $row[$key] = is_string($value) ? trim($value) : $value;
                if ($row[$key] === '') {
                    $row[$key] = null;
                }
            }

            if (collect($row)->except('_line')->filter(fn ($v) => $v !== null)->isEmpty()) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    protected function headerKey(string $header, array $labels, array $keys): ?string
    {
        $header = Str::lower(trim($header));
        foreach ($labels as $i => $label) {
            if ($header === Str::lower($label) || $header === Str::lower(trim($label, ' *')) || $header === $keys[$i]) {
                return $keys[$i];
            }
        }

        return null;
    }

    // ------------------------------------------------------------- validation

    protected function validateRows(array $rows, Collection $attributes, array $lookups, array &$errors): array
    {
        $plans = [];
        $seenVariantSkus = [];
        $err = function (int $line, string $message) use (&$errors) {
            $errors[$line][] = $message;
        };

        foreach ($rows as $row) {
            $line = $row['_line'];
            $sku = $row['product_sku'] ?? null;

            if ($sku === null) {
                $err($line, 'Product SKU is required.');

                continue;
            }
            $sku = (string) $sku;
            if (preg_match('/\s/', $sku) || strlen($sku) > 191) {
                $err($line, 'Product SKU must not contain spaces and must be at most 191 characters.');

                continue;
            }

            if (! isset($plans[$sku])) {
                $product = $this->validateProduct($row, $lookups, fn ($m) => $err($line, $m));
                $plans[$sku] = ['product' => $product, 'variants' => [], 'line' => $line];
            } elseif ($this->hasProductData($row)) {
                $err($line, "Product data is repeated for SKU \"{$sku}\". Fill product columns only on the first row of a product.");
            }

            $values = $this->rowAttributeValues($row, $attributes, fn ($m) => $err($line, $m));
            $hasVariantData = $values !== [] || collect(array_keys(self::VARIANT_COLUMNS))->contains(fn ($k) => ($row[$k] ?? null) !== null);

            if (! $hasVariantData) {
                continue;
            }

            if ($values === []) {
                $err($line, 'Variant needs at least one attribute value (e.g. Color or Size).');

                continue;
            }

            $variant = $this->validateVariant($row, $sku, count($plans[$sku]['variants']), fn ($m) => $err($line, $m));
            $hash = collect($values)->sortBy('variant_attribute_id')
                ->map(fn ($v) => $v->variant_attribute_id.':'.$v->id)->implode('|');

            if (collect($plans[$sku]['variants'])->contains(fn ($v) => $v['hash'] === $hash)) {
                $err($line, 'Duplicate variant combination for this product.');
            }

            $variantSku = $variant['sku'] ?? null;
            if ($variantSku !== null) {
                if (isset($seenVariantSkus[Str::lower($variantSku)])) {
                    $err($line, "Variant SKU \"{$variantSku}\" is used more than once in the file.");
                }
                $seenVariantSkus[Str::lower($variantSku)] = true;

                $existing = ProductVariant::where('sku', $variantSku)->first();
                $owner = Product::where('sku', $sku)->value('id');
                $sameVariant = $existing && $owner && $existing->product_id === $owner && $existing->combination_hash === $hash;
                if ($existing && ! $sameVariant) {
                    $err($line, "Variant SKU \"{$variantSku}\" already exists.");
                }
            }

            $plans[$sku]['variants'][] = ['hash' => $hash, 'data' => $variant, 'values' => $values, 'line' => $line];
        }

        // Fill in auto-generated variant SKUs only after all explicit ones are known.
        foreach ($plans as $sku => &$plan) {
            foreach ($plan['variants'] as $n => &$variantPlan) {
                if (($variantPlan['data']['sku'] ?? null) === null) {
                    $variantPlan['data']['sku'] = ProductVariant::where('combination_hash', $variantPlan['hash'])
                        ->whereHas('product', fn ($q) => $q->where('sku', $sku))
                        ->value('sku')
                        ?? $this->autoVariantSku($sku, $seenVariantSkus);
                }
            }
            unset($variantPlan);
        }
        unset($plan);

        // Product SKU / slug-independent checks that need the DB.
        foreach ($plans as $sku => $plan) {
            if (! Product::where('sku', $sku)->exists()
                && ProductVariant::where('sku', $sku)->exists()) {
                $errors[$plan['line']][] = "SKU \"{$sku}\" is already used by a variant.";
            }
        }

        return array_values($plans);
    }

    protected function validateProduct(array $row, array $lookups, callable $err): array
    {
        $data = ['sku' => (string) $row['product_sku']];

        $name = $row['name'] ?? null;
        if ($name === null) {
            $err('Product Name is required.');
        } elseif (mb_strlen((string) $name) > 191) {
            $err('Product Name must be at most 191 characters.');
        }
        $data['name'] = (string) $name;

        $data['regular_price'] = $this->number($row['regular_price'] ?? null, 'Regular Price', true, $err);
        $data['purchase_price'] = $this->number($row['purchase_price'] ?? null, 'Purchase Price', false, $err);

        $quantity = $this->integer($row['quantity'] ?? null, 'Quantity', $err);
        $data['quantity'] = $quantity ?? 0;
        $data['stock_status'] = $data['quantity'] > 0 ? 'in_stock' : 'out_of_stock';

        $data['category_id'] = $this->lookup($row['category'] ?? null, $lookups['categories'], 'Category', $err);
        $data['brand_id'] = $this->lookup($row['brand'] ?? null, $lookups['brands'], 'Brand', $err);
        $data['unit'] = $this->lookup($row['unit'] ?? null, $lookups['units'], 'Unit', $err) ?? 'pcs';

        $data['status'] = $this->choice($row['status'] ?? null, ['published', 'draft', 'archived', 'scheduled'], 'Status', 'published', $err);
        $data['visibility'] = $this->choice($row['visibility'] ?? null, ['public', 'hidden'], 'Visibility', 'public', $err);
        $data['product_location'] = $this->choice($row['location'] ?? null, ['store', 'warehouse'], 'Location', 'store', $err);
        if ($data['product_location'] === 'warehouse') {
            $data['visibility'] = 'hidden';
        }

        $pricing = $this->pricing(
            $data['regular_price'] ?? 0,
            $row['discount_type'] ?? null,
            $row['discount_value'] ?? null,
            'Discount',
            $err
        );
        $data['discount_type'] = $pricing['type'];
        $data['discount_percentage'] = $pricing['percentage'];
        $data['discount_amount'] = $pricing['amount'];
        $data['price'] = $pricing['final'];
        $data['is_discounted'] = $pricing['amount'] > 0;

        $short = $row['short_description'] ?? null;
        if ($short !== null && mb_strlen((string) $short) > 500) {
            $err('Short Description must be at most 500 characters.');
        }
        $data['short_description'] = $short !== null ? (string) $short : null;
        $data['description'] = isset($row['description']) ? (string) $row['description'] : null;

        return $data;
    }

    protected function validateVariant(array $row, string $productSku, int $position, callable $err): array
    {
        $sku = $row['variant_sku'] ?? null;
        if ($sku !== null) {
            $sku = (string) $sku;
            if (preg_match('/\s/', $sku) || strlen($sku) > 191) {
                $err('Variant SKU must not contain spaces and must be at most 191 characters.');
            }
        }

        $regular = $this->number($row['variant_regular_price'] ?? null, 'Variant Regular Price', true, $err);
        $pricing = $this->pricing($regular ?? 0, $row['variant_discount_type'] ?? null, $row['variant_discount_value'] ?? null, 'Variant Discount', $err);
        $purchase = $this->number($row['variant_purchase_price'] ?? null, 'Variant Purchase Price', false, $err);
        $quantity = $this->integer($row['variant_quantity'] ?? null, 'Variant Quantity', $err);

        return [
            'sku' => $sku,
            'quantity' => $quantity ?? 0,
            'regular_price' => $regular ?? 0,
            'discount_type' => $pricing['type'],
            'discount_value' => $pricing['value'],
            'discount_amount' => $pricing['amount'],
            'discount_percentage' => $pricing['percentage'],
            'selling_price' => $pricing['final'],
            'purchase_price' => $purchase,
        ];
    }

    /** @return VariantAttributeValue[] */
    protected function rowAttributeValues(array $row, Collection $attributes, callable $err): array
    {
        $values = [];

        foreach ($attributes as $attribute) {
            $cell = $row['attr:'.$attribute->id] ?? null;
            if ($cell === null) {
                continue;
            }

            $match = $attribute->values->first(fn ($v) => Str::lower(trim($v->value)) === Str::lower(trim((string) $cell)));
            if (! $match) {
                $err("\"{$cell}\" is not a valid {$attribute->name} value. Allowed: ".$attribute->values->pluck('value')->implode(', ').'.');

                continue;
            }
            $values[] = $match;
        }

        return $values;
    }

    protected function hasProductData(array $row): bool
    {
        foreach (array_keys(self::PRODUCT_COLUMNS) as $key) {
            if ($key !== 'product_sku' && ($row[$key] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    // --------------------------------------------------------------- parsers

    protected function number(mixed $value, string $label, bool $required, callable $err): ?float
    {
        if ($value === null) {
            if ($required) {
                $err("{$label} is required.");
            }

            return null;
        }

        if (! is_numeric($value)) {
            $err("{$label} must be a number (got \"{$value}\").");

            return null;
        }

        if ((float) $value < 0) {
            $err("{$label} cannot be negative.");

            return null;
        }

        if ((float) $value > 99999999.99) {
            $err("{$label} is too large.");

            return null;
        }

        return round((float) $value, 2);
    }

    protected function integer(mixed $value, string $label, callable $err): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value) || floor((float) $value) != (float) $value || (float) $value < 0) {
            $err("{$label} must be a whole number of 0 or more (got \"{$value}\").");

            return null;
        }

        return (int) $value;
    }

    protected function choice(mixed $value, array $allowed, string $label, string $default, callable $err): string
    {
        if ($value === null) {
            return $default;
        }

        $normalized = Str::lower(trim((string) $value));
        if (! in_array($normalized, $allowed, true)) {
            $err("{$label} must be one of: ".implode(', ', $allowed).'.');

            return $default;
        }

        return $normalized;
    }

    protected function lookup(mixed $value, Collection $map, string $label, callable $err): int|string|null
    {
        if ($value === null) {
            return null;
        }

        $found = $map->get(Str::lower(trim((string) $value)));
        if ($found === null) {
            $err("{$label} \"{$value}\" does not exist. Create it first or use a name from the Reference sheet.");
        }

        return $found;
    }

    /** @return array{type: string, value: float, amount: float, percentage: float, final: float} */
    protected function pricing(float $regular, mixed $type, mixed $value, string $label, callable $err): array
    {
        $type = $type === null ? 'percentage' : Str::lower(trim((string) $type));
        if (! in_array($type, ['percentage', 'amount'], true)) {
            $err("{$label} Type must be \"percentage\" or \"amount\".");
            $type = 'percentage';
        }

        $number = $this->number($value, "{$label} Value", false, $err) ?? 0.0;

        if ($type === 'percentage' && $number > 100) {
            $err("{$label} percentage cannot exceed 100.");
            $number = 100;
        }
        if ($type === 'amount' && $number > $regular) {
            $err("{$label} amount cannot exceed the regular price.");
            $number = $regular;
        }

        $amount = $type === 'amount' ? $number : $regular * $number / 100;
        $percentage = $type === 'amount' ? ($regular > 0 ? $amount / $regular * 100 : 0) : $number;

        return [
            'type' => $type,
            'value' => $number,
            'amount' => round($amount, 2),
            'percentage' => round($percentage, 2),
            'final' => round($regular - $amount, 2),
        ];
    }

    // --------------------------------------------------------------- helpers

    protected function attributes(): Collection
    {
        return VariantAttribute::where('is_active', true)->with(['values' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])->orderBy('id')->get();
    }

    /** @return array<string, string> key => header label */
    protected function columnMap(Collection $attributes): array
    {
        $map = self::PRODUCT_COLUMNS;
        foreach ($attributes as $attribute) {
            $map['attr:'.$attribute->id] = $attribute->name;
        }

        return $map + self::VARIANT_COLUMNS;
    }

    protected function writeHeader(Worksheet $sheet, Collection $attributes): void
    {
        $col = 1;
        foreach ($this->columnMap($attributes) as $label) {
            $sheet->setCellValue([$col, 1], $label);
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $col++;
        }

        $last = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F0FE');
        $sheet->freezePane('C2');
    }

    /**
     * @param  array  $product  product cells in PRODUCT_COLUMNS order
     * @param  array{attributes?: array, variant?: array}  $variant
     */
    protected function writeRow(Worksheet $sheet, int $row, array $product, int $attributeCount, array $variant = []): void
    {
        $values = array_pad($product, count(self::PRODUCT_COLUMNS), null);
        $values = array_merge($values, array_pad($variant['attributes'] ?? [], $attributeCount, null));
        $values = array_merge($values, array_pad($variant['variant'] ?? [], count(self::VARIANT_COLUMNS), null));

        foreach ($values as $i => $value) {
            if ($value === null) {
                continue;
            }
            // Strings are written explicitly so SKUs like "00123" or "1E5" are never coerced.
            $sheet->setCellValueExplicit([$i + 1, $row], $value, is_numeric($value) && ! is_string($value)
                ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
                : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }

    protected function discountValue(?string $type, mixed $amount, mixed $percentage): mixed
    {
        return $type === 'amount' ? $amount : $percentage;
    }

    protected function addReferenceSheet(Spreadsheet $spreadsheet, Collection $attributes): void
    {
        $sheet = $spreadsheet->createSheet()->setTitle('Reference');

        $columns = [
            'Category' => Category::where('is_active', true)->orderBy('name')->pluck('name'),
            'Brand' => Brand::where('status', true)->orderBy('name')->pluck('name'),
            'Unit' => Unit::orderBy('name')->pluck('name'),
            'Status' => collect(['published', 'draft', 'archived', 'scheduled']),
            'Visibility' => collect(['public', 'hidden']),
            'Location' => collect(['store', 'warehouse']),
            'Discount Type' => collect(['percentage', 'amount']),
        ];
        foreach ($attributes as $attribute) {
            $columns[$attribute->name] = $attribute->values->pluck('value');
        }

        $col = 1;
        foreach ($columns as $title => $items) {
            $sheet->setCellValue([$col, 1], $title);
            foreach ($items->values() as $i => $item) {
                $sheet->setCellValueExplicit([$col, $i + 2], (string) $item, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $col++;
        }
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
    }

    protected function addInstructionSheet(Spreadsheet $spreadsheet, Collection $attributes): void
    {
        $sheet = $spreadsheet->createSheet()->setTitle('Instructions');
        $lines = [
            'How to fill the Products sheet',
            '',
            '1. One row = one simple product, or one variant of a product.',
            '2. Rows with the same Product SKU belong to the same product. Fill the product columns only on its FIRST row; repeat just the SKU on the other rows.',
            '3. For a variant row, pick a value in each attribute column ('.$attributes->pluck('name')->implode(', ').'). Values must exist in Variant Attributes (see Reference sheet).',
            '4. Variant Regular Price is the price before discount; the selling price is calculated from Variant Discount Type/Value.',
            '5. Discount Type: percentage (0-100) or amount (cannot exceed the regular price). Leave blank for no discount.',
            '6. Variant SKU is optional; it is generated when blank. SKUs must not contain spaces.',
            '7. Category, Brand and Unit must match an existing name (see Reference sheet).',
            '8. If a Product SKU already exists, that product is updated and variants are matched by their attribute combination.',
            '9. The file is checked before anything is saved. If any row has an error, NOTHING is imported and the errors are listed by row number.',
            '10. Columns marked * are required. Do not rename or remove header columns. Maximum '.self::MAX_ROWS.' rows.',
        ];
        foreach ($lines as $i => $line) {
            $sheet->setCellValue([1, $i + 1], $line);
        }
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(140);
    }

    protected function autoVariantSku(string $productSku, array &$taken): string
    {
        $n = 1;
        $candidate = substr($productSku, 0, 180).'-V'.$n;

        while (isset($taken[Str::lower($candidate)]) || ProductVariant::where('sku', $candidate)->exists()) {
            $candidate = substr($productSku, 0, 180).'-V'.++$n;
        }
        $taken[Str::lower($candidate)] = true;

        return $candidate;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::substr(Str::slug($name) ?: 'product', 0, 180);
        $slug = $base;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.str_pad((string) $counter++, 3, '0', STR_PAD_LEFT);
        }

        return $slug;
    }
}
