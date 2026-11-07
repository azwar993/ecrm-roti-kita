document.addEventListener('DOMContentLoaded', function () {
    function showImageFallback(image) {
        image.hidden = true;
        const fallback = image.parentElement?.querySelector('[data-store-image-fallback]');
        if (fallback) fallback.hidden = false;
    }

    document.addEventListener('error', function (event) {
        const image = event.target;
        if (image instanceof HTMLImageElement && image.matches('[data-store-image]')) {
            showImageFallback(image);
        }
    }, true);

    document.querySelectorAll('img[data-store-image]').forEach(function (image) {
        if (image.complete && image.naturalWidth === 0) showImageFallback(image);
    });

    document.querySelectorAll('[data-product-carousel]').forEach(function (carousel) {
        const track = carousel.querySelector('[data-carousel-track]');
        const previous = carousel.querySelector('[data-carousel-prev]');
        const next = carousel.querySelector('[data-carousel-next]');
        if (!track || !previous || !next) return;

        const cards = Array.from(track.querySelectorAll('.rks-product-card'));
        const step = function () {
            const card = cards[0];
            if (!card) return track.clientWidth;
            const styles = window.getComputedStyle(track);
            return card.getBoundingClientRect().width + (parseFloat(styles.columnGap || styles.gap) || 0);
        };
        const updateButtons = function () {
            const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
            previous.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maxScroll - 2;
            previous.hidden = cards.length === 0;
            next.hidden = cards.length === 0;
        };

        previous.addEventListener('click', function () {
            track.scrollBy({ left: -step(), behavior: 'smooth' });
        });
        next.addEventListener('click', function () {
            track.scrollBy({ left: step(), behavior: 'smooth' });
        });
        track.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                track.scrollBy({ left: event.key === 'ArrowLeft' ? -step() : step(), behavior: 'smooth' });
            }
        });
        track.addEventListener('scroll', function () {
            window.requestAnimationFrame(updateButtons);
        }, { passive: true });
        window.addEventListener('resize', updateButtons, { passive: true });
        updateButtons();
    });

    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.getElementById('rks-main-nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        toggle.setAttribute('aria-label', expanded ? 'Buka navigasi' : 'Tutup navigasi');
        nav.classList.toggle('is-open', !expanded);
    });
});
