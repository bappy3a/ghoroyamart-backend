<script>
    document.addEventListener('DOMContentLoaded', () => {
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        const iconClassInput = document.getElementById('icon_class');
        const iconClassPreview = document.querySelector('#icon-class-preview i');
        let slugEdited = Boolean(slugInput?.value);

        const slugify = (value) => value
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 150);

        const bindImagePreview = (inputId, imgId, placeholderId) => {
            const input = document.getElementById(inputId);
            const previewImg = document.getElementById(imgId);
            const placeholder = document.getElementById(placeholderId);

            input?.addEventListener('change', () => {
                const file = input.files?.[0];
                if (!file) {
                    if (previewImg) {
                        previewImg.src = '';
                        previewImg.classList.add('d-none');
                    }
                    placeholder?.classList.remove('d-none');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    if (!previewImg) {
                        return;
                    }
                    previewImg.src = event.target?.result ?? '';
                    previewImg.classList.remove('d-none');
                    placeholder?.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            });
        };

        slugInput?.addEventListener('input', () => {
            slugEdited = (slugInput?.value?.length ?? 0) > 0;
        });

        nameInput?.addEventListener('input', () => {
            if (slugEdited || !slugInput) {
                return;
            }

            slugInput.value = slugify(nameInput.value);
        });

        iconClassInput?.addEventListener('input', () => {
            if (!iconClassPreview) {
                return;
            }
            const className = (iconClassInput.value || 'ri-image-line').trim();
            iconClassPreview.className = className || 'ri-image-line';
        });

        bindImagePreview('icon', 'icon-preview-img', 'icon-preview-placeholder');
        bindImagePreview('image', 'image-preview-img', 'image-preview-placeholder');
        bindImagePreview('meta_image', 'meta-image-preview-img', 'meta-image-preview-placeholder');
    });
</script>
