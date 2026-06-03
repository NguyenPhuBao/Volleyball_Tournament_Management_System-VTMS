(function () {
    const appShell = document.querySelector('.app-shell');
    const sidebar = document.querySelector('[data-app-sidebar]');
    const overlay = document.querySelector('[data-app-overlay]');
    const menuButton = document.querySelector('[data-app-menu]');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarToggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
    const searchInput = document.querySelector('[data-app-search]');
    const searchButton = document.querySelector('[data-app-search-button]');
    const content = document.querySelector('[data-app-content]');
    const collapseStorageKey = 'vtms.sidebarCollapsed';
    const desktopQuery = window.matchMedia('(min-width: 981px)');
    let manualSearchDispatching = false;

    function closeSidebar() {
        sidebar?.classList.remove('open');
        appShell?.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-open');
        overlay?.classList.remove('show');
    }

    function openSidebar() {
        sidebar?.classList.add('open');
        appShell?.classList.add('sidebar-open');
        document.body.classList.add('sidebar-open');
        overlay?.classList.add('show');
    }

    function setSidebarCollapsed(collapsed, persist = true) {
        appShell?.classList.toggle('sidebar-collapsed', collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);

        if (sidebarToggle) {
            const title = collapsed ? 'Mở thanh menu' : 'Thu gọn thanh menu';
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarToggle.setAttribute('aria-label', title);
            sidebarToggle.setAttribute('title', title);
        }

        if (sidebarToggleIcon) {
            sidebarToggleIcon.textContent = collapsed ? '>' : '<';
        }

        if (persist) {
            localStorage.setItem(collapseStorageKey, collapsed ? '1' : '0');
        }
    }

    if (desktopQuery.matches && localStorage.getItem(collapseStorageKey) === '1') {
        setSidebarCollapsed(true, false);
    }

    menuButton?.addEventListener('click', () => {
        if (sidebar?.classList.contains('open')) {
            closeSidebar();
            return;
        }
        openSidebar();
    });

    sidebarToggle?.addEventListener('click', () => {
        const collapsed = appShell?.classList.contains('sidebar-collapsed') ?? false;
        setSidebarCollapsed(!collapsed);
        closeSidebar();
    });

    desktopQuery.addEventListener?.('change', (event) => {
        if (!event.matches) {
            closeSidebar();
            return;
        }

        setSidebarCollapsed(localStorage.getItem(collapseStorageKey) === '1', false);
    });

    overlay?.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    function filterRowsAndCards(keyword) {
        if (!content) {
            return;
        }

        const rows = content.querySelectorAll('tbody tr');
        const cards = content.querySelectorAll('.link-card, .coach-card, .athlete-card, .team-card, .dashboard-action-card, .dashboard-stat-card, .dashboard-panel');
        const normalized = keyword.trim().toLowerCase();

        rows.forEach((row) => {
            row.hidden = normalized !== '' && !row.textContent.toLowerCase().includes(normalized);
        });

        cards.forEach((card) => {
            card.hidden = normalized !== '' && !card.textContent.toLowerCase().includes(normalized);
        });
    }

    function runHeaderSearch() {
        filterRowsAndCards(searchInput?.value || '');
    }

    searchButton?.addEventListener('click', runHeaderSearch);

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        runHeaderSearch();
    });

    const filterContainersSelector = [
        '.toolbar',
        '.sidebar__tools',
        '.sidebar-tools',
        '.pane__tools',
        '.filterbar',
        '.filters',
        '[class*="toolbar"]'
    ].join(',');

    const refreshButtonSelector = [
        '#btnRefresh',
        '#t_refresh',
        '#r_refresh',
        '#l_refresh',
        'button[id$="Refresh"]',
        'button[id$="_refresh"]',
        'button[data-refresh]'
    ].join(',');

    function isFilterControl(target) {
        if (!(target instanceof HTMLElement)) {
            return false;
        }

        if (!target.matches('input, select')) {
            return false;
        }

        if (target.closest('.modal, .modal-content, .app-topbar')) {
            return false;
        }

        if (target.matches('[type="button"], [type="submit"], [type="reset"], [type="hidden"], [type="checkbox"], [type="radio"], [type="file"]')) {
            return false;
        }

        return Boolean(target.closest('[data-manual-search-ready="true"]'));
    }

    function findRefreshButton(container) {
        const bySelector = container.querySelector(refreshButtonSelector);
        if (bySelector instanceof HTMLButtonElement) {
            return bySelector;
        }

        return Array.from(container.querySelectorAll('button')).find((button) => {
            const text = (button.textContent || '').trim().toLowerCase();
            return text.includes('làm mới') || text.includes('lam moi') || text.includes('refresh');
        }) || null;
    }

    function triggerContainerSearch(container) {
        const refreshButton = findRefreshButton(container);
        if (refreshButton) {
            refreshButton.click();
            return;
        }

        const control = container.__vtmsDirtyControl || findSearchControl(container);
        if (control) {
            const eventType = control.matches('select, input[type="date"], input[type="datetime-local"], input[type="month"]') ? 'change' : 'input';
            manualSearchDispatching = true;
            control.dispatchEvent(new Event(eventType, { bubbles: true }));
            manualSearchDispatching = false;
            return;
        }

        container.dispatchEvent(new CustomEvent('vtms:manual-search', {
            bubbles: true,
            detail: { container }
        }));
    }

    function findSearchControl(container) {
        return Array.from(container.querySelectorAll('input, select')).find((control) => {
            if (!(control instanceof HTMLElement)) {
                return false;
            }

            if (control.matches('[type="button"], [type="submit"], [type="reset"], [type="hidden"], [type="checkbox"], [type="radio"], [type="file"]')) {
                return false;
            }

            const haystack = [
                control.id || '',
                control.getAttribute('name') || '',
                control.getAttribute('placeholder') || '',
                control.getAttribute('aria-label') || ''
            ].join(' ').toLowerCase();

            return control.matches('input[type="search"]')
                || control.id === 'q'
                || control.id.endsWith('_q')
                || haystack.includes('tìm')
                || haystack.includes('tim')
                || haystack.includes('search')
                || haystack.includes('keyword')
                || haystack.includes('query');
        }) || null;
    }

    function isResetButton(button) {
        const text = (button.textContent || '').trim().toLowerCase();
        return text.includes('xóa lọc') || text.includes('xoa loc') || text.includes('reset') || button.id.toLowerCase().includes('reset');
    }

    function hasNonFilterActionButton(container, refreshButton) {
        return Array.from(container.querySelectorAll('button')).some((button) => {
            if (button === refreshButton || button.dataset.manualSearchButton === 'true' || isResetButton(button)) {
                return false;
            }

            return true;
        });
    }

    function installManualSearchButtons() {
        document.querySelectorAll(filterContainersSelector).forEach((container) => {
            if (!(container instanceof HTMLElement)) {
                return;
            }

            if (container.closest('.modal, .modal-content')) {
                return;
            }

            const hasFilterControl = container.querySelector('input:not([type="hidden"]):not([type="button"]):not([type="submit"]):not([type="reset"]), select');
            const refreshButton = findRefreshButton(container);
            const searchControl = findSearchControl(container);
            const refreshOnlyFilters = refreshButton && !hasNonFilterActionButton(container, refreshButton);

            if (!hasFilterControl || (!searchControl && !refreshOnlyFilters) || container.querySelector('[data-manual-search-button]')) {
                return;
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn primary app-filter-search';
            button.dataset.manualSearchButton = 'true';
            button.textContent = 'Tìm kiếm';
            button.addEventListener('click', () => triggerContainerSearch(container));

            const nextButton = refreshButton || container.querySelector('button');
            if (nextButton) {
                nextButton.insertAdjacentElement('beforebegin', button);
            } else {
                container.appendChild(button);
            }
            container.dataset.manualSearchReady = 'true';
        });
    }

    function suppressAutoFilter(event) {
        if (manualSearchDispatching) {
            return;
        }

        if (!isFilterControl(event.target)) {
            return;
        }

        const container = event.target.closest('[data-manual-search-ready="true"]');
        if (container) {
            container.__vtmsDirtyControl = event.target;
        }

        event.stopImmediatePropagation();
    }

    document.addEventListener('input', suppressAutoFilter, true);
    document.addEventListener('change', suppressAutoFilter, true);
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || !isFilterControl(event.target)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        const container = event.target.closest('[data-manual-search-ready="true"]');
        if (container) {
            triggerContainerSearch(container);
        }
    }, true);

    installManualSearchButtons();
})();
