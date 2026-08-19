(() => {
    'use strict';

    const header = document.querySelector('[data-site-header]');
    const mobileToggle = document.querySelector('[data-mobile-nav-toggle]');
    const mobileNav = document.querySelector('[data-mobile-nav]');

    const updateHeader = () => {
        header?.classList.toggle('is-scrolled', window.scrollY > 10);
    };

    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    mobileToggle?.addEventListener('click', () => {
        mobileNav?.classList.toggle('is-open');
    });

    document.addEventListener('click', (event) => {
        if (
            mobileNav?.classList.contains('is-open')
            && !mobileNav.contains(event.target)
            && !mobileToggle?.contains(event.target)
        ) {
            mobileNav.classList.remove('is-open');
        }
    });

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const sanitizeColor = (value) => /^#[0-9a-fA-F]{3,8}$/.test(String(value ?? ''))
        ? String(value)
        : '#988C75';

    const grid = document.querySelector('[data-menu-grid]');
    const searchInput = document.querySelector('[data-menu-search]');
    const clearButton = document.querySelector('[data-search-clear]');
    const availableOnly = document.querySelector('[data-available-only]');
    const categoryTabs = document.querySelector('[data-category-tabs]');
    const resultCount = document.querySelector('[data-results-count]');
    const resultTitle = document.querySelector('[data-results-title]');
    const emptyState = document.querySelector('[data-menu-empty]');
    const modal = document.querySelector('[data-item-modal]');
    const modalContent = document.querySelector('[data-item-modal-content]');

    if (grid) {
        const apiUrl = grid.dataset.apiUrl;
        const itemApiUrl = grid.dataset.itemApiUrl;
        let activeCategory = Number(grid.dataset.initialCategory || 0);
        let requestController = null;
        let debounceTimer = null;

        const activeTab = categoryTabs?.querySelector(`[data-category-id="${activeCategory}"]`);
        if (activeTab) {
            categoryTabs.querySelectorAll('.category-tab').forEach((tab) => tab.classList.remove('active'));
            activeTab.classList.add('active');
            activeTab.scrollIntoView({ inline: 'center', block: 'nearest' });
        }

        const cardTemplate = (item) => {
            const tags = Array.isArray(item.tags)
                ? item.tags.slice(0, 3).map((tag) => `
                    <span class="menu-tag" style="--tag-color:${sanitizeColor(tag.color)}">
                        ${escapeHtml(tag.title)}
                    </span>
                `).join('')
                : '';

            return `
                <article class="menu-card" tabindex="0" role="button" data-item-id="${Number(item.id)}" aria-label="مشاهده جزئیات ${escapeHtml(item.name_fa)}">
                    <div class="menu-card-image">
                        <img loading="lazy" src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name_fa)}">
                        <span class="availability-badge ${item.availability === 'available' ? 'available' : 'unavailable'}">
                            ${item.availability === 'available' ? 'موجود' : 'ناموجود'}
                        </span>
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-card-heading">
                            <div>
                                <h3>${escapeHtml(item.name_fa)}</h3>
                                <span class="menu-card-name-en">${escapeHtml(item.name_en || '')}</span>
                            </div>
                        </div>
                        <p class="menu-card-description">${escapeHtml(item.short_description || '')}</p>
                        <div class="menu-tags">${tags}</div>
                        <div class="menu-card-bottom">
                            <div class="menu-card-price">
                                <strong>${escapeHtml(item.formatted_price)}</strong>
                                ${item.formatted_old_price ? `<del>${escapeHtml(item.formatted_old_price)}</del>` : ''}
                            </div>
                            <button class="menu-card-open" type="button" data-item-id="${Number(item.id)}" aria-label="بازکردن جزئیات">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                        </div>
                    </div>
                </article>
            `;
        };

        const showLoading = () => {
            grid.innerHTML = Array.from({ length: 6 }, () => `
                <article class="menu-card skeleton-card" aria-hidden="true">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton-content">
                        <span class="skeleton skeleton-line wide"></span>
                        <span class="skeleton skeleton-line"></span>
                        <span class="skeleton skeleton-line short"></span>
                    </div>
                </article>
            `).join('');
            emptyState.hidden = true;
            if (resultCount) resultCount.textContent = 'در حال دریافت...';
        };

        const loadMenu = async () => {
            requestController?.abort();
            requestController = new AbortController();
            showLoading();

            const params = new URLSearchParams();
            if (activeCategory > 0) params.set('category', String(activeCategory));
            if (availableOnly?.checked) params.set('available', '1');
            const query = searchInput?.value.trim() || '';
            if (query) params.set('q', query);

            try {
                const response = await fetch(`${apiUrl}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                    signal: requestController.signal
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'دریافت منو انجام نشد.');
                }

                grid.innerHTML = data.items.map(cardTemplate).join('');
                emptyState.hidden = data.items.length > 0;

                if (resultCount) {
                    resultCount.textContent = `${new Intl.NumberFormat('fa-IR').format(data.count)} آیتم`;
                }

                const selectedTab = categoryTabs?.querySelector('.category-tab.active');
                if (resultTitle) {
                    resultTitle.textContent = selectedTab?.textContent.trim() || 'همه آیتم‌ها';
                }
            } catch (error) {
                if (error.name === 'AbortError') return;

                grid.innerHTML = `
                    <div class="menu-load-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <p>${escapeHtml(error.message)}</p>
                        <button type="button" data-menu-retry>تلاش دوباره</button>
                    </div>
                `;
                if (resultCount) resultCount.textContent = 'خطا در دریافت';
            }
        };

        const openItem = async (id) => {
            if (!modal || !modalContent) return;

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            modalContent.innerHTML = `
                <div class="modal-loading">
                    <span class="spinner"></span>
                    در حال دریافت جزئیات...
                </div>
            `;

            try {
                const response = await fetch(`${itemApiUrl}?id=${encodeURIComponent(id)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'جزئیات آیتم دریافت نشد.');
                }

                const item = data.item;
                const tags = Array.isArray(item.tags)
                    ? item.tags.map((tag) => `
                        <span class="menu-tag" style="--tag-color:${sanitizeColor(tag.color)}">
                            ${escapeHtml(tag.title)}
                        </span>
                    `).join('')
                    : '';

                const related = Array.isArray(item.related) && item.related.length
                    ? `
                        <div class="modal-related">
                            <span>آیتم‌های مشابه</span>
                            <div class="related-mini-grid">
                                ${item.related.map((relatedItem) => `
                                    <button class="related-mini-card" type="button" data-item-id="${Number(relatedItem.id)}">
                                        <img src="${escapeHtml(relatedItem.image_url)}" alt="${escapeHtml(relatedItem.name_fa)}">
                                        <span>
                                            <b>${escapeHtml(relatedItem.name_fa)}</b>
                                            <small>${escapeHtml(relatedItem.formatted_price)}</small>
                                        </span>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    `
                    : '';

                modalContent.innerHTML = `
                    <div class="modal-item-layout">
                        <div class="modal-item-image">
                            <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name_fa)}">
                            <span class="availability-badge ${item.availability === 'available' ? 'available' : 'unavailable'}">
                                ${item.availability === 'available' ? 'موجود' : 'ناموجود'}
                            </span>
                        </div>
                        <div class="modal-item-copy">
                            <p class="public-eyebrow">${escapeHtml(item.category_name)}</p>
                            <h2 id="item-modal-title">${escapeHtml(item.name_fa)}</h2>
                            <small>${escapeHtml(item.name_en || '')}</small>
                            <p class="modal-item-description">${escapeHtml(item.full_description || item.short_description || '')}</p>
                            <div class="menu-tags">${tags}</div>

                            <div class="modal-detail-grid">
                                <div class="modal-detail-box">
                                    <span>مواد تشکیل‌دهنده</span>
                                    <p>${escapeHtml(item.ingredients || 'ثبت نشده')}</p>
                                </div>
                                <div class="modal-detail-box">
                                    <span>کالری</span>
                                    <p>${item.calories ? `${new Intl.NumberFormat('fa-IR').format(item.calories)} کیلوکالری` : 'ثبت نشده'}</p>
                                </div>
                                <div class="modal-detail-box">
                                    <span>حساسیت‌های غذایی</span>
                                    <p>${escapeHtml(item.allergens || 'موردی ثبت نشده')}</p>
                                </div>
                                <div class="modal-detail-box">
                                    <span>وضعیت</span>
                                    <p>${item.availability === 'available' ? 'آماده سفارش' : 'در حال حاضر ناموجود'}</p>
                                </div>
                            </div>

                            ${related}

                            <div class="modal-price-row">
                                <div class="modal-price">
                                    <strong>${escapeHtml(item.formatted_price)}</strong>
                                    ${item.formatted_old_price ? `<del>${escapeHtml(item.formatted_old_price)}</del>` : ''}
                                </div>
                                <div class="modal-actions">
                                    <button type="button" data-share-url="${escapeHtml(item.share_url)}" data-share-title="${escapeHtml(item.name_fa)}" aria-label="اشتراک‌گذاری">
                                        <i class="fa-solid fa-share-nodes"></i>
                                    </button>
                                    <a href="${escapeHtml(item.share_url)}" aria-label="صفحه مستقل آیتم">
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } catch (error) {
                modalContent.innerHTML = `
                    <div class="not-found-card">
                        <span><i class="fa-solid fa-circle-exclamation"></i></span>
                        <h1>دریافت جزئیات ممکن نشد</h1>
                        <p>${escapeHtml(error.message)}</p>
                    </div>
                `;
            }
        };

        const closeModal = () => {
            modal?.classList.remove('is-open');
            modal?.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        categoryTabs?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-category-id]');
            if (!button) return;

            activeCategory = Number(button.dataset.categoryId || 0);
            categoryTabs.querySelectorAll('.category-tab').forEach((tab) => tab.classList.remove('active'));
            button.classList.add('active');
            loadMenu();

            const url = new URL(window.location.href);
            if (activeCategory > 0) {
                url.searchParams.set('category', String(activeCategory));
            } else {
                url.searchParams.delete('category');
            }
            history.replaceState(null, '', url);
        });

        searchInput?.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(loadMenu, 320);
        });

        clearButton?.addEventListener('click', () => {
            if (!searchInput) return;
            searchInput.value = '';
            searchInput.focus();
            loadMenu();
        });

        availableOnly?.addEventListener('change', loadMenu);

        grid.addEventListener('click', (event) => {
            const target = event.target.closest('[data-item-id]');
            if (target) openItem(target.dataset.itemId);
            if (event.target.closest('[data-menu-retry]')) loadMenu();
        });

        grid.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-item-id]')) {
                event.preventDefault();
                openItem(event.target.dataset.itemId);
            }
        });

        modal?.addEventListener('click', (event) => {
            if (event.target.closest('[data-modal-close]')) {
                closeModal();
                return;
            }

            const relatedItem = event.target.closest('.related-mini-card[data-item-id]');
            if (relatedItem) {
                openItem(relatedItem.dataset.itemId);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
                closeModal();
            }
        });

        loadMenu();
    }

    document.addEventListener('click', async (event) => {
        const shareButton = event.target.closest('[data-share-url]');
        if (!shareButton) return;

        const url = shareButton.dataset.shareUrl;
        const title = shareButton.dataset.shareTitle || document.title;

        try {
            if (navigator.share) {
                await navigator.share({ title, url });
            } else {
                await navigator.clipboard.writeText(url);
                const original = shareButton.innerHTML;
                shareButton.innerHTML = '<i class="fa-solid fa-check"></i>';
                setTimeout(() => { shareButton.innerHTML = original; }, 1500);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.prompt('لینک را کپی کنید:', url);
            }
        }
    });
})();
