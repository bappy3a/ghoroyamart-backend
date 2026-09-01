@php
    $oldVariants = old('variants');
    $inlineVariantsForJs = $oldVariants !== null ? collect($oldVariants)->values() : $inlineVariants;
    $oldSelectedValueIds = $inlineVariantsForJs
        ->pluck('attribute_value_ids')
        ->flatten()
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
        ->all();
@endphp

<div class="card" id="product-variants">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0">Product Variants</h5>
            <p class="text-muted mb-0 mt-1">Add SKU, image, quantity, selling price, and purchase price for each variant.</p>
        </div>
        <a href="{{ route('variant-attributes.index') }}" class="btn btn-light btn-sm">
            <i class="ri-list-settings-line align-middle me-1"></i>
            Attributes
        </a>
    </div>
    <div class="card-body">
        @if($variantAttributes->isEmpty())
            <div class="alert alert-warning mb-0">
                Create variant attributes first, then come back to this product form.
                <a href="{{ route('variant-attributes.index') }}" class="alert-link">Create attributes</a>
            </div>
        @else
            @error('variants')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            <div class="row">
                <div class="col-xl-3">
                    @foreach($variantAttributes as $attribute)
                        <div class="mb-4 product-variant-attribute" data-attribute-id="{{ $attribute->id }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">{{ $attribute->name }}</label>
                                @if($attribute->values->isNotEmpty())
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light variant-select-all">Select All</button>
                                        <button type="button" class="btn btn-light variant-clear-all">Clear</button>
                                    </div>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($attribute->values as $value)
                                    <input
                                        type="checkbox"
                                        class="btn-check product-variant-value"
                                        id="product-variant-value-{{ $value->id }}"
                                        value="{{ $value->id }}"
                                        data-attribute-id="{{ $attribute->id }}"
                                        data-attribute-name="{{ $attribute->name }}"
                                        data-value-name="{{ $value->value }}"
                                        data-value-slug="{{ $value->slug }}"
                                        data-color-code="{{ $value->color_code }}"
                                        autocomplete="off"
                                        {{ in_array((int) $value->id, $oldSelectedValueIds, true) ? 'checked' : '' }}
                                    >
                                    <label class="btn btn-outline-primary btn-sm" for="product-variant-value-{{ $value->id }}">
                                        @if($value->color_code)
                                            <span class="d-inline-block rounded-circle align-middle me-1 border" style="width: 12px; height: 12px; background: {{ $value->color_code }};"></span>
                                        @endif
                                        {{ $value->value }}
                                    </label>
                                @empty
                                    <span class="text-muted">No values yet.</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-xl-9">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Choose Combinations</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-light" id="useAllCombinations">Select All</button>
                            <button type="button" class="btn btn-sm btn-light" id="clearAllCombinations">Clear</button>
                        </div>
                    </div>

                    <div id="combinationPicker" class="border rounded p-3 mb-3 bg-light-subtle">
                        <div class="text-center text-muted py-3">Select variant values to choose combinations.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Variant Details</h6>
                        <span class="badge bg-primary-subtle text-primary" id="selectedCombinationCount">0 selected</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 170px;">Variant</th>
                                    <th style="min-width: 170px;">SKU</th>
                                    <th style="min-width: 190px;">Image</th>
                                    <th style="width: 120px;">Quantity</th>
                                    <th style="width: 145px;">Selling Price</th>
                                    <th style="width: 145px;">Purchase Price</th>
                                </tr>
                            </thead>
                            <tbody id="inlineVariantRows">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Choose combinations above to add variant details.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-text">
                        Pick values on the left, choose valid combinations above, then fill stock and prices below.
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
