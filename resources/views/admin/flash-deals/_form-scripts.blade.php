<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedProductIds = new Set();
    let searchTimeout;

    // Initialize selected products from existing data
    document.querySelectorAll('input[name="product_ids[]"]').forEach(input => {
        selectedProductIds.add(parseInt(input.value));
    });

    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (titleInput && slugInput) {
        titleInput.addEventListener('input', function() {
            if (!slugInput.value || slugInput.dataset.manual !== 'true') {
                const slug = this.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });

        // Mark slug as manually edited
        slugInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }

    // Validate end date is after start date
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
            endDateInput.min = this.value;
        });

        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && this.value < startDateInput.value) {
                alert('End date must be after or equal to start date.');
                this.value = startDateInput.value;
            }
        });
    }

    // Product search functionality
    const productSearchInput = document.getElementById('product-search');
    const productResultsDiv = document.getElementById('product-results');
    const searchBtn = document.getElementById('search-btn');

    function searchProducts(query = '') {
        if (query.length < 2 && query.length > 0) {
            return;
        }

        const excludeIds = Array.from(selectedProductIds);
        
        fetch('{{ route("flash-deals.products.search") }}?search=' + encodeURIComponent(query) + '&exclude_ids=' + JSON.stringify(excludeIds), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProducts(data.products);
            }
        })
        .catch(error => {
            console.error('Error searching products:', error);
            productResultsDiv.innerHTML = '<div class="text-center text-danger py-4">Error loading products. Please try again.</div>';
        });
    }

    function displayProducts(products) {
        if (products.length === 0) {
            productResultsDiv.innerHTML = '<div class="text-center text-muted py-4">No products found.</div>';
            return;
        }

        let html = '<div class="list-group list-group-flush">';
        products.forEach(product => {
            const isSelected = selectedProductIds.has(product.id);
            html += `
                <div class="list-group-item list-group-item-action ${isSelected ? 'bg-light' : ''}" 
                     style="cursor: pointer; ${isSelected ? 'opacity: 0.6;' : ''}"
                     onclick="${isSelected ? '' : `addProduct(${product.id}, '${product.name.replace(/'/g, "\\'")}', ${product.price})`}">
                    <div class="d-flex align-items-center gap-3">
                        <img src="${product.image}" alt="${product.name}" 
                             class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${product.name}</h6>
                            <small class="text-muted">SKU: ${product.sku || 'N/A'} | ৳${product.price}</small>
                        </div>
                        ${isSelected ? '<span class="badge bg-success">Selected</span>' : '<i class="ri-add-circle-line text-primary fs-5"></i>'}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        productResultsDiv.innerHTML = html;
    }

    // Search on input with debounce
    if (productSearchInput) {
        productSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length === 0) {
                productResultsDiv.innerHTML = '<div class="text-center text-muted py-4"><i class="ri-search-line fs-3 d-block mb-2"></i><p class="mb-0">Start typing to search for products</p></div>';
                return;
            }

            searchTimeout = setTimeout(() => {
                searchProducts(query);
            }, 300);
        });

        // Search on button click
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                searchProducts(productSearchInput.value.trim());
            });
        }

        // Search on Enter key
        productSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchProducts(this.value.trim());
            }
        });
    }

    // Add product function (global for onclick)
    window.addProduct = function(productId, productName, productPrice) {
        if (selectedProductIds.has(productId)) {
            return;
        }

        selectedProductIds.add(productId);
        
        const selectedList = document.getElementById('selected-products-list');
        const emptyMessage = selectedList.querySelector('.text-muted');
        if (emptyMessage) {
            emptyMessage.remove();
        }

        const badge = document.createElement('div');
        badge.className = 'badge bg-primary d-flex align-items-center gap-2 p-2';
        badge.setAttribute('data-product-id', productId);
        badge.innerHTML = `
            <span>${productName}</span>
            <button type="button" class="btn-close btn-close-white" style="font-size: 0.7rem;" onclick="removeProduct(${productId})"></button>
            <input type="hidden" name="product_ids[]" value="${productId}">
        `;
        selectedList.appendChild(badge);

        // Update search results to show product as selected
        searchProducts(document.getElementById('product-search')?.value || '');
        
        // Hide error message
        document.getElementById('product-error').style.display = 'none';
    };

    // Remove product function (global for onclick)
    window.removeProduct = function(productId) {
        selectedProductIds.delete(productId);
        
        // Remove from selected list
        const badge = document.querySelector(`[data-product-id="${productId}"]`);
        if (badge) {
            badge.remove();
        }

        // Remove hidden input
        document.querySelectorAll('input[name="product_ids[]"]').forEach(input => {
            if (parseInt(input.value) === productId) {
                input.remove();
            }
        });

        // Show empty message if no products selected
        const selectedList = document.getElementById('selected-products-list');
        if (selectedList.children.length === 0) {
            selectedList.innerHTML = '<span class="text-muted">No products selected. Search and click to add products.</span>';
        }

        // Update search results
        searchProducts(document.getElementById('product-search')?.value || '');
    };

    // Form validation
    const form = document.getElementById('flashDealForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (selectedProductIds.size === 0) {
                e.preventDefault();
                document.getElementById('product-error').style.display = 'block';
                productSearchInput?.focus();
                return false;
            }
        });
    }

    // Initial load - show some products if search is empty
    if (productSearchInput && productSearchInput.value.trim() === '') {
        // Load initial products (first 20)
        fetch('{{ route("flash-deals.products.search") }}?exclude_ids=' + JSON.stringify(Array.from(selectedProductIds)), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProducts(data.products);
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
        });
    }
});
</script>
