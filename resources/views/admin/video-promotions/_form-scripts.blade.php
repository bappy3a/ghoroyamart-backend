<script>
    document.addEventListener('DOMContentLoaded', () => {
        const videoUrlInput = document.getElementById('video_url');
        const videoPreview = document.getElementById('video-preview');
        const videoPreviewCard = document.getElementById('video-preview-card');

        // Convert YouTube/Vimeo URLs to embed format for preview
        const convertToEmbedUrl = (url) => {
            if (!url) return '';

            // YouTube
            const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
            const youtubeMatch = url.match(youtubeRegex);
            if (youtubeMatch) {
                return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
            }

            // Vimeo
            const vimeoRegex = /(?:vimeo\.com\/)(\d+)/;
            const vimeoMatch = url.match(vimeoRegex);
            if (vimeoMatch) {
                return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
            }

            return url;
        };

        const updateVideoPreview = () => {
            if (!videoUrlInput || !videoPreview || !videoPreviewCard) return;

            const url = videoUrlInput.value.trim();
            
            if (url) {
                const embedUrl = convertToEmbedUrl(url);
                if (embedUrl) {
                    videoPreview.src = embedUrl;
                    videoPreviewCard.style.display = '';
                } else {
                    videoPreviewCard.style.display = 'none';
                }
            } else {
                videoPreviewCard.style.display = 'none';
            }
        };

        // Initialize preview if URL exists on page load
        if (videoUrlInput && videoUrlInput.value) {
            // Small delay to ensure DOM is ready
            setTimeout(updateVideoPreview, 100);
        }

        // Update preview on input
        videoUrlInput?.addEventListener('input', updateVideoPreview);
        videoUrlInput?.addEventListener('paste', () => {
            setTimeout(updateVideoPreview, 100);
        });
    });
</script>
