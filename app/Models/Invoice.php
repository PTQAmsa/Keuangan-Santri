<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'payment_category_id',
        'title',
        'amount',
        'paid_amount',
        'due_date',
        'status',
        'period_month',
        'period_year',
        'notes',
        'edit_count',
    ];

    /**
     * Jumlah maksimal koreksi data tagihan (judul, nominal, jatuh tempo).
     */
    public const MAX_EDITS = 2;

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'edit_count' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PaymentCategory::class, 'payment_category_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Sisa tagihan yang belum dibayar
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) ($this->amount - $this->paid_amount));
    }

    /**
     * Tagihan yang sudah dibatalkan tidak boleh diubah/dihapus lagi.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Apakah tagihan ini berasal dari pos wakaf (komitmen, bukan tagihan bulanan).
     */
    public function isWakaf(): bool
    {
        $teks = strtoupper(trim(($this->category?->code ?? '').' '.($this->category?->name ?? '')));

        return str_contains($teks, 'WAKAF');
    }

    /**
     * Sisa kuota koreksi data tagihan (maksimal dua kali).
     */
    public function remainingEdits(): int
    {
        return max(0, self::MAX_EDITS - (int) $this->edit_count);
    }

    /**
     * Pembayaran aktif (belum dibatalkan) yang tercatat pada tagihan ini.
     */
    public function hasActivePayments(): bool
    {
        return $this->payments()->whereNull('voided_at')->exists();
    }

    /**
     * Hitung ulang status tagihan berdasarkan total pembayaran aktif.
     */
    public function recalculateStatus(): void
    {
        $paid = (float) $this->payments()->whereNull('voided_at')->sum('amount_paid');

        $this->paid_amount = $paid;

        // Tagihan yang sudah dibatalkan tetap berstatus dibatalkan.
        if ($this->isCancelled()) {
            $this->save();

            return;
        }

        if ($paid <= 0) {
            $this->status = 'unpaid';
        } elseif ($paid >= (float) $this->amount) {
            $this->status = 'paid';
        } else {
            $this->status = 'partial';
        }

        $this->save();
    }

    /**
     * Label status bahasa Indonesia
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'Lunas',
            'partial' => 'Dicicil (Sebagian)',
            'unpaid' => 'Belum Bayar',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Judul ringkas untuk kuitansi: keterangan potongan/beasiswa tidak dicetak
     * dan nama pos SPP diseragamkan menjadi "SPP Bulanan, ...".
     */
    public function getReceiptTitleAttribute(): string
    {
        $title = (string) preg_replace('/\s*\((?:diskon|potongan)\s*\d+(?:[.,]\d+)?%\)\s*$/i', '', (string) $this->title);
        $title = (string) preg_replace('/^(?:iuran rutin spp|spp)\s*,/i', 'SPP Bulanan,', $title);

        return trim($title);
    }
}
