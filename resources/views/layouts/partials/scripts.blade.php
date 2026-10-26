<script>
(function () {
    'use strict';

    /* ---------------------------------------------------------------
     * 1. Sidebar: buka/tutup di layar kecil, ringkas/perluas di desktop.
     *    Status ringkas disimpan di localStorage agar konsisten antar halaman.
     * ------------------------------------------------------------- */
    var sidebar = document.getElementById('app-sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var shell = document.getElementById('app-shell');
    var COLLAPSED_KEY = 'ptq.sidebar.collapsed';
    var desktopQuery = window.matchMedia('(min-width: 1024px)');

    function isDesktop() { return desktopQuery.matches; }

    function setCollapsed(collapsed) {
        if (!sidebar || !shell) return;
        sidebar.classList.toggle('w-64', !collapsed);
        sidebar.classList.toggle('w-20', collapsed);
        sidebar.classList.toggle('is-collapsed', collapsed);
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        shell.classList.toggle('lg:pl-64', !collapsed);
        shell.classList.toggle('lg:pl-20', collapsed);

        sidebar.querySelectorAll('[data-sidebar-label]').forEach(function (el) {
            el.classList.toggle('hidden', collapsed);
        });
        sidebar.querySelectorAll('[data-sidebar-link]').forEach(function (el) {
            el.classList.toggle('justify-center', collapsed);
        });
        var collapseBtn = sidebar.querySelector('[data-sidebar-collapse]');
        if (collapseBtn) collapseBtn.classList.toggle('justify-center', collapsed);
        var icon = sidebar.querySelector('[data-sidebar-collapse-icon]');
        if (icon) icon.classList.toggle('rotate-180', collapsed);

        try { localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0'); } catch (e) {}
    }

    function openMobile() {
        if (!sidebar) return;
        sidebar.classList.remove('-translate-x-full');
        if (backdrop) backdrop.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeMobile() {
        if (!sidebar) return;
        sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (isDesktop()) {
                setCollapsed(!sidebar.classList.contains('is-collapsed'));
            } else if (sidebar.classList.contains('-translate-x-full')) {
                openMobile();
            } else {
                closeMobile();
            }
        });
    });

    document.querySelectorAll('[data-sidebar-close]').forEach(function (btn) {
        btn.addEventListener('click', closeMobile);
    });

    document.querySelectorAll('[data-sidebar-collapse]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setCollapsed(!sidebar.classList.contains('is-collapsed'));
        });
    });

    if (backdrop) backdrop.addEventListener('click', closeMobile);

    // Terapkan status tersimpan saat halaman dimuat (hanya desktop).
    try {
        if (isDesktop() && localStorage.getItem(COLLAPSED_KEY) === '1') setCollapsed(true);
    } catch (e) {}

    desktopQuery.addEventListener('change', function (event) {
        if (event.matches) {
            closeMobile();
            try { setCollapsed(localStorage.getItem(COLLAPSED_KEY) === '1'); } catch (e) {}
        } else {
            setCollapsed(false);
            closeMobile();
        }
    });

    /* ---------------------------------------------------------------
     * 1b. Submenu sidebar (dropdown per menu utama).
     *     Saat sidebar diringkas, klik menu utama langsung membuka
     *     halaman pertamanya agar tidak ada daftar yang terpotong.
     * ------------------------------------------------------------- */
    document.querySelectorAll('[data-submenu]').forEach(function (group) {
        var toggle = group.querySelector('[data-submenu-toggle]');
        var list = group.querySelector('[data-submenu-list]');
        var chevron = group.querySelector('[data-submenu-chevron]');
        if (!toggle || !list) return;

        toggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('is-collapsed')) {
                var href = toggle.getAttribute('data-submenu-href');
                if (href) window.location.href = href;
                return;
            }

            var willOpen = list.classList.contains('hidden');

            // Satu submenu terbuka pada satu waktu agar daftar tetap ringkas.
            document.querySelectorAll('[data-submenu]').forEach(function (other) {
                if (other === group) return;
                var otherList = other.querySelector('[data-submenu-list]');
                var otherToggle = other.querySelector('[data-submenu-toggle]');
                var otherChevron = other.querySelector('[data-submenu-chevron]');
                if (otherList) otherList.classList.add('hidden');
                if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
                if (otherChevron) otherChevron.classList.remove('rotate-180');
                other.classList.remove('is-open');
            });

            list.classList.toggle('hidden', !willOpen);
            group.classList.toggle('is-open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (chevron) chevron.classList.toggle('rotate-180', willOpen);
        });
    });

    /* ---------------------------------------------------------------
     * 1c. Mode lihat-saja (Pengasuh): kunci semua isian pada form yang
     *     mengubah data. Tombol simpan/ubah sudah disembunyikan lewat CSS;
     *     ini lapisan kedua, dan server tetap menolak bila ditembus.
     * ------------------------------------------------------------- */
    if (document.body.classList.contains('readonly-mode')) {
        document.querySelectorAll('form').forEach(function (form) {
            var method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET' || form.hasAttribute('data-allow-readonly')) return;
            form.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                if (el.type === 'hidden') return;
                el.disabled = true;
            });
            form.addEventListener('submit', function (event) { event.preventDefault(); });
        });
    }

    /* ---------------------------------------------------------------
     * 2. Dropdown generik (notifikasi, profil): toggle class hidden,
     *    tutup saat klik di luar atau tekan Escape.
     * ------------------------------------------------------------- */
    var dropdowns = Array.prototype.slice.call(document.querySelectorAll('[data-dropdown]'));

    function closeAllDropdowns(except) {
        dropdowns.forEach(function (dd) {
            if (dd === except) return;
            var menu = dd.querySelector('[data-dropdown-menu]');
            var toggle = dd.querySelector('[data-dropdown-toggle]');
            if (menu) menu.classList.add('hidden');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }

    dropdowns.forEach(function (dd) {
        var toggle = dd.querySelector('[data-dropdown-toggle]');
        var menu = dd.querySelector('[data-dropdown-menu]');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = menu.classList.contains('hidden');
            closeAllDropdowns(dd);
            hideSearchResults();
            menu.classList.toggle('hidden', !willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-dropdown]')) closeAllDropdowns(null);
        if (!event.target.closest('#quick-search')) hideSearchResults();
    });

    /* ---------------------------------------------------------------
     * 3. Pencarian cepat: Ctrl+K / Cmd+K, debounce, hasil berkelompok.
     * ------------------------------------------------------------- */
    var searchInput = document.getElementById('quick-search-input');
    var searchBox = document.getElementById('quick-search-results');
    var searchTimer = null;
    var lastQuery = '';
    var activeIndex = -1;

    var isMac = /Mac|iPhone|iPad/.test(navigator.platform);
    var shortcutLabel = document.querySelector('[data-search-shortcut]');
    if (shortcutLabel && isMac) shortcutLabel.textContent = '⌘';

    function searchState(name) {
        if (!searchBox) return null;
        searchBox.querySelectorAll('[data-search-state]').forEach(function (el) {
            el.classList.toggle('hidden', el.getAttribute('data-search-state') !== name);
        });
        return searchBox.querySelector('[data-search-state="' + name + '"]');
    }

    function showSearchResults() {
        if (searchBox) searchBox.classList.remove('hidden');
    }

    function hideSearchResults() {
        if (searchBox) searchBox.classList.add('hidden');
        activeIndex = -1;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderResults(data) {
        var container = searchState('results');
        if (!container) return;
        container.innerHTML = '';

        if (!data.groups || data.groups.length === 0) {
            var emptyEl = searchState('empty');
            var termEl = emptyEl ? emptyEl.querySelector('[data-search-term]') : null;
            if (termEl) termEl.textContent = data.query || '';
            return;
        }

        data.groups.forEach(function (group) {
            var section = document.createElement('div');
            section.className = 'py-1.5';
            section.innerHTML =
                '<p class="flex items-center gap-2 px-4 pb-1 pt-2 text-[10px] font-bold uppercase tracking-widest text-slate-400">' +
                '<i class="fas ' + escapeHtml(group.icon) + '"></i>' + escapeHtml(group.label) + '</p>';

            group.items.forEach(function (item) {
                var actions = (item.actions || []).map(function (action) {
                    return '<a href="' + escapeHtml(action.url) + '" data-search-action ' +
                        'class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">' +
                        '<i class="fas ' + escapeHtml(action.icon) + '"></i>' + escapeHtml(action.label) + '</a>';
                }).join('');

                var row = document.createElement('a');
                row.href = item.url;
                row.setAttribute('data-search-item', '');
                row.className = 'flex items-start gap-3 px-4 py-2.5 transition hover:bg-slate-50 focus:bg-blue-50 focus:outline-none';
                row.innerHTML =
                    '<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500"><i class="fas ' + escapeHtml(group.icon) + '"></i></span>' +
                    '<span class="min-w-0 flex-1">' +
                        '<span class="block truncate text-sm font-semibold text-slate-800">' + escapeHtml(item.title) + '</span>' +
                        '<span class="block truncate text-xs text-slate-500">' + escapeHtml(item.subtitle) + '</span>' +
                        '<span class="mt-0.5 block text-[11px] font-semibold ' + escapeHtml(item.metaClass || 'text-slate-500') + '">' + escapeHtml(item.meta) + '</span>' +
                    '</span>' +
                    (actions ? '<span class="hidden shrink-0 items-center gap-1 sm:flex">' + actions + '</span>' : '');
                section.appendChild(row);
            });

            container.appendChild(section);
        });

        // Tombol aksi di dalam baris tidak boleh ikut membuka tautan baris.
        container.querySelectorAll('[data-search-action]').forEach(function (el) {
            el.addEventListener('click', function (event) { event.stopPropagation(); });
        });
    }

    function runSearch(query) {
        if (!searchInput) return;
        var url = searchInput.getAttribute('data-search-url') + '?q=' + encodeURIComponent(query);
        lastQuery = query;
        searchState('loading');
        showSearchResults();

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (data.query !== lastQuery) return; // hasil usang
                renderResults(data);
                if (data.groups && data.groups.length > 0) searchState('results');
            })
            .catch(function () { searchState('error'); });
    }

    function handleSearchInput() {
        var query = searchInput.value.trim();
        clearTimeout(searchTimer);
        activeIndex = -1;

        if (query.length < 2) {
            lastQuery = '';
            searchState('hint');
            showSearchResults();
            return;
        }

        searchTimer = setTimeout(function () { runSearch(query); }, 250);
    }

    if (searchInput && searchBox) {
        searchInput.addEventListener('focus', function () {
            closeAllDropdowns(null);
            handleSearchInput();
        });
        searchInput.addEventListener('input', handleSearchInput);

        searchInput.addEventListener('keydown', function (event) {
            var items = Array.prototype.slice.call(searchBox.querySelectorAll('[data-search-item]'));

            if (event.key === 'Escape') {
                hideSearchResults();
                searchInput.blur();
                return;
            }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                if (items.length === 0) return;
                event.preventDefault();
                activeIndex = event.key === 'ArrowDown'
                    ? (activeIndex + 1) % items.length
                    : (activeIndex - 1 + items.length) % items.length;
                items.forEach(function (el, i) { el.classList.toggle('bg-blue-50', i === activeIndex); });
                items[activeIndex].scrollIntoView({ block: 'nearest' });
                return;
            }
            if (event.key === 'Enter') {
                var target = activeIndex >= 0 ? items[activeIndex] : items[0];
                if (target) {
                    event.preventDefault();
                    window.location.href = target.href;
                }
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        var combo = (event.ctrlKey || event.metaKey) && !event.altKey && String(event.key).toLowerCase() === 'k';
        if (!combo || !searchInput) return;
        event.preventDefault();
        searchInput.focus();
        searchInput.select();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllDropdowns(null);
            if (!isDesktop()) closeMobile();
        }
    });
})();
</script>
