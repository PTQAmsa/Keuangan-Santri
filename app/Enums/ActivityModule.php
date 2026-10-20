<?php

namespace App\Enums;

enum ActivityModule: string
{
    case Invoice = 'invoice';
    case Payment = 'payment';
    case Expense = 'expense';
    case Student = 'student';
    case Auth = 'auth';

    /**
     * Label modul yang ditampilkan pada halaman log aktivitas.
     */
    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Tagihan',
            self::Payment => 'Pembayaran',
            self::Expense => 'Kas Keluar',
            self::Student => 'Data Santri',
            self::Auth => 'Akses Panel',
        };
    }

    /**
     * Kelas badge Tailwind untuk modul ini.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Invoice => 'bg-brand-50 text-brand-800',
            self::Payment => 'bg-emerald-50 text-emerald-700',
            self::Expense => 'bg-rose-50 text-rose-700',
            self::Student => 'bg-amber-50 text-amber-700',
            self::Auth => 'bg-indigo-50 text-indigo-700',
        };
    }
}
