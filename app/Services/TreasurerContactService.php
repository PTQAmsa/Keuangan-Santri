<?php

namespace App\Services;

/**
 * Kontak WhatsApp bendahara untuk tombol "Hubungi Bendahara" dan footer.
 *
 * Nomor diambil dari konfigurasi app.treasurer_contacts (berasal dari .env)
 * dengan format "Nama:nomor;Nama:nomor" agar nomor pribadi tidak ikut
 * tersimpan di kode sumber.
 */
class TreasurerContactService
{
    /**
     * @return list<array{name: string, phone: string, display: string}>
     */
    public static function all(): array
    {
        $contacts = [];

        foreach (explode(';', (string) config('app.treasurer_contacts', '')) as $entry) {
            if (! str_contains($entry, ':')) {
                continue;
            }

            [$name, $phone] = array_map('trim', explode(':', $entry, 2));
            $phone = WhatsAppService::normalizePhone($phone);

            if ($name === '' || $phone === '') {
                continue;
            }

            $contacts[] = [
                'name' => $name,
                'phone' => $phone,
                'display' => self::formatDisplay($phone),
            ];
        }

        return $contacts;
    }

    /**
     * Kontak utama (urutan pertama), atau null bila belum diatur.
     *
     * @return array{name: string, phone: string, display: string}|null
     */
    public static function primary(): ?array
    {
        return self::all()[0] ?? null;
    }

    /**
     * Tautan wa.me ke kontak utama, dengan pesan awal bila diberikan.
     * Null bila belum ada kontak yang diatur.
     */
    public static function primaryUrl(?string $text = null): ?string
    {
        $primary = self::primary();

        if ($primary === null) {
            return null;
        }

        return self::url($primary['phone'], $text);
    }

    public static function url(string $phone, ?string $text = null): string
    {
        $url = 'https://wa.me/'.$phone;

        return filled($text) ? $url.'?text='.urlencode($text) : $url;
    }

    /**
     * 6282236465949 → +62 822-3646-5949
     */
    public static function formatDisplay(string $phone): string
    {
        if (! str_starts_with($phone, '62') || strlen($phone) < 10) {
            return '+'.$phone;
        }

        $local = substr($phone, 2);
        $groups = [substr($local, 0, 3)];

        for ($i = 3; $i < strlen($local); $i += 4) {
            $groups[] = substr($local, $i, 4);
        }

        return '+62 '.implode('-', $groups);
    }
}
