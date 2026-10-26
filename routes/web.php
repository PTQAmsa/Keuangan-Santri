<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BisyarahController;
use App\Http\Controllers\Admin\BookClosingController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\OtherIncomeController;
use App\Http\Controllers\Admin\QuickSearchController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReceiptController;
use App\Http\Middleware\EnsurePortalPinIsChanged;
use Illuminate\Support\Facades\Route;

// Halaman Beranda Utama
Route::get('/', function () {
    return view('welcome');
});

// Portal Wali Santri
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'index'])->name('login');
    Route::post('/login', [PortalController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
    Route::get('/lupa-pin', [PortalController::class, 'forgotPin'])->name('forgot-pin');
    Route::post('/lupa-pin', [PortalController::class, 'requestPinReset'])->middleware('throttle:5,1')->name('forgot-pin.post');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])
        ->middleware(EnsurePortalPinIsChanged::class)
        ->name('dashboard');
    Route::get('/profil', [PortalController::class, 'profile'])
        ->middleware(EnsurePortalPinIsChanged::class)
        ->name('profile');
    Route::get('/kartu-keuangan', [PortalController::class, 'card'])
        ->middleware(EnsurePortalPinIsChanged::class)
        ->name('card');
    Route::get('/kartu-keuangan/tagihan/pdf', [PortalController::class, 'cardInvoicesPdf'])
        ->middleware(EnsurePortalPinIsChanged::class)
        ->name('card.invoices.pdf');
    Route::get('/kartu-keuangan/pembayaran/pdf', [PortalController::class, 'cardPaymentsPdf'])
        ->middleware(EnsurePortalPinIsChanged::class)
        ->name('card.payments.pdf');
    Route::get('/ganti-pin', [PortalController::class, 'showChangePin'])->name('change-pin');
    Route::post('/ganti-pin', [PortalController::class, 'changePin'])->name('change-pin.post');
    Route::get('/logout', [PortalController::class, 'logout'])->name('logout');
});

// Kuitansi Digital (Unduh PDF)
Route::prefix('kuitansi')->name('receipt.')->group(function () {
    Route::get('/{id}/download', [ReceiptController::class, 'download'])->name('download');
});

