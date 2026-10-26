<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\CarbonInterface;

class InvoiceNumberService
{
    /**
     * Hasilkan nomor tagihan berurutan yang belum dipakai untuk prefix & periode tertentu.
     *
     * Nomor berbentuk INV-{PREFIX}-{YYYYMM}-{URUT}, mis. INV-BUKU-202610-0001.
     * Urutan dihitung dari nomor terbesar yang sudah ada pada prefix & bulan yang
     * sama, lalu kandidat dicek satu per satu agar nomor lama (termasuk format
     * lama yang memakai angka acak) tidak pernah tertabrak.
     *
     * @return list<string>
     */
    public function generate(string $prefix, int $count, ?CarbonInterface $date = null): array
    {
        $date ??= now();
        $yearMonth = $date->format('Ym');
        $sequence = $this->highestSequence($prefix, $yearMonth);

        $numbers = [];

        while (count($numbers) < $count) {
            $sequence++;
            $candidate = sprintf('INV-%s-%s-%04d', $prefix, $yearMonth, $sequence);

            if (Invoice::where('invoice_number', $candidate)->exists()) {
                continue;
            }

            $numbers[] = $candidate;
        }

        return $numbers;
    }

    /**
     * Urutan tertinggi yang tercatat untuk prefix & periode tertentu.
     */
    private function highestSequence(string $prefix, string $yearMonth): int
    {
        $pattern = '/^INV-'.preg_quote($prefix, '/').'-'.$yearMonth.'-(\d+)(?:-|$)/';

        return Invoice::where('invoice_number', 'like', "INV-{$prefix}-{$yearMonth}-%")
            ->pluck('invoice_number')
            ->reduce(function (int $max, string $number) use ($pattern): int {
                if (preg_match($pattern, $number, $matches) !== 1) {
                    return $max;
                }

                return max($max, (int) $matches[1]);
            }, 0);
    }
}
