<script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoInput = document.getElementById('logo');
        const logoPreviewImg = document.getElementById('logo-preview-img');
        const logoPreviewPlaceholder = document.getElementById('logo-preview-placeholder');

        logoInput?.addEventListener('change', () => {
            const file = logoInput.files?.[0];
            if (!file) {
                if (logoPreviewImg) {
                    logoPreviewImg.src = '';
                    logoPreviewImg.classList.add('d-none');
                }
                logoPreviewPlaceholder?.classList.remove('d-none');
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                if (!logoPreviewImg) {
                    return;
                }

                logoPreviewImg.src = event.target?.result ?? '';
                logoPreviewImg.classList.remove('d-none');
                logoPreviewPlaceholder?.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    });
</script>


