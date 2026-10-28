<?php

namespace App\Enums;

/**
 * Saluran transfer untuk dana keluar: bank atau dompet digital.
 *
 * Nilai yang disimpan adalah nama salurannya sendiri, sehingga data lama
 * yang sudah menyimpan nama bank bebas tetap bisa dikenali. Pilihan
 * Lainnya mewajibkan pengguna menuliskan nama salurannya sendiri.
 */
enum TransferChannel: string
{
    case Bsi = 'BSI';
    case Bca = 'BCA';
    case Bri = 'BRI';
    case Bni = 'BNI';
    case Mandiri = 'Mandiri';
    case Btn = 'BTN';
    case Bjb = 'BJB';
    case Muamalat = 'Bank Muamalat';
    case CimbNiaga = 'CIMB Niaga';
    case Permata = 'Permata';
    case Jenius = 'Jenius';
    case Jago = 'Bank Jago';
    case SeaBank = 'SeaBank';
    case Dana = 'DANA';
    case GoPay = 'GoPay';
    case Ovo = 'OVO';
    case ShopeePay = 'ShopeePay';
    case LinkAja = 'LinkAja';
    case Lainnya = 'Lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Bsi => 'BSI (Bank Syariah Indonesia)',
            self::Bca => 'BCA',
            self::Bri => 'BRI',
            self::Bni => 'BNI',
            self::Mandiri => 'Bank Mandiri',
            self::Btn => 'BTN',
            self::Bjb => 'BJB (Bank Jabar Banten)',
            self::Lainnya => 'Lainnya (tulis sendiri)',
            default => $this->value,
        };
    }

    public function isDigitalWallet(): bool
    {
        return in_array($this, [self::Dana, self::GoPay, self::Ovo, self::ShopeePay, self::LinkAja], true);
    }

    /**
     * @return list<self>
     */
    public static function banks(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $channel): bool => $channel !== self::Lainnya && ! $channel->isDigitalWallet()
        ));
    }

    /**
     * @return list<self>
     */
    public static function digitalWallets(): array
    {
        return array_values(array_filter(self::cases(), fn (self $channel): bool => $channel->isDigitalWallet()));
    }

    /**
     * Nama saluran yang disimpan: pilihan dari daftar, atau isian bebas
     * bila pengguna memilih Lainnya.
     */
    public static function resolveName(?string $channel, ?string $other): ?string
    {
        if ($channel === null || $channel === '') {
            return null;
        }

        if ($channel === self::Lainnya->value) {
            $other = trim((string) $other);

            return $other === '' ? null : $other;
        }

        return $channel;
    }

    /**
     * Pilihan daftar yang mewakili nama saluran tersimpan. Nama yang tidak
     * ada di daftar diperlakukan sebagai Lainnya.
     */
    public static function optionFor(?string $name): ?self
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        return self::tryFrom(trim($name)) ?? self::Lainnya;
    }

    /**
     * Isian bebas yang perlu ditampilkan kembali untuk nama di luar daftar.
     */
    public static function otherFor(?string $name): string
    {
        return self::optionFor($name) === self::Lainnya ? trim((string) $name) : '';
    }

    /**
     * Teks metode untuk tabel dan log: "Transfer BSI", "Transfer DANA", "Tunai".
     */
    public static function methodLabel(string $paymentMethod, ?string $channelName): string
    {
        if ($paymentMethod === 'cash') {
            return 'Tunai';
        }

        return filled($channelName) ? 'Transfer '.$channelName : 'Transfer';
    }
}
