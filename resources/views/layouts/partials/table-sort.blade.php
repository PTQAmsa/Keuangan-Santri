{{-- Pengurutan sisi-klien untuk tabel ringkasan (dashboard, portal, kartu santri).
     Tandai <table data-sortable>; kolom dengan <th data-nosort> dilewati. --}}
<script>
(function () {
    'use strict';

    function cellValue(row, index) {
        var cell = row.children[index];
        if (!cell) return '';
        var raw = (cell.getAttribute('data-sort-value') || cell.textContent || '').trim();

        // Angka rupiah / tanggal dd/mm/yyyy dibandingkan sebagai angka agar urutannya benar.
        var money = raw.replace(/^Rp\s*/i, '').replace(/\./g, '').replace(/,/g, '.').replace(/\s.*$/, '');
        if (money !== '' && /^-?\d+(\.\d+)?$/.test(money)) return parseFloat(money);

        var date = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})/);
        if (date) return parseInt(date[3] + date[2] + date[1], 10);

        return raw.toLowerCase();
    }

    document.querySelectorAll('table[data-sortable]').forEach(function (table) {
        var headers = table.querySelectorAll('thead th');
        var tbody = table.querySelector('tbody');
        if (!tbody) return;

        headers.forEach(function (th, index) {
            if (th.hasAttribute('data-nosort') || th.textContent.trim() === '') return;

            th.classList.add('cursor-pointer', 'select-none', 'hover:text-brand-900');
            th.setAttribute('title', 'Urutkan ' + th.textContent.trim());
            if (!th.querySelector('[data-sort-icon]')) {
                var icon = document.createElement('i');
                icon.setAttribute('data-sort-icon', '');
                icon.className = 'fa-solid fa-sort ml-1 text-[10px] text-gray-300';
                th.appendChild(icon);
            }

            th.addEventListener('click', function () {
                var current = th.getAttribute('aria-sort');
                var direction = current === 'ascending' ? 'descending' : 'ascending';

                headers.forEach(function (other) {
                    other.removeAttribute('aria-sort');
                    var otherIcon = other.querySelector('[data-sort-icon]');
                    if (otherIcon) otherIcon.className = 'fa-solid fa-sort ml-1 text-[10px] text-gray-300';
                });

                th.setAttribute('aria-sort', direction);
                var activeIcon = th.querySelector('[data-sort-icon]');
                if (activeIcon) {
                    activeIcon.className = 'fa-solid ' + (direction === 'ascending' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') + ' ml-1 text-[10px] text-brand-900';
                }

                var rows = Array.prototype.slice.call(tbody.querySelectorAll(':scope > tr'))
                    .filter(function (row) { return row.children.length > 1; });

                rows.sort(function (a, b) {
                    var va = cellValue(a, index);
                    var vb = cellValue(b, index);
                    var result = (typeof va === 'number' && typeof vb === 'number')
                        ? va - vb
                        : String(va).localeCompare(String(vb), 'id');
                    return direction === 'ascending' ? result : -result;
                });

                rows.forEach(function (row) { tbody.appendChild(row); });
            });
        });
    });
})();
</script>
