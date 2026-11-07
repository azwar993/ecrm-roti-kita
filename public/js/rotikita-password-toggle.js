(function () {
    if (window.__rkPasswordToggleBound) return;
    window.__rkPasswordToggleBound = true;

    const eyeMarkup = '<path d="M2.5 12s3.4-6.4 9.5-6.4 9.5 6.4 9.5 6.4-3.4 6.4-9.5 6.4S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/>';
    const eyeOffMarkup = '<path d="m3 3 18 18M10.6 5.8c.5-.1.9-.2 1.4-.2 6.1 0 9.5 6.4 9.5 6.4a16 16 0 0 1-3.1 3.7M6.2 6.3C3.8 8 2.5 12 2.5 12s3.4 6.4 9.5 6.4c1 0 2-.2 2.8-.5M9.9 9.9a3 3 0 0 0 4.2 4.2"/>';

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-password-toggle]');
        if (!button) return;

        const targetId = button.getAttribute('data-target');
        const input = targetId ? document.getElementById(targetId) : null;
        const icon = button.querySelector('svg');
        if (!input || !icon) return;

        const shouldReveal = input.type === 'password';
        input.type = shouldReveal ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(shouldReveal));
        button.setAttribute('aria-label', shouldReveal ? 'Sembunyikan password' : 'Tampilkan password');
        icon.innerHTML = shouldReveal ? eyeOffMarkup : eyeMarkup;
    });
})();
