(() => {
    const flagKey = 'rotikita:page-navigation';
    const loader = document.querySelector('[data-page-loader]');
    if (!loader) return;

    let failSafeTimer;
    const showLoader = () => {
        document.documentElement.classList.add('rks-navigation-pending');
        loader.setAttribute('aria-hidden', 'false');
    };
    const hideLoader = () => {
        window.clearTimeout(failSafeTimer);
        document.documentElement.classList.remove('rks-navigation-pending');
        loader.setAttribute('aria-hidden', 'true');
        try { window.sessionStorage.removeItem(flagKey); } catch {}
    };
    const armNavigation = () => {
        try { window.sessionStorage.setItem(flagKey, String(Date.now())); } catch {}
        showLoader();
        window.clearTimeout(failSafeTimer);
        failSafeTimer = window.setTimeout(hideLoader, 15000);
    };
    const isInternalPageLink = (link, event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
        if (link.hasAttribute('download') || (link.target && link.target.toLowerCase() !== '_self')) return false;
        if (link.hasAttribute('data-no-page-loader') || link.closest('[data-ajax], [data-no-page-loader]')) return false;
        let destination;
        try { destination = new URL(link.href, window.location.href); } catch { return false; }
        if (!['http:', 'https:'].includes(destination.protocol) || destination.origin !== window.location.origin) return false;
        if (destination.pathname === window.location.pathname && destination.search === window.location.search && destination.hash) return false;
        if (destination.href === window.location.href) return false;
        return true;
    };

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (link && isInternalPageLink(link, event)) {
            armNavigation();
            queueMicrotask(() => { if (event.defaultPrevented) hideLoader(); });
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        if (form.hasAttribute('data-ajax') || form.hasAttribute('data-remote') || form.hasAttribute('data-no-page-loader') || form.hasAttribute('wire:submit')) return;
        if (form.target && form.target.toLowerCase() !== '_self') return;
        const action = new URL(form.action || window.location.href, window.location.href);
        if (action.origin !== window.location.origin) return;
        armNavigation();
        queueMicrotask(() => { if (event.defaultPrevented) hideLoader(); });
    });

    let hadFreshNavigationFlag = false;
    try {
        const timestamp = Number(window.sessionStorage.getItem(flagKey));
        hadFreshNavigationFlag = Number.isFinite(timestamp) && timestamp > 0 && Date.now() - timestamp < 15000;
        if (!hadFreshNavigationFlag) window.sessionStorage.removeItem(flagKey);
    } catch {}

    if (hadFreshNavigationFlag && performance.getEntriesByType('navigation')[0]?.type !== 'back_forward') {
        showLoader();
        failSafeTimer = window.setTimeout(hideLoader, 15000);
    } else {
        hideLoader();
    }

    const finishWhenReady = () => window.setTimeout(hideLoader, 120);
    if (document.readyState === 'complete') finishWhenReady();
    else window.addEventListener('load', finishWhenReady, { once: true });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) hideLoader();
    });
    window.addEventListener('pagehide', () => window.clearTimeout(failSafeTimer));
})();
