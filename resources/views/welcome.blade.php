<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Keuangan & Tagihan Santri - PTQ Amsa001</title>
    <meta name="description" content="Sistem Keuangan & Tagihan Santri Terpadu Pesantren Tadabbur Al-Qur'an Amsa001 Gadog: SPP bulanan, wakaf pembangunan, kuitansi digital PDF, dan notifikasi WhatsApp.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" type="image/png" sizes="64x64" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <!-- Tailwind CSS v3 (di-host lokal agar demo tetap berjalan tanpa internet) -->
    <script src="{{ asset('vendor/tailwind/tailwind.min.js') }}"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
                        gold: { 400: '#facc15', 500: '#eab308', 600: '#ca8a04' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">

    <!-- 1. Top Navigation Bar -->
    <header id="site-header" class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur transition-shadow">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="h-10 w-10 rounded-full bg-white object-contain p-0.5 shadow ring-1 ring-slate-200">
                <span class="leading-tight">
                    <span class="block text-sm font-bold tracking-tight text-slate-900 sm:text-base">Pesantren Tadabbur Al-Qur'an Amsa001</span>
                    <span class="hidden text-[11px] text-slate-500 sm:block">Sistem Keuangan & Tagihan Santri</span>
                </span>
            </a>

            <nav class="flex items-center gap-2" aria-label="Navigasi utama">
                <a href="{{ route('admin.login') }}"
                   class="hidden items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 sm:inline-flex">
                    <i class="fa-solid fa-lock text-xs"></i> Login Pengurus / Bendahara
                </a>
                <a href="{{ route('portal.login') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                    <i class="fa-solid fa-user-shield text-xs"></i> Masuk Portal Wali Santri
                </a>
                <a href="{{ route('admin.login') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-700 transition hover:bg-slate-100 sm:hidden" aria-label="Login Pengurus / Bendahara" title="Login Pengurus / Bendahara">
                    <i class="fa-solid fa-lock text-xs"></i>
                </a>
            </nav>
        </div>
    </header>

    <main>
        <!-- 2. Full-Width Hero Section -->
        <section class="relative overflow-hidden bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 px-6 py-16 text-white sm:py-20">
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]" aria-hidden="true">
                <i class="fa-solid fa-coins absolute -right-10 -bottom-16 text-[320px]"></i>
                <i class="fa-solid fa-mosque absolute -left-10 -top-10 text-[220px]"></i>
            </div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-2">
                <!-- Kolom Kiri -->
                <div class="space-y-6">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-400/30 bg-amber-400/10 px-3.5 py-1.5 text-xs font-semibold text-amber-300">
                        <i class="fa-solid fa-location-dot"></i> PTQ Amsa001 Gadog - Megamendung, Bogor
                    </div>
                    <h1 class="text-3xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                        Sistem Keuangan &amp; Tagihan Santri Terpadu
                    </h1>
                    <p class="max-w-xl text-sm leading-relaxed text-blue-100/90 sm:text-base">
                        Pencatatan pembayaran SPP bulanan, fasilitas asrama, dan wakaf pembangunan bertahap dalam satu sistem.
                        Setiap pembayaran menerbitkan kuitansi digital resmi dan dikirim otomatis ke WhatsApp wali santri.
                    </p>
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-blue-100/80">
                        <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-400"></i> Kuitansi PDF ber-kop resmi</span>
                        <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-400"></i> Cek tagihan 24 jam</span>
                        <span class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-400"></i> Pengingat WhatsApp</span>
                    </div>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="#skema-biaya" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/20">
                            <i class="fa-solid fa-list-ol"></i> Lihat Skema Biaya
                        </a>
                        <a href="{{ route('admin.login') }}" class="inline-flex items-center gap-2 px-2 py-3 text-sm font-medium text-blue-200 transition hover:text-white">
                            Pengurus / Bendahara? Masuk di sini <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- Kolom Kanan: Quick Action Wali Santri -->
                <div class="lg:justify-self-end lg:w-full lg:max-w-md">
                    <div class="rounded-2xl border border-white/10 bg-white p-6 text-slate-800 shadow-2xl shadow-blue-950/50 sm:p-7">
                        <div class="mb-5 flex items-center gap-3">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                <i class="fa-solid fa-magnifying-glass-dollar text-lg"></i>
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-slate-900">Cek Tagihan Santri</h2>
                                <p class="text-xs text-slate-500">Khusus wali santri PTQ Amsa001</p>
                            </div>
                        </div>

                        <form action="{{ route('portal.login') }}" method="GET" class="space-y-3" id="quick-check-form">
                            <label for="quick-check-input" class="block text-xs font-semibold text-slate-700">Masukkan NIS / No. WhatsApp Santri</label>
                            <div class="relative">
                                <i class="fa-solid fa-id-card pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                                <input type="text" name="q" id="quick-check-input" required autocomplete="off" inputmode="text"
                                       placeholder="Contoh: 2026001 atau 0812xxxxxxx"
                                       class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                            </div>
                            <p id="quick-check-hint" class="text-[11px] text-slate-500">
                                Anda akan diarahkan ke Portal Wali untuk memasukkan PIN.
                            </p>
                            <button type="submit"
                                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-amber-600">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i> Cek Tagihan / Masuk
                            </button>
                        </form>

                        <div class="mt-5 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center text-[11px] text-slate-500">
                            <div><i class="fa-solid fa-file-invoice-dollar mb-1 block text-base text-blue-600"></i>Rincian tagihan</div>
                            <div><i class="fa-solid fa-file-pdf mb-1 block text-base text-rose-500"></i>Unduh kuitansi</div>
                            <div><i class="fa-solid fa-clock-rotate-left mb-1 block text-base text-emerald-600"></i>Riwayat bayar</div>
                        </div>

                        <p class="mt-4 text-center text-[11px] text-slate-400">
                            Lupa PIN? <a href="{{ route('portal.forgot-pin') }}" class="font-semibold text-blue-700 hover:underline">Ajukan reset PIN</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Section Skema Pembiayaan Resmi -->
        <section id="skema-biaya" class="scroll-mt-20 bg-slate-50 py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-8 max-w-2xl space-y-2 text-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-blue-700">
                        <i class="fa-solid fa-scroll"></i> Ketetapan resmi
                    </span>
                    <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">Skema Pembiayaan Resmi PTQ Amsa001</h2>
                    <p class="text-sm text-slate-500">Berdasarkan ketetapan Pesantren Tadabbur Al-Qur'an Amsa001 Gadog. Santri yatim/dhuafa dapat mengajukan beasiswa.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Card 1: Pembiayaan Awal -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                <i class="fa-solid fa-door-open text-lg"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Pembiayaan Awal (Santri Baru)</h3>
                                <p class="text-xs text-slate-500">Dibayarkan saat pendaftaran &amp; masuk asrama</p>
                            </div>
                        </div>
                        <dl class="divide-y divide-slate-100 text-sm">
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">1. Biaya Formulir Pendaftaran</dt>
                                <dd class="font-bold text-slate-900">Rp 200.000</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">2. Fasilitas Kamar <span class="block text-[11px] text-slate-400">Lemari, kasur, bantal, selimut &amp; sprei</span></dt>
                                <dd class="font-bold text-slate-900">Rp 1.000.000</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">3. Wakaf Pembangunan <span class="block text-[11px] font-medium text-amber-600">Bisa dicicil 2x @ Rp 2.500.000</span></dt>
                                <dd class="font-bold text-slate-900">Rp 5.000.000</dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 text-sm">
                            <span class="font-semibold text-slate-700">Total biaya awal</span>
                            <span class="font-extrabold text-slate-900">Rp 6.200.000</span>
                        </div>
                    </div>

                    <!-- Card 2: Iuran Rutin Bulanan -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                <i class="fa-solid fa-calendar-check text-lg"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Iuran Rutin Bulanan</h3>
                                <p class="text-xs text-slate-500">Jatuh tempo setiap tanggal 10 tiap bulan</p>
                            </div>
                        </div>
                        <dl class="divide-y divide-slate-100 text-sm">
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">1. SPP Pendidikan</dt>
                                <dd class="font-bold text-slate-900">Rp 600.000</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">2. Dapur / Konsumsi <span class="block text-[11px] text-slate-400">Makan 3x sehari</span></dt>
                                <dd class="font-bold text-slate-900">Rp 550.000</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-slate-600">3. Fasilitas Laundry Pakaian</dt>
                                <dd class="font-bold text-slate-900">Rp 200.000</dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex items-center justify-between rounded-lg bg-emerald-600 px-4 py-3 text-white shadow-sm">
                            <span class="text-sm font-semibold">Total Paket Iuran Bulanan</span>
                            <span class="inline-flex items-center gap-2">
                                <span class="text-lg font-extrabold">Rp 1.350.000</span>
                                <span class="rounded-full bg-white/20 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide">/ bulan</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Section Keunggulan Layanan -->
        <section class="py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-8 max-w-2xl space-y-2 text-center">
                    <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">Keunggulan Layanan Keuangan</h2>
                    <p class="text-sm text-slate-500">Dirancang agar wali santri tenang dan pencatatan pesantren tertib.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    <article class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg">
                        <span class="inline-flex rounded-lg bg-blue-50 p-3 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                            <i class="fa-solid fa-handshake-angle text-xl"></i>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">Skema Cicilan &amp; Beasiswa</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Wakaf pembangunan dapat dicicil dua tahap. Santri yatim/dhuafa mendapat potongan beasiswa 50% dan bebas wakaf secara otomatis di tagihan.
                        </p>
                    </article>

                    <article class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg">
                        <span class="inline-flex rounded-lg bg-blue-50 p-3 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                            <i class="fa-solid fa-file-invoice text-xl"></i>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">Kuitansi Digital PDF</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Setiap pembayaran menerbitkan kuitansi PDF ber-kop resmi PTQ Amsa001 yang bisa diunduh kapan saja dari Portal Wali sebagai bukti sah.
                        </p>
                    </article>

                    <article class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg">
                        <span class="inline-flex rounded-lg bg-blue-50 p-3 text-blue-600 transition group-hover:bg-blue-600 group-hover:text-white">
                            <i class="fa-brands fa-whatsapp text-xl"></i>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-slate-900">Notifikasi WhatsApp Gateway</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Konfirmasi kuitansi dan pengingat jatuh tempo terkirim otomatis ke WhatsApp wali santri, tanpa perlu dihubungi satu per satu.
                        </p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <!-- 5. Footer -->
    <footer class="border-t border-slate-200 bg-slate-900 text-slate-300">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3 lg:px-8">
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo-pesantren.png') }}" alt="Logo PTQ Amsa001" class="h-10 w-10 rounded-full bg-white object-contain p-0.5">
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-white">PTQ Amsa001</p>
                        <p class="text-[11px] text-slate-400">Pesantren Tadabbur Al-Qur'an Amsa001</p>
                    </div>
                </div>
                <p class="text-xs leading-relaxed text-slate-400">
                    Sistem Keuangan &amp; Tagihan Santri Terpadu untuk pencatatan yang tertib dan transparan bagi wali santri.
                </p>
            </div>

            <div class="space-y-2 text-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Alamat Pesantren</p>
                <p class="flex items-start gap-2 leading-relaxed">
                    <i class="fa-solid fa-location-dot mt-1 text-amber-400"></i>
                    <span>Jl. Cikopo Selatan, Gg. Yulias, Sukabirus, Desa Gadog, Kec. Megamendung, Kab. Bogor, Jawa Barat 16770</span>
                </p>
            </div>

            <div class="space-y-2 text-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Layanan Keuangan</p>
                <a href="https://wa.me/6285692253516" target="_blank" rel="noopener" class="flex items-center gap-2 transition hover:text-white">
                    <i class="fa-brands fa-whatsapp text-emerald-400"></i> +62 856-9225-3516
                </a>
                <a href="https://www.ptqamsa.id" target="_blank" rel="noopener" class="flex items-center gap-2 transition hover:text-white">
                    <i class="fa-solid fa-globe text-blue-400"></i> www.ptqamsa.id
                </a>
                <a href="{{ route('admin.login') }}" class="flex items-center gap-2 text-slate-400 transition hover:text-white">
                    <i class="fa-solid fa-lock"></i> Login Pengurus / Bendahara
                </a>
            </div>
        </div>
        <div class="border-t border-slate-800 px-4 py-4 text-center text-[11px] text-slate-500">
            &copy; {{ now()->year }} Pesantren Tadabbur Al-Qur'an Amsa001 Gadog. Seluruh hak cipta dilindungi.
        </div>
    </footer>

    <script>
        (function () {
            'use strict';

            // Bayangan navbar muncul setelah halaman digulir.
            var header = document.getElementById('site-header');
            function syncHeaderShadow() {
                if (!header) return;
                header.classList.toggle('shadow-md', window.scrollY > 8);
            }
            window.addEventListener('scroll', syncHeaderShadow, { passive: true });
            syncHeaderShadow();

            // Petunjuk isian cepat: bedakan NIS dan nomor WhatsApp secara langsung.
            var input = document.getElementById('quick-check-input');
            var hint = document.getElementById('quick-check-hint');
            if (input && hint) {
                input.addEventListener('input', function () {
                    var value = input.value.trim();
                    if (value === '') {
                        hint.textContent = 'Anda akan diarahkan ke Portal Wali untuk memasukkan PIN.';
                    } else if (/^\+?\d{9,}$/.test(value)) {
                        hint.textContent = 'Terbaca sebagai nomor WhatsApp wali. Di portal, lengkapi NIS santri lalu masuk.';
                    } else {
                        hint.textContent = 'Terbaca sebagai NIS. Di portal, masukkan PIN atau nomor WhatsApp wali.';
                    }
                });
            }
        })();
    </script>
</body>
</html>
