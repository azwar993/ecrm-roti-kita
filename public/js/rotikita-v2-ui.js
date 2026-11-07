(() => {
    'use strict';

    const statusLabels = {
        pending: 'Menunggu',
        processing: 'Diproses',
        ready: 'Siap diambil',
        completed: 'Selesai',
        cancelled: 'Dibatalkan',
        new: 'Baru',
        resolved: 'Selesai',
        active: 'Aktif',
        low: 'Stok menipis',
        out: 'Habis',
    };

    let toastTimer;
    let toastElement;

    function toast(message) {
        if (!toastElement) {
            toastElement = document.createElement('div');
            toastElement.className = 'rk-toast';
            toastElement.setAttribute('role', 'status');
            toastElement.setAttribute('aria-live', 'polite');
            document.body.appendChild(toastElement);
        }
        toastElement.textContent = message;
        toastElement.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => toastElement?.classList.remove('is-visible'), 2800);
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('rk-modal-open');
        modal.querySelector('input, select, textarea, button')?.focus();
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.rk-modal[aria-hidden="false"]')) {
            document.body.classList.remove('rk-modal-open');
        }
    }

    function setBadge(element, status) {
        if (!element) return;
        Object.keys(statusLabels).forEach((key) => element.classList.remove(`rk-badge-${key}`));
        element.classList.add(`rk-badge-${status}`);
        const feedbackStatusLabel = element.id === 'feedback-detail-status' && status === 'processing' ? 'Dalam proses' : null;
        element.textContent = feedbackStatusLabel || statusLabels[status] || status;
    }

    document.addEventListener('click', (event) => {
        const modalOpen = event.target.closest('[data-open-modal]');
        if (modalOpen) openModal(modalOpen.dataset.openModal);

        const modalClose = event.target.closest('[data-close-modal]');
        if (modalClose) closeModal(modalClose.closest('.rk-modal'));

        const demoAction = event.target.closest('[data-demo-toast]');
        if (demoAction) toast(demoAction.dataset.demoToast);

        const addCart = event.target.closest('[data-add-cart]');
        if (addCart) toast('Interaksi keranjang masih preview UI; transaksi belum disimpan.');

        const copyButton = event.target.closest('[data-copy-text]');
        if (copyButton) {
            const text = copyButton.dataset.copyText || '';
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => toast(`Kode ${text} disalin.`)).catch(() => toast(`Kode promo: ${text}`));
            } else {
                toast(`Kode promo: ${text}`);
            }
        }

        const orderPick = event.target.closest('.order-pick');
        if (orderPick) {
            const row = orderPick.closest('tr');
            if (!row) return;
            document.querySelectorAll('#order-table tr').forEach((item) => item.classList.toggle('is-selected-row', item === row));
            document.getElementById('order-detail-number')?.replaceChildren(document.createTextNode(`#${row.dataset.order || ''}`));
            document.getElementById('order-detail-name')?.replaceChildren(document.createTextNode(row.dataset.customer || ''));
            document.getElementById('order-detail-email')?.replaceChildren(document.createTextNode(row.cells[1]?.querySelector('small')?.textContent || ''));
            document.getElementById('order-detail-items')?.replaceChildren(document.createTextNode(row.dataset.items || ''));
            document.getElementById('order-detail-total')?.replaceChildren(document.createTextNode(row.dataset.total || ''));
            document.getElementById('order-detail-payment')?.replaceChildren(document.createTextNode(row.dataset.payment || ''));
            const visibleStatus = row.querySelector('td .rk-badge')?.textContent || statusLabels[row.dataset.status] || 'Menunggu';
            const detailBadge = document.getElementById('order-detail-badge');
            if (detailBadge) {
                Object.keys(statusLabels).forEach((key) => detailBadge.classList.remove(`rk-badge-${key}`));
                detailBadge.classList.add(`rk-badge-${row.dataset.status || 'pending'}`);
                detailBadge.textContent = visibleStatus;
            }
            const statusSelect = document.getElementById('order-status-update');
            if (statusSelect) statusSelect.value = row.dataset.status || 'pending';
            toast(`Ringkasan ${row.dataset.order || 'pesanan'} dipilih.`);
        }

        const feedbackRow = event.target.closest('[data-feedback-row]');
        if (feedbackRow) {
            document.querySelectorAll('[data-feedback-row]').forEach((row) => row.classList.toggle('is-selected', row === feedbackRow));
            const assignText = (id, value) => {
                const element = document.getElementById(id);
                if (element) element.textContent = value || '';
            };
            assignText('feedback-detail-name', feedbackRow.dataset.name);
            assignText('feedback-detail-email', feedbackRow.dataset.email);
            assignText('feedback-detail-meta', `Dikirim ${feedbackRow.dataset.date || ''}`);
            assignText('feedback-detail-type', `${feedbackRow.dataset.type || ''} · ${feedbackRow.dataset.order || ''}`);
            assignText('feedback-detail-message', feedbackRow.dataset.message);
            assignText('feedback-detail-initial', (feedbackRow.dataset.name || 'RK').split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase());
            assignText('feedback-detail-stars', feedbackRow.dataset.rating ? '★'.repeat(Number(feedbackRow.dataset.rating)) + '☆'.repeat(5 - Number(feedbackRow.dataset.rating)) : 'Tidak ada rating');
            setBadge(document.getElementById('feedback-detail-status'), feedbackRow.dataset.status || 'new');
            const statusSelect = document.getElementById('feedback-status-select');
            if (statusSelect) statusSelect.value = feedbackRow.dataset.status || 'new';
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') document.querySelectorAll('.rk-modal[aria-hidden="false"]').forEach(closeModal);
    });

    document.querySelectorAll('[data-preview-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            toast('Form ini masih preview UI. Belum ada perubahan yang disimpan ke database.');
            const modal = form.closest('.rk-modal');
            if (modal) closeModal(modal);
        });
    });

    // Search and filter tables. These controls are front-end preview only.
    function filterTable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const search = document.querySelector(`[data-table-search="${tableId}"]`);
        const statusFilter = document.querySelector(`[data-table-filter="${tableId}"]`);
        const categoryFilter = document.querySelector(`[data-table-category="${tableId}"]`);
        const run = () => {
            const query = (search?.value || '').trim().toLowerCase();
            const status = statusFilter?.value || 'all';
            const category = categoryFilter?.value || 'all';
            table.querySelectorAll('tr').forEach((row) => {
                const searchText = (row.dataset.search || row.innerText || '').toLowerCase();
                const searchMatch = searchText.includes(query);
                const statusMatch = status === 'all' || row.dataset.status === status;
                const categoryMatch = category === 'all' || row.dataset.category === category;
                row.hidden = !(searchMatch && statusMatch && categoryMatch);
            });
        };
        [search, statusFilter, categoryFilter].filter(Boolean).forEach((element) => {
            element.addEventListener(element.tagName === 'INPUT' ? 'input' : 'change', run);
        });
    }

    ['customer-table', 'product-table', 'order-table', 'customer-order-table'].forEach(filterTable);

    // Catalog search and category chips.
    const catalogSearch = document.getElementById('catalog-search');
    const productCards = [...document.querySelectorAll('[data-product-card]')];
    const emptyCatalog = document.getElementById('catalog-empty');
    let activeCategory = 'all';
    function filterCatalog() {
        const query = (catalogSearch?.value || '').trim().toLowerCase();
        let visible = 0;
        productCards.forEach((card) => {
            const textMatch = (card.dataset.search || '').includes(query);
            const categoryMatch = activeCategory === 'all' || card.dataset.category === activeCategory;
            const show = textMatch && categoryMatch;
            card.hidden = !show;
            if (show) visible += 1;
        });
        if (emptyCatalog) emptyCatalog.hidden = visible > 0;
    }
    catalogSearch?.addEventListener('input', filterCatalog);
    document.querySelectorAll('[data-product-category]').forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.productCategory || 'all';
            document.querySelectorAll('[data-product-category]').forEach((item) => item.classList.toggle('is-active', item === button));
            filterCatalog();
        });
    });

    // Feedback list search/status filters.
    const feedbackSearch = document.querySelector('[data-feedback-search]');
    const feedbackFilter = document.querySelector('[data-feedback-filter]');
    function filterFeedback() {
        const query = (feedbackSearch?.value || '').trim().toLowerCase();
        const status = feedbackFilter?.value || 'all';
        document.querySelectorAll('[data-feedback-row]').forEach((row) => {
            const matchesText = (row.dataset.search || '').includes(query);
            const matchesStatus = status === 'all' || row.dataset.status === status;
            row.hidden = !(matchesText && matchesStatus);
        });
    }
    feedbackSearch?.addEventListener('input', filterFeedback);
    feedbackFilter?.addEventListener('change', filterFeedback);

    // Selected order status updates only the visual preview, not the database.
    document.getElementById('apply-order-status')?.addEventListener('click', () => {
        const row = document.querySelector('#order-table tr.is-selected-row');
        const select = document.getElementById('order-status-update');
        if (!row || !select) {
            toast('Pilih salah satu pesanan terlebih dahulu.');
            return;
        }
        const status = select.value;
        row.dataset.status = status;
        setBadge(row.querySelector('.rk-badge'), status);
        setBadge(document.getElementById('order-detail-badge'), status);
        toast(`Status preview ${row.dataset.order} diubah menjadi ${statusLabels[status]}. Belum disimpan ke database.`);
    });
})();
