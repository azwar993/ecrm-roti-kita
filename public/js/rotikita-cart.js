document.addEventListener('submit', async function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.action.includes('/customer/cart/items')) return;
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;
    try {
        const response = await fetch(form.action, {
            method: 'POST', body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Produk belum dapat ditambahkan.');
        document.querySelectorAll('.rks-cart-count, [data-cart-count]').forEach(node => node.textContent = data.cart_count);
        let toast = document.getElementById('rk-cart-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'rk-cart-toast';
            toast.setAttribute('role', 'status');
            toast.style.cssText = 'position:fixed;z-index:9999;right:20px;bottom:20px;background:#4d3025;color:#fff8ed;padding:12px 18px;border-radius:12px;box-shadow:0 8px 24px #24140f33';
            document.body.appendChild(toast);
        }
        toast.textContent = data.message;
        toast.hidden = false;
        clearTimeout(window.rkCartToastTimer);
        window.rkCartToastTimer = setTimeout(() => { toast.hidden = true; }, 2800);
    } catch (error) {
        window.location.assign(document.referrer || '/customer/cart');
    } finally {
        if (button) button.disabled = false;
    }
}, true);