// Admin Kasir & Bendahara Pesantren
Route::prefix('admin')->name('admin.')->group(function () {
    // Autentikasi Pengurus (Bendahara, Pengasuh, Administrator)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.post');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Dapat diakses semua peran internal — Pengasuh bersifat hanya melihat.
        Route::get('/', [CashierController::class, 'index'])->name('dashboard');

        // Pencarian cepat dari kotak cari di header (santri, kuitansi, guru).
        Route::get('/cari', QuickSearchController::class)->name('search');

        // Modul Laporan Keuangan & Rekap Kas
        Route::prefix('laporan')->name('reports.')->group(function () {
            Route::get('/kas', [ReportController::class, 'cashInflow'])->name('cash');
            Route::get('/kas/csv', [ReportController::class, 'exportCashInflowCsv'])->name('cash.csv');
            Route::get('/kas/pdf', [ReportController::class, 'exportCashInflowPdf'])->name('cash.pdf');

            Route::get('/arus-kas', [ReportController::class, 'cashFlow'])->name('cash-flow');

            Route::get('/tunggakan', [ReportController::class, 'arrears'])->name('arrears');
            Route::get('/tunggakan/csv', [ReportController::class, 'exportArrearsCsv'])->name('arrears.csv');
            Route::get('/tunggakan/pdf', [ReportController::class, 'exportArrearsPdf'])->name('arrears.pdf');

            // Tutup buku bulanan: snapshot laporan + kunci transaksi kas.
            Route::get('/tutup-buku', [BookClosingController::class, 'index'])->name('closing');
            Route::post('/tutup-buku', [BookClosingController::class, 'store'])
                ->middleware('role:admin,bendahara')
                ->name('closing.store');
            Route::patch('/tutup-buku/{bookClosing}/buka', [BookClosingController::class, 'reopen'])
                ->middleware('role:admin')
                ->name('closing.reopen');
        });

        // Log Aktivitas Perubahan Data: khusus Administrator.
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])
            ->middleware('role:admin')
            ->name('logs');

        // Akun pengurus (kelola akun: khusus Administrator).
        Route::middleware('role:admin')->group(function () {
            Route::get('/akun', [AccountController::class, 'index'])->name('accounts');
            Route::post('/akun', [AccountController::class, 'store'])->name('accounts.store');
            Route::patch('/akun/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');
            Route::patch('/akun/{user}/status', [AccountController::class, 'toggleStatus'])->name('accounts.status');
        });

        // Ganti sandi milik sendiri (semua peran).
        Route::get('/ganti-sandi', [AccountController::class, 'showChangePassword'])->name('password.edit');
        Route::patch('/ganti-sandi', [AccountController::class, 'updatePassword'])->name('password.update');

        // Hanya Administrator & Bendahara yang boleh mencatat transaksi keuangan.
        Route::middleware('role:admin,bendahara')->group(function () {
            Route::get('/kasir', [CashierController::class, 'createPayment'])->name('cashier');
            Route::post('/kasir', [CashierController::class, 'storePayment'])->name('cashier.store');

            // Kas masuk di luar pembayaran santri: donasi, infaq, unit usaha, dsb.
            Route::get('/kas-masuk/lainnya', [OtherIncomeController::class, 'index'])->name('incomes');
            Route::post('/kas-masuk/lainnya', [OtherIncomeController::class, 'store'])->name('incomes.store');
            Route::patch('/kas-masuk/lainnya/{income}/batal', [OtherIncomeController::class, 'void'])->name('incomes.void');
            Route::post('/generate-spp', [CashierController::class, 'generateMonthlySPP'])->name('generate-spp');
            Route::get('/tagihan', [CashierController::class, 'invoices'])->name('invoices');
            Route::post('/tagihan', [CashierController::class, 'storeInvoice'])->name('invoices.store');

            // Data induk guru/pengajar. Cukup diisi sekali.
            Route::get('/guru', [TeacherController::class, 'index'])->name('teachers');
            Route::post('/guru', [TeacherController::class, 'store'])->name('teachers.store');

            // Bisyarah bulanan: pilih guru yang sudah terdaftar, isi nominal & metode.
            Route::get('/guru/bisyarah', [BisyarahController::class, 'index'])->name('bisyarah');
            Route::post('/guru/bisyarah', [BisyarahController::class, 'store'])->name('bisyarah.store');
            Route::patch('/guru/bisyarah/{bisyarah}', [BisyarahController::class, 'update'])->name('bisyarah.update');
            Route::delete('/guru/bisyarah/{bisyarah}', [BisyarahController::class, 'destroy'])->name('bisyarah.destroy');

            Route::patch('/guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
            Route::patch('/guru/{teacher}/status', [TeacherController::class, 'toggleStatus'])->name('teachers.toggle');

            // Data santri: santri masuk (tambah) & santri keluar (nonaktifkan).
            Route::get('/santri', [StudentController::class, 'index'])->name('students');
            Route::get('/santri/{student}/kartu', [StudentController::class, 'card'])->name('students.card');
            Route::get('/santri/{student}/kartu/tagihan/pdf', [StudentController::class, 'cardInvoicesPdf'])->name('students.card.invoices.pdf');
            Route::get('/santri/{student}/kartu/pembayaran/pdf', [StudentController::class, 'cardPaymentsPdf'])->name('students.card.payments.pdf');
            Route::post('/santri', [StudentController::class, 'store'])->name('students.store');
            Route::patch('/santri/{student}/status', [StudentController::class, 'toggleStatus'])->name('students.toggle');
            Route::post('/santri/{student}/reset-pin', [StudentController::class, 'resetPin'])->name('students.reset-pin');

            // Kas keluar: pengeluaran operasional pesantren.
            Route::get('/kas-keluar', [ExpenseController::class, 'index'])->name('expenses');
            Route::post('/kas-keluar', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::patch('/kas-keluar/{expense}/batal', [ExpenseController::class, 'void'])->name('expenses.void');

            // Perbaikan & pembatalan transaksi keuangan.
            Route::get('/transaksi', [TransactionController::class, 'index'])->name('transactions');
            Route::patch('/transaksi/{payment}/void', [TransactionController::class, 'voidPayment'])->name('transactions.void');
            Route::patch('/transaksi/{payment}', [TransactionController::class, 'updatePayment'])->name('transactions.update');
            Route::patch('/tagihan/{invoice}', [TransactionController::class, 'updateInvoice'])->name('invoices.update');
            Route::patch('/tagihan/{invoice}/batal', [TransactionController::class, 'cancelInvoice'])->name('invoices.cancel');
            Route::delete('/tagihan/{invoice}', [TransactionController::class, 'destroyInvoice'])->name('invoices.destroy');
        });
    });
});
