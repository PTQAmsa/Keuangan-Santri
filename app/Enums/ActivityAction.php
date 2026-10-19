<?php

namespace App\Enums;

enum ActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Cancelled = 'cancelled';
    case Deleted = 'deleted';
    case Login = 'login';

    /**
     * Label aksi yang ditampilkan pada halaman log aktivitas.
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Dibuat',
            self::Updated => 'Diubah',
            self::Cancelled => 'Dibatalkan',
            self::Deleted => 'Dihapus',
            self::Login => 'Login',
        };
    }

    /**
     * Kelas badge Tailwind untuk aksi ini.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Created => 'bg-emerald-100 text-emerald-800',
            self::Updated => 'bg-amber-100 text-amber-800',
            self::Cancelled => 'bg-rose-100 text-rose-800',
            self::Deleted => 'bg-rose-100 text-rose-800',
            self::Login => 'bg-indigo-100 text-indigo-800',
        };
    }
}
