<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VariantAttribute;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VariantAttributeController extends Controller
{
    public function index(): View
    {
        $attributes = VariantAttribute::withCount('values')
            ->with('values')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.variant-attributes.index', compact('attributes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:variant_attributes,name'],
            'values' => ['nullable', 'string'],
            'color_values' => ['nullable', 'array'],
            'color_values.*.value' => ['nullable', 'string', 'max:191'],
            'color_values.*.color_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $attribute = VariantAttribute::create([
                'name' => $data['name'],
                'slug' => $this->uniqueAttributeSlug($data['name']),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncSubmittedValues($attribute, $data['values'] ?? '', $data['color_values'] ?? []);
        });

        flash_message('Variant attribute created successfully!');

        return redirect()->route('variant-attributes.index');
    }

    public function update(Request $request, VariantAttribute $variantAttribute): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('variant_attributes', 'name')->ignore($variantAttribute->id),
            ],
            'values' => ['nullable', 'string'],
            'color_values' => ['nullable', 'array'],
            'color_values.*.value' => ['nullable', 'string', 'max:191'],
            'color_values.*.color_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $request, $variantAttribute) {
            $variantAttribute->update([
                'name' => $data['name'],
                'slug' => $this->uniqueAttributeSlug($data['name'], $variantAttribute->id),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncSubmittedValues($variantAttribute, $data['values'] ?? '', $data['color_values'] ?? []);
        });

        flash_message('Variant attribute updated successfully!');

        return redirect()->route('variant-attributes.index');
    }

    public function destroy(VariantAttribute $variantAttribute): RedirectResponse
    {
        if ($variantAttribute->variantValues()->exists()) {
            flash_message('This attribute is used by product variants and cannot be deleted.', 'error');

            return redirect()->route('variant-attributes.index');
        }

        $variantAttribute->delete();

        flash_message('Variant attribute deleted successfully!');

        return redirect()->route('variant-attributes.index');
    }

    protected function syncSubmittedValues(VariantAttribute $attribute, string $rawValues, array $colorValues = []): void
    {
        $values = collect(preg_split('/[\r\n,]+/', $rawValues))
            ->map(fn (?string $value) => trim((string) $value))
            ->filter()
            ->map(fn (string $value) => [
                'value' => $value,
                'color_code' => null,
            ]);

        $colors = collect($colorValues)
            ->map(fn (array $colorValue) => [
                'value' => trim((string) ($colorValue['value'] ?? '')),
                'color_code' => $this->normalizeColorCode($colorValue['color_code'] ?? null),
            ])
            ->filter(fn (array $colorValue) => $colorValue['value'] !== '');

        $values = $colors
            ->merge($values)
            ->unique(fn (array $value) => Str::lower($value['value']))
            ->values();

        foreach ($values as $index => $valueData) {
            $attributeValue = $attribute->values()->firstOrCreate(
                ['value' => $valueData['value']],
                [
                    'slug' => Str::slug($valueData['value']),
                    'sort_order' => $index,
                ]
            );

            $attributeValue->update([
                'color_code' => $valueData['color_code'],
                'sort_order' => $index,
            ]);
        }
    }

    protected function normalizeColorCode(?string $colorCode): ?string
    {
        $colorCode = trim((string) $colorCode);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $colorCode) ? strtoupper($colorCode) : null;
    }

    protected function uniqueAttributeSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'attribute';
        $slug = $baseSlug;
        $counter = 1;

        while (VariantAttribute::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
