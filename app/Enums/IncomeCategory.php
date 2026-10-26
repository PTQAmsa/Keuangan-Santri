<?php

namespace App\Enums;

enum IncomeCategory: string
{
    case Donasi = 'donasi';
    case Infaq = 'infaq';
    case Hibah = 'hibah';
    case UnitUsaha = 'unit_usaha';
    case Sewa = 'sewa';
    case Lainnya = 'lainnya';

    /**
     * Label kategori pemasukan untuk antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Donasi => 'Donasi / Sumbangan',
            self::Infaq => 'Infaq & Sedekah',
            self::Hibah => 'Hibah / Bantuan',
            self::UnitUsaha => 'Unit Usaha / Kantin',
            self::Sewa => 'Sewa Aset',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Kelas badge Tailwind untuk kategori ini.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Donasi => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::Infaq => 'bg-teal-50 text-teal-700 border border-teal-200',
            self::Hibah => 'bg-blue-50 text-blue-700 border border-blue-200',
            self::UnitUsaha => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::Sewa => 'bg-cyan-50 text-cyan-700 border border-cyan-200',
            self::Lainnya => 'bg-gray-100 text-gray-700 border border-gray-200',
        };
    }
}
