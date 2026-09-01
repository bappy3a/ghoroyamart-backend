<script>
    (() => {
        if (window.adminSkuSpaceSanitizerLoaded) {
            return;
        }

        window.adminSkuSpaceSanitizerLoaded = true;

        function isSkuInput(element) {
            if (!(element instanceof HTMLInputElement)) {
                return false;
            }

            return element.name === 'sku'
                || /^variants\[[^\]]+\]\[sku\]$/.test(element.name)
                || element.dataset.field === 'sku';
        }

        function removeSkuWhitespace(input) {
            const original = input.value;
            const cleaned = original.replace(/\s+/g, '');

            if (original === cleaned) {
                return;
            }

            const start = input.selectionStart ?? original.length;
            const removedBeforeCursor = (original.slice(0, start).match(/\s/g) || []).length;
            const cursor = Math.max(0, start - removedBeforeCursor);

            input.value = cleaned;

            try {
                input.setSelectionRange(cursor, cursor);
            } catch (error) {
                // Some input types do not support cursor placement.
            }
        }

        function sanitizeSkuFields(root = document) {
            const inputs = root instanceof HTMLInputElement
                ? [root]
                : Array.from(root.querySelectorAll?.('input') || []);

            inputs.forEach((input) => {
                if (isSkuInput(input)) {
                    removeSkuWhitespace(input);
                }
            });
        }

        window.sanitizeSkuFields = sanitizeSkuFields;

        document.addEventListener('input', (event) => {
            if (isSkuInput(event.target)) {
                removeSkuWhitespace(event.target);
            }
        }, true);

        document.addEventListener('change', (event) => {
            if (isSkuInput(event.target)) {
                removeSkuWhitespace(event.target);
            }
        }, true);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => sanitizeSkuFields());
        } else {
            sanitizeSkuFields();
        }

        new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof HTMLElement) {
                        sanitizeSkuFields(node);
                    }
                });
            });
        }).observe(document.documentElement, {
            childList: true,
            subtree: true,
        });
    })();
</script>
