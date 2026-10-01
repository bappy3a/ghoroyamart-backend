<script>
    document.addEventListener('DOMContentLoaded', () => {
        const imageInput = document.getElementById('image');
        const imagePreviewImg = document.getElementById('image-preview-img');
        const imagePreviewPlaceholder = document.getElementById('image-preview-placeholder');
        const textColorInput = document.getElementById('text_color');
        const textColorValue = document.getElementById('text-color-value');

        textColorInput?.addEventListener('input', () => {
            if (textColorValue) {
                textColorValue.value = textColorInput.value;
                textColorValue.textContent = textColorInput.value;
            }
        });

        imageInput?.addEventListener('change', () => {
            const file = imageInput.files?.[0];
            if (!file) {
                if (imagePreviewImg) {
                    imagePreviewImg.src = '';
                    imagePreviewImg.classList.add('d-none');
                }
                imagePreviewPlaceholder?.classList.remove('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                if (!imagePreviewImg) {
                    return;
                }

                imagePreviewImg.src = event.target?.result ?? '';
                imagePreviewImg.classList.remove('d-none');
                imagePreviewPlaceholder?.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    });
</script>
