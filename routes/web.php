<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StudentController;
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
    Route::get('/kartu-keuangan', [PortalController::class, 'card'])
        ->middleware(EnsurePortalPinIsChanged::class)
        ->name('card');
    Route::get('/kartu-keuangan/pdf', [PortalController::class, 'cardPdf'])
        ->middleware(EnsurePortalPinIsChanged::class)
        ->name('card.pdf');
    Route::get('/ganti-pin', [PortalController::class, 'showChangePin'])->name('change-pin');
    Route::post('/ganti-pin', [PortalController::class, 'changePin'])->name('change-pin.post');
    Route::get('/logout', [PortalController::class, 'logout'])->name('logout');
});

// Kuitansi Digital (Unduh PDF & Verifikasi Publik QR Code)
Route::prefix('kuitansi')->name('receipt.')->group(function () {
    Route::get('/{id}/download', [ReceiptController::class, 'download'])->name('download');
    Route::get('/verifikasi/{token}', [ReceiptController::class, 'verify'])->name('verify');
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

        // Modul Laporan Keuangan & Rekap Kas
        Route::prefix('laporan')->name('reports.')->group(function () {
            Route::get('/kas', [ReportController::class, 'cashInflow'])->name('cash');
            Route::get('/kas/csv', [ReportController::class, 'exportCashInflowCsv'])->name('cash.csv');
            Route::get('/kas/pdf', [ReportController::class, 'exportCashInflowPdf'])->name('cash.pdf');

            Route::get('/tunggakan', [ReportController::class, 'arrears'])->name('arrears');
            Route::get('/tunggakan/csv', [ReportController::class, 'exportArrearsCsv'])->name('arrears.csv');
            Route::get('/tunggakan/pdf', [ReportController::class, 'exportArrearsPdf'])->name('arrears.pdf');
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
            Route::post('/generate-spp', [CashierController::class, 'generateMonthlySPP'])->name('generate-spp');
            Route::get('/tagihan', [CashierController::class, 'invoices'])->name('invoices');
            Route::post('/tagihan', [CashierController::class, 'storeInvoice'])->name('invoices.store');

            // Data santri: santri masuk (tambah) & santri keluar (nonaktifkan).
            Route::get('/santri', [StudentController::class, 'index'])->name('students');
            Route::get('/santri/{student}/kartu', [StudentController::class, 'card'])->name('students.card');
            Route::get('/santri/{student}/kartu/pdf', [StudentController::class, 'cardPdf'])->name('students.card.pdf');
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
