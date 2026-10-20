<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Operasional = 'operasional';
    case Pemeliharaan = 'pemeliharaan';
    case Gaji = 'gaji';
    case Transport = 'transport';
    case Konsumsi = 'konsumsi';
    case Lainnya = 'lainnya';

    /**
     * Label kategori pengeluaran untuk antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Operasional => 'Operasional Harian',
            self::Pemeliharaan => 'Pemeliharaan & Perbaikan',
            self::Gaji => 'Gaji / Insentif',
            self::Transport => 'Transportasi',
            self::Konsumsi => 'Konsumsi',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Kelas badge Tailwind untuk kategori ini.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Operasional => 'bg-blue-50 text-blue-700 border border-blue-200',
            self::Pemeliharaan => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::Gaji => 'bg-purple-50 text-purple-700 border border-purple-200',
            self::Transport => 'bg-cyan-50 text-cyan-700 border border-cyan-200',
            self::Konsumsi => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::Lainnya => 'bg-gray-100 text-gray-700 border border-gray-200',
        };
    }
}
