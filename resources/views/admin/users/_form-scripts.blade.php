<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Avatar preview
        const avatarInput = document.getElementById('avatar');
        const avatarPreviewImg = document.getElementById('avatar-preview-img');
        const avatarPreviewPlaceholder = document.getElementById('avatar-preview-placeholder');

        avatarInput?.addEventListener('change', () => {
            const file = avatarInput.files?.[0];
            if (!file) {
                if (avatarPreviewImg) {
                    avatarPreviewImg.src = '';
                    avatarPreviewImg.classList.add('d-none');
                }
                avatarPreviewPlaceholder?.classList.remove('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                if (!avatarPreviewImg) {
                    return;
                }

                avatarPreviewImg.src = event.target?.result ?? '';
                avatarPreviewImg.classList.remove('d-none');
                avatarPreviewPlaceholder?.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

        // Cover photo preview
        const coverInput = document.getElementById('cover_photo');
        const coverPreviewImg = document.getElementById('cover-preview-img');
        const coverPreviewPlaceholder = document.getElementById('cover-preview-placeholder');

        coverInput?.addEventListener('change', () => {
            const file = coverInput.files?.[0];
            if (!file) {
                if (coverPreviewImg) {
                    coverPreviewImg.src = '';
                    coverPreviewImg.classList.add('d-none');
                }
                coverPreviewPlaceholder?.classList.remove('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                if (!coverPreviewImg) {
                    return;
                }

                coverPreviewImg.src = event.target?.result ?? '';
                coverPreviewImg.classList.remove('d-none');
                coverPreviewPlaceholder?.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    });
</script>
