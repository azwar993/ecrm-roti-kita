(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.querySelector('[data-product-image-input]');
        const image = document.querySelector('[data-product-preview-image]');
        const placeholder = document.getElementById('product-photo-placeholder');
        const message = document.getElementById('product-image-message');
        const removePhoto = document.querySelector('[data-remove-product-image]');

        if (!input || !image || !placeholder) return;

        let temporaryUrl = null;
        const originalUrl = image.getAttribute('src');

        function showPlaceholder(text) {
            image.hidden = true;
            placeholder.hidden = false;
            if (message && text) message.textContent = text;
        }

        function showImage(url) {
            image.src = url;
            image.hidden = false;
            placeholder.hidden = true;
        }

        image.addEventListener('error', function () {
            showPlaceholder('Foto tidak dapat dimuat. Pilih foto pengganti sebelum mengaktifkan produk.');
        });

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) {
                if (temporaryUrl) URL.revokeObjectURL(temporaryUrl);
                temporaryUrl = null;
                if (!(removePhoto && removePhoto.checked) && originalUrl) showImage(originalUrl);
                else showPlaceholder('Foto belum dipilih. Produk harus tetap nonaktif tanpa foto.');
                return;
            }

            const validType = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
            if (!validType || file.size > 4 * 1024 * 1024) {
                input.value = '';
                if (message) message.textContent = !validType
                    ? 'Format tidak didukung. Pilih JPG, JPEG, PNG, atau WEBP.'
                    : 'Ukuran file melebihi batas 4 MB.';
                return;
            }

            if (temporaryUrl) URL.revokeObjectURL(temporaryUrl);
            temporaryUrl = URL.createObjectURL(file);
            if (removePhoto) removePhoto.checked = false;
            showImage(temporaryUrl);
            if (message) message.textContent = 'Preview foto pilihan. Foto tersimpan setelah formulir berhasil disimpan.';
        });

        if (removePhoto) {
            removePhoto.disabled = false;
            removePhoto.addEventListener('change', function () {
                if (removePhoto.checked && !window.confirm('Hapus foto produk ini setelah perubahan berhasil disimpan?')) {
                    removePhoto.checked = false;
                    return;
                }
                if (removePhoto.checked) showPlaceholder('Foto akan dihapus setelah perubahan berhasil disimpan. Produk harus nonaktif.');
                else if (originalUrl) showImage(originalUrl);
                else showPlaceholder('Foto belum tersedia.');
            });
            if (removePhoto.checked) showPlaceholder('Foto akan dihapus setelah perubahan berhasil disimpan. Produk harus nonaktif.');
        }
    });
})();
