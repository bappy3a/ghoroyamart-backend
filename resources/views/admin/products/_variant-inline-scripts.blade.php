@php
    $oldVariantsForScript = old('variants');
    $inlineVariantsForScript = $oldVariantsForScript !== null ? collect($oldVariantsForScript)->values() : ($inlineVariants ?? collect());
    $inlineVariantImageUrls = $inlineVariantsForScript
        ->map(fn ($variant) => data_get($variant, 'image') ?: data_get($variant, 'existing_image'))
        ->filter()
        ->unique()
        ->mapWithKeys(fn ($path) => [$path => api_asset($path)]);
@endphp

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const picker = document.getElementById('combinationPicker');
        const rows = document.getElementById('inlineVariantRows');
        const selectedCount = document.getElementById('selectedCombinationCount');
        const attributeSections = Array.from(document.querySelectorAll('.product-variant-attribute'));
        const checks = Array.from(document.querySelectorAll('.product-variant-value'));
        const skuInput = document.getElementById('sku-input');
        const nameInput = document.getElementById('product-title-input');
        const sellingPriceInput = document.getElementById('product-price-input');
        const purchasePriceInput = document.getElementById('purchase-price-input');
        const useAllButton = document.getElementById('useAllCombinations');
        const clearAllButton = document.getElementById('clearAllCombinations');
        const initialVariants = @json($inlineVariantsForScript);
        const imageUrls = @json($inlineVariantImageUrls);
        const imageUrlTemplate = @json(api_asset('__API_ASSET_PATH__'));
        let rowState = {};

        if (!picker || !rows || !checks.length) {
            return;
        }

        Object.values(initialVariants).forEach((variant) => {
            const key = normalizeKey(variant.attribute_value_ids || []);

            if (key) {
                rowState[key] = {
                    ...variant,
                    attribute_value_ids: key.split('-'),
                    enabled: true,
                };
            }
        });

        function normalizeKey(valueIds) {
            return valueIds.map(Number).sort((a, b) => a - b).join('-');
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

        function resolveImageUrl(path) {
            const value = String(path || '');

            if (!value || /^(?:https?:)?\/\//i.test(value) || value.startsWith('data:')) {
                return value;
            }

            return imageUrls[value]
                || imageUrlTemplate.replace('__API_ASSET_PATH__', value.replace(/^\/+/, ''));
        }

        function baseSku() {
            return slugPart(skuInput?.value || nameInput?.value || 'VARIANT') || 'VARIANT';
        }

        function defaultSellingPrice() {
            return sellingPriceInput?.value || '0';
        }

        function defaultPurchasePrice() {
            return purchasePriceInput?.value || '';
        }

        function collectCurrentRows() {
            rows.querySelectorAll('tr[data-key]').forEach((row) => {
                const key = row.dataset.key;
                rowState[key] = {
                    id: row.querySelector('[data-field="id"]')?.value || '',
                    sku: row.querySelector('[data-field="sku"]')?.value || '',
                    quantity: row.querySelector('[data-field="quantity"]')?.value || '',
                    selling_price: row.querySelector('[data-field="selling_price"]')?.value || '',
                    purchase_price: row.querySelector('[data-field="purchase_price"]')?.value || '',
                    image: row.querySelector('[data-field="image"]')?.dataset.existingImage || '',
                    attribute_value_ids: key.split('-'),
                    enabled: true,
                };
            });
        }

        function selectedGroups() {
            return attributeSections
                .map((section) => {
                    const values = Array.from(section.querySelectorAll('.product-variant-value:checked')).map((check) => ({
                        attributeId: check.dataset.attributeId,
                        attributeName: check.dataset.attributeName,
                        valueId: check.value,
                        valueName: check.dataset.valueName,
                        valueSlug: check.dataset.valueSlug,
                        colorCode: check.dataset.colorCode,
                    }));

                    return values.length ? values : null;
                })
                .filter(Boolean);
        }

        function setSectionChecked(section, checked) {
            section.querySelectorAll('.product-variant-value').forEach((check) => {
                check.checked = checked;
            });

            render();
        }

        attributeSections.forEach((section) => {
            section.querySelector('.variant-select-all')?.addEventListener('click', () => {
                setSectionChecked(section, true);
            });

            section.querySelector('.variant-clear-all')?.addEventListener('click', () => {
                setSectionChecked(section, false);
            });
        });

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

        function combinationKey(combination) {
            return normalizeKey(combination.map((item) => item.valueId));
        }

        function combinationLabel(combination) {
            return combination.map((item) => {
                const swatch = item.colorCode ? `<span class="d-inline-block rounded-circle align-middle me-1 border" style="width: 12px; height: 12px; background: ${escapeHtml(item.colorCode)};"></span>` : '';

                return `${escapeHtml(item.attributeName)}: ${swatch}${escapeHtml(item.valueName)}`;
            }).join(' + ');
        }

        function setCombinationEnabled(key, enabled) {
            const existing = rowState[key] || {};

            rowState[key] = {
                ...existing,
                attribute_value_ids: key.split('-'),
                enabled,
            };
        }

        function renderPicker(groups, combinations) {
            if (!combinations.length) {
                picker.innerHTML = '<div class="text-center text-muted py-3">Select variant values to choose combinations.</div>';
                useAllButton?.classList.add('d-none');
                clearAllButton?.classList.add('d-none');
                return;
            }

            if (groups.length === 2) {
                useAllButton?.classList.remove('d-none');
                clearAllButton?.classList.remove('d-none');
                renderMatrixPicker(groups);
                return;
            }

            if (groups.length >= 3) {
                useAllButton?.classList.add('d-none');
                clearAllButton?.classList.remove('d-none');
                renderCombinationBuilder(groups);
                return;
            }

            useAllButton?.classList.remove('d-none');
            clearAllButton?.classList.remove('d-none');
            renderCheckboxPicker(combinations);
        }

        function renderCheckboxPicker(combinations) {
            picker.innerHTML = `
                <div class="row g-2">
                    ${combinations.map((combination) => {
                        const key = combinationKey(combination);
                        const checked = rowState[key]?.enabled ? 'checked' : '';

                        return `
                            <div class="col-md-6">
                                <label class="border rounded bg-white d-flex gap-2 align-items-center p-2 mb-0">
                                    <input type="checkbox" class="form-check-input combination-check mt-0" value="${escapeHtml(key)}" ${checked}>
                                    <span>${combinationLabel(combination)}</span>
                                </label>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function renderCombinationBuilder(groups) {
            picker.innerHTML = `
                <div class="row g-2 align-items-end">
                    ${groups.map((group, index) => `
                        <div class="col-md">
                            <label class="form-label small mb-1">${escapeHtml(group[0]?.attributeName || 'Option')}</label>
                            <select class="form-select form-select-sm combination-builder-select" data-group-index="${index}">
                                ${group.map((item) => `<option value="${escapeHtml(item.valueId)}">${escapeHtml(plainValueLabel(item))}</option>`).join('')}
                            </select>
                        </div>
                    `).join('')}
                    <div class="col-md-auto">
                        <button type="button" class="btn btn-sm btn-primary w-100" id="addCombinationButton">
                            <i class="ri-add-line align-middle me-1"></i>
                            Add
                        </button>
                    </div>
                </div>
                <div class="form-text mt-2">Choose one value from each attribute, then add only the real product variant.</div>
            `;
        }

        function plainValueLabel(item) {
            return item.valueName;
        }

        function renderMatrixPicker(groups) {
            const [rowGroup, columnGroup] = groups;

            picker.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle bg-white mb-0">
                        <thead>
                            <tr>
                                <th style="width: 160px;">${escapeHtml(rowGroup[0]?.attributeName || 'Variant')}</th>
                                ${columnGroup.map((column) => `<th class="text-center">${valueLabel(column)}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>
                            ${rowGroup.map((rowItem) => `
                                <tr>
                                    <th>${valueLabel(rowItem)}</th>
                                    ${columnGroup.map((columnItem) => {
                                        const key = normalizeKey([rowItem.valueId, columnItem.valueId]);
                                        const checked = rowState[key]?.enabled ? 'checked' : '';

                                        return `
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input combination-check" value="${escapeHtml(key)}" ${checked}>
                                            </td>
                                        `;
                                    }).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function valueLabel(item) {
            const swatch = item.colorCode ? `<span class="d-inline-block rounded-circle align-middle me-1 border" style="width: 12px; height: 12px; background: ${escapeHtml(item.colorCode)};"></span>` : '';

            return `${swatch}${escapeHtml(item.valueName)}`;
        }

        function addBuiltCombination() {
            const groups = selectedGroups();
            const selectedIds = Array.from(picker.querySelectorAll('.combination-builder-select')).map((select) => select.value);

            if (selectedIds.length !== groups.length) {
                return;
            }

            const key = normalizeKey(selectedIds);
            setCombinationEnabled(key, true);
            render();
        }

        function renderRows(combinations) {
            const enabledCombinations = combinations.filter((combination) => rowState[combinationKey(combination)]?.enabled);

            selectedCount.textContent = `${enabledCombinations.length} selected`;

            if (!enabledCombinations.length) {
                rows.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Choose combinations above to add variant details.</td></tr>';
                return;
            }

            rows.innerHTML = enabledCombinations.map((combination) => {
                const key = combinationKey(combination);
                const saved = rowState[key] || {};
                const sku = saved.sku || [baseSku(), ...combination.map((item) => slugPart(item.valueSlug || item.valueName))].filter(Boolean).join('-');
                const quantity = saved.quantity ?? '0';
                const sellingPrice = saved.selling_price ?? defaultSellingPrice();
                const purchasePrice = saved.purchase_price ?? defaultPurchasePrice();
                const image = saved.image || '';
                const imagePreview = image
                    ? `<div class="mt-2"><img src="${escapeHtml(resolveImageUrl(image))}" alt="" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;"></div>`
                    : '';
                const hiddenValues = key.split('-').map((id) => `<input type="hidden" name="variants[${key}][attribute_value_ids][]" value="${id}">`).join('');

                return `
                    <tr data-key="${escapeHtml(key)}">
                        <td>
                            <input type="hidden" name="variants[${key}][id]" value="${escapeHtml(saved.id || '')}" data-field="id">
                            ${hiddenValues}
                            <div class="fw-semibold">${combinationLabel(combination)}</div>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="variants[${key}][sku]" value="${escapeHtml(sku)}" data-field="sku" required>
                        </td>
                        <td>
                            <input type="hidden" name="variants[${key}][existing_image]" value="${escapeHtml(image)}" data-field="image" data-existing-image="${escapeHtml(image)}">
                            <input type="file" class="form-control" name="variants[${key}][image]" accept="image/*">
                            ${imagePreview}
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
                    </tr>
                `;
            }).join('');
        }

        function render() {
            collectCurrentRows();

            const groups = selectedGroups();
            const combinations = cartesian(groups);

            renderPicker(groups, combinations);
            renderRows(combinations);
        }

        function setAllCombinations(enabled) {
            cartesian(selectedGroups()).forEach((combination) => {
                setCombinationEnabled(combinationKey(combination), enabled);
            });

            render();
        }

        picker.addEventListener('change', (event) => {
            if (!event.target.classList.contains('combination-check')) {
                return;
            }

            collectCurrentRows();
            setCombinationEnabled(event.target.value, event.target.checked);
            render();
        });

        picker.addEventListener('click', (event) => {
            if (event.target.closest('#addCombinationButton')) {
                collectCurrentRows();
                addBuiltCombination();
            }
        });

        checks.forEach((check) => check.addEventListener('change', render));
        useAllButton?.addEventListener('click', () => setAllCombinations(true));
        clearAllButton?.addEventListener('click', () => setAllCombinations(false));
        skuInput?.addEventListener('input', render);
        nameInput?.addEventListener('input', render);
        sellingPriceInput?.addEventListener('input', render);
        purchasePriceInput?.addEventListener('input', render);

        render();
    });
</script>
