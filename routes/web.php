<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CashierController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

// Halaman Beranda Utama
Route::get('/', function () {
    return view('welcome');
});

// Portal Wali Santri
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'index'])->name('login');
    Route::post('/login', [PortalController::class, 'login'])->name('login.post');
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('dashboard');
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

        // Hanya Administrator & Bendahara yang boleh mencatat transaksi keuangan.
        Route::middleware('role:admin,bendahara')->group(function () {
            Route::get('/kasir', [CashierController::class, 'createPayment'])->name('cashier');
            Route::post('/kasir', [CashierController::class, 'storePayment'])->name('cashier.store');
            Route::post('/generate-spp', [CashierController::class, 'generateMonthlySPP'])->name('generate-spp');
            Route::get('/tagihan', [CashierController::class, 'invoices'])->name('invoices');
            Route::post('/tagihan', [CashierController::class, 'storeInvoice'])->name('invoices.store');
        });
    });
});
