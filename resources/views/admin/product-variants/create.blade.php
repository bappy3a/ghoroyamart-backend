@extends('layouts.master')

@section('title', 'Generate Product Variants')

@section('content')
    <form action="{{ route('product-variants.store') }}" method="POST" id="variantGeneratorForm" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Product</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Product <span class="text-danger">*</span></label>
                            <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option
                                        value="{{ $product->id }}"
                                        data-sku="{{ $product->sku }}"
                                        data-selling-price="{{ $product->regular_price }}"
                                        data-purchase-price="{{ $product->purchase_price }}"
                                        {{ old('product_id', $selectedProductId) == $product->id ? 'selected' : '' }}
                                    >
                                        {{ $product->name }}{{ $product->sku ? ' - '.$product->sku : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($attributes->isEmpty())
                            <div class="alert alert-warning mb-0">
                                Create variant attributes first.
                                <a href="{{ route('variant-attributes.index') }}" class="alert-link">Go to attributes</a>.
                            </div>
                        @else
                            <a href="{{ route('variant-attributes.index') }}" class="btn btn-light btn-sm">
                                <i class="ri-list-settings-line align-middle me-1"></i>
                                Manage Attributes
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Attributes</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $oldAttributeValues = collect(old('attribute_values', []))->map(fn ($values) => array_map('intval', (array) $values));
                        @endphp

                        @forelse($attributes as $attribute)
                            <div class="mb-4">
                                <label class="form-label fw-semibold">{{ $attribute->name }}</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @forelse($attribute->values as $value)
                                        @php
                                            $checked = in_array((int) $value->id, $oldAttributeValues->get($attribute->id, []), true);
                                        @endphp
                                        <input
                                            type="checkbox"
                                            class="btn-check variant-value-check"
                                            id="value-{{ $value->id }}"
                                            name="attribute_values[{{ $attribute->id }}][]"
                                            value="{{ $value->id }}"
                                            data-attribute-id="{{ $attribute->id }}"
                                            data-attribute-name="{{ $attribute->name }}"
                                            data-value-name="{{ $value->value }}"
                                            data-value-slug="{{ $value->slug }}"
                                            autocomplete="off"
                                            {{ $checked ? 'checked' : '' }}
                                        >
                                        <label class="btn btn-outline-primary btn-sm" for="value-{{ $value->id }}">
                                            {{ $value->value }}
                                        </label>
                                    @empty
                                        <span class="text-muted">No values for this attribute.</span>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No variant attributes available.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Generated Combinations</h5>
                        <button type="button" class="btn btn-light btn-sm" id="refreshCombinations">
                            <i class="ri-refresh-line align-middle me-1"></i>
                            Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        @if($errors->has('variants'))
                            <div class="alert alert-danger">{{ $errors->first('variants') }}</div>
                        @endif

                        <div class="alert alert-info" id="variantHelp">
                            Select one or more values from each attribute. Combinations like Red + S and Blue + L will appear here automatically.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 180px;">Variant</th>
                                        <th style="min-width: 180px;">SKU</th>
                                        <th style="width: 130px;">Quantity</th>
                                        <th style="width: 150px;">Selling Price</th>
                                        <th style="width: 150px;">Purchase Price</th>
                                        <th style="min-width: 180px;">Image</th>
                                    </tr>
                                </thead>
                                <tbody id="variantRows">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Select attribute values to generate variants.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @error('variants.*.sku')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        @error('variants.*.quantity')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        @error('variants.*.selling_price')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror

                        <div class="text-end">
                            <a href="{{ route('product-variants.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-success">Save Variants</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    @include('admin.products._sku-space-sanitizer')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const productSelect = document.getElementById('product_id');
            const checks = Array.from(document.querySelectorAll('.variant-value-check'));
            const rows = document.getElementById('variantRows');
            const refreshButton = document.getElementById('refreshCombinations');
            const oldVariants = @json(old('variants', []));
            let rowState = {};

            Object.values(oldVariants).forEach((variant) => {
                const key = (variant.attribute_value_ids || []).map(Number).sort((a, b) => a - b).join('-');
                if (key) {
                    rowState[key] = variant;
                }
            });

            function selectedProduct() {
                return productSelect.options[productSelect.selectedIndex] || null;
            }

            function baseSku() {
                const option = selectedProduct();
                const sku = option?.dataset.sku || option?.textContent || 'VARIANT';

                return slugPart(sku) || 'VARIANT';
            }

            function defaultSellingPrice() {
                return selectedProduct()?.dataset.sellingPrice || '0';
            }

            function defaultPurchasePrice() {
                return selectedProduct()?.dataset.purchasePrice || '';
            }

            function slugPart(value) {
                return String(value || '')
                    .trim()
                    .toUpperCase()
                    .replace(/[^A-Z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function collectCurrentRows() {
                rows.querySelectorAll('tr[data-key]').forEach((row) => {
                    const key = row.dataset.key;
                    rowState[key] = {
                        sku: row.querySelector('[data-field="sku"]')?.value || '',
                        quantity: row.querySelector('[data-field="quantity"]')?.value || '',
                        selling_price: row.querySelector('[data-field="selling_price"]')?.value || '',
                        purchase_price: row.querySelector('[data-field="purchase_price"]')?.value || '',
                        attribute_value_ids: key.split('-'),
                    };
                });
            }

            function selectedGroups() {
                const groups = {};

                checks.filter((check) => check.checked).forEach((check) => {
                    const attributeId = check.dataset.attributeId;
                    groups[attributeId] = groups[attributeId] || [];
                    groups[attributeId].push({
                        attributeId,
                        attributeName: check.dataset.attributeName,
                        valueId: check.value,
                        valueName: check.dataset.valueName,
                        valueSlug: check.dataset.valueSlug,
                    });
                });

                return Object.values(groups);
            }

            function cartesian(groups) {
                if (!groups.length) {
                    return [];
                }

                return groups.reduce((combinations, group) => {
                    const next = [];

                    combinations.forEach((combination) => {
                        group.forEach((item) => {
                            next.push([...combination, item]);
                        });
                    });

                    return next;
                }, [[]]);
            }

            function render() {
                collectCurrentRows();

                const combinations = cartesian(selectedGroups());

                if (!combinations.length) {
                    rows.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Select attribute values to generate variants.</td></tr>';
                    return;
                }

                rows.innerHTML = combinations.map((combination) => {
                    const sorted = [...combination].sort((a, b) => Number(a.valueId) - Number(b.valueId));
                    const key = sorted.map((item) => item.valueId).join('-');
                    const saved = rowState[key] || {};
                    const sku = saved.sku || [baseSku(), ...combination.map((item) => slugPart(item.valueSlug || item.valueName))].filter(Boolean).join('-');
                    const quantity = saved.quantity ?? '0';
                    const sellingPrice = saved.selling_price ?? defaultSellingPrice();
                    const purchasePrice = saved.purchase_price ?? defaultPurchasePrice();
                    const label = combination.map((item) => `${item.attributeName}: ${item.valueName}`).join(' + ');
                    const hiddenInputs = sorted.map((item) => `<input type="hidden" name="variants[${key}][attribute_value_ids][]" value="${item.valueId}">`).join('');

                    return `
                        <tr data-key="${escapeHtml(key)}">
                            <td>
                                ${hiddenInputs}
                                <div class="fw-semibold">${escapeHtml(label)}</div>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="variants[${key}][sku]" value="${escapeHtml(sku)}" data-field="sku" required>
                            </td>
                            <td>
                                <input type="number" class="form-control" name="variants[${key}][quantity]" value="${escapeHtml(quantity)}" min="0" data-field="quantity" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control" name="variants[${key}][selling_price]" value="${escapeHtml(sellingPrice)}" min="0" data-field="selling_price" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control" name="variants[${key}][purchase_price]" value="${escapeHtml(purchasePrice)}" min="0" data-field="purchase_price">
                            </td>
                            <td>
                                <input type="file" class="form-control" name="variants[${key}][image]" accept="image/*">
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            checks.forEach((check) => check.addEventListener('change', render));
            productSelect?.addEventListener('change', render);
            refreshButton?.addEventListener('click', render);

            render();
        });
    </script>
@endsection
