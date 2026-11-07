document.addEventListener('DOMContentLoaded', function () {
    const menus = Array.from(document.querySelectorAll('[data-account-menu]'));

    function closeMenu(menu) {
        const trigger = menu.querySelector('[data-account-trigger]');
        const dropdown = menu.querySelector('[data-account-dropdown]');
        if (!trigger || !dropdown) return;
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', 'Buka menu akun');
        dropdown.hidden = true;
    }

    menus.forEach(function (menu) {
        const trigger = menu.querySelector('[data-account-trigger]');
        const dropdown = menu.querySelector('[data-account-dropdown]');
        if (!trigger || !dropdown) return;

        trigger.addEventListener('click', function () {
            const willOpen = trigger.getAttribute('aria-expanded') !== 'true';
            menus.forEach(function (otherMenu) {
                if (otherMenu !== menu) closeMenu(otherMenu);
            });
            trigger.setAttribute('aria-expanded', String(willOpen));
            trigger.setAttribute('aria-label', willOpen ? 'Tutup menu akun' : 'Buka menu akun');
            dropdown.hidden = !willOpen;
        });

        dropdown.addEventListener('click', function (event) {
            if (event.target.closest('a')) closeMenu(menu);
        });
    });

    document.addEventListener('click', function (event) {
        menus.forEach(function (menu) {
            if (!menu.contains(event.target)) closeMenu(menu);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        menus.forEach(closeMenu);
    });
});
