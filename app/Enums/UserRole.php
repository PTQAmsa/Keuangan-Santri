<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Bendahara = 'bendahara';
    case Pengasuh = 'pengasuh';

    /**
     * Label peran yang ditampilkan pada antarmuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Bendahara => 'Bendahara',
            self::Pengasuh => 'Pengasuh',
        };
    }

    /**
     * Hanya Administrator & Bendahara yang boleh mencatat transaksi keuangan.
     * Pengasuh bersifat hanya melihat (read-only).
     */
    public function canManageFinances(): bool
    {
        return in_array($this, [self::Admin, self::Bendahara], true);
    }

    /**
     * Hanya Administrator yang boleh melihat log aktivitas sistem.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
