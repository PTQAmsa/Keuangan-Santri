{{-- Tombol kembali ke atas: muncul setelah digulir cukup jauh, biru, kuning saat ditekan. --}}
<button type="button" id="back-to-top" aria-label="Kembali ke atas" title="Kembali ke atas"
        class="pointer-events-none fixed bottom-5 right-5 z-40 flex h-12 w-12 translate-y-4 items-center justify-center rounded-full bg-blue-600 text-white opacity-0 shadow-lg shadow-blue-900/30 transition-all duration-200 hover:bg-blue-700 active:bg-amber-500 active:text-slate-900 sm:bottom-6 sm:right-6">
    <i class="fa-solid fa-arrow-up text-lg"></i>
</button>

<script>
(function () {
    'use strict';

    var backToTop = document.getElementById('back-to-top');
    if (!backToTop) return;

    function onScroll() {
        var y = window.scrollY || document.documentElement.scrollTop;
        var show = y > Math.max(240, window.innerHeight * 0.6);
        backToTop.classList.toggle('opacity-0', !show);
        backToTop.classList.toggle('translate-y-4', !show);
        backToTop.classList.toggle('pointer-events-none', !show);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    backToTop.addEventListener('click', function () {
        // Warna kuning dipertahankan sebentar sebagai umpan balik sentuhan di HP.
        backToTop.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        backToTop.classList.add('bg-amber-500', 'text-slate-900');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(function () {
            backToTop.classList.remove('bg-amber-500', 'text-slate-900');
            backToTop.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        }, 900);
    });
})();
</script>
