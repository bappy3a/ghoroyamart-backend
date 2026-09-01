<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const discountTypeSelect = document.getElementById('discount_type');
    const discountValueInput = document.getElementById('discount_value');
    const discountSymbol = document.getElementById('discount_symbol');
    const discountHelp = document.getElementById('discount_help');
    const maxDiscountWrapper = document.getElementById('max_discount_wrapper');
    const productSelectionWrapper = document.getElementById('product_selection_wrapper');
    const minOrderWrapper = document.getElementById('min_order_wrapper');
    const productSearch = document.getElementById('product_search');
    const productItems = Array.from(document.querySelectorAll('.product-item'));
    const productCheckboxes = Array.from(document.querySelectorAll('.product-checkbox'));
    const selectVisibleProductsButton = document.getElementById('select_visible_products');
    const clearProductsButton = document.getElementById('clear_products');
    const selectedProductCount = document.getElementById('selected_product_count');

    function updateSelectedProductCount() {
        if (!selectedProductCount) {
            return;
        }
        const selectedCount = productCheckboxes.filter((checkbox) => checkbox.checked).length;
        selectedProductCount.textContent = selectedCount > 0
            ? `${selectedCount} product(s) selected`
            : 'No product selected yet.';
    }

    // Handle coupon type change
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const type = this.value;
            
            if (type === 'product_wise') {
                productSelectionWrapper.style.display = 'block';
                minOrderWrapper.style.display = 'none';
                document.getElementById('minimum_order_amount').value = '';
            } else {
                productSelectionWrapper.style.display = 'none';
                minOrderWrapper.style.display = 'block';
                // Clear product selections
                productCheckboxes.forEach((checkbox) => {
                    checkbox.checked = false;
                });
                updateSelectedProductCount();
            }
        });

        // Trigger on page load
        typeSelect.dispatchEvent(new Event('change'));
    }

    if (productSearch) {
        productSearch.addEventListener('input', function() {
            const searchTerm = this.value.trim().toLowerCase();

            productItems.forEach((item) => {
                const productName = item.dataset.productName || '';
                item.style.display = productName.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    if (selectVisibleProductsButton) {
        selectVisibleProductsButton.addEventListener('click', function() {
            productItems.forEach((item) => {
                if (item.style.display !== 'none') {
                    const checkbox = item.querySelector('.product-checkbox');
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                }
            });
            updateSelectedProductCount();
        });
    }

    if (clearProductsButton) {
        clearProductsButton.addEventListener('click', function() {
            productCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
            updateSelectedProductCount();
        });
    }

    productCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelectedProductCount);
    });
    updateSelectedProductCount();

    // Handle discount type change
    if (discountTypeSelect) {
        discountTypeSelect.addEventListener('change', function() {
            const discountType = this.value;
            
            if (discountType === 'percentage') {
                discountSymbol.textContent = '%';
                discountValueInput.setAttribute('max', '100');
                discountHelp.textContent = 'Enter percentage (0-100).';
                maxDiscountWrapper.style.display = 'block';
            } else {
                discountSymbol.textContent = '$';
                discountValueInput.removeAttribute('max');
                discountHelp.textContent = 'Enter fixed discount amount.';
                maxDiscountWrapper.style.display = 'none';
                const maxDiscountInput = document.getElementById('maximum_discount_amount');
                if (maxDiscountInput) {
                    maxDiscountInput.value = '';
                }
            }
        });

        // Trigger on page load
        discountTypeSelect.dispatchEvent(new Event('change'));
    }

    // Auto-uppercase coupon code
    const codeInput = document.getElementById('code');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // Form validation
    const form = document.getElementById('couponForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const type = typeSelect.value;
            const checkedProducts = productCheckboxes.filter((checkbox) => checkbox.checked);
            
            if (type === 'product_wise') {
                if (checkedProducts.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one product for product-wise coupon.');
                    if (productSearch) {
                        productSearch.focus();
                    }
                    return false;
                }
            }

            // Validate discount value for percentage
            if (discountTypeSelect.value === 'percentage') {
                const value = parseFloat(discountValueInput.value);
                if (value > 100) {
                    e.preventDefault();
                    alert('Percentage discount cannot exceed 100%.');
                    discountValueInput.focus();
                    return false;
                }
            }
        });
    }
});
</script>
