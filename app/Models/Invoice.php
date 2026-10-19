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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
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
     * Judul ringkas untuk kuitansi: keterangan diskon/beasiswa tidak dicetak
     * dan nama pos SPP diseragamkan menjadi "SPP Bulanan, ...".
     */
    public function getReceiptTitleAttribute(): string
    {
        $title = (string) preg_replace('/\s*\(diskon\s*\d+(?:[.,]\d+)?%\)\s*$/i', '', (string) $this->title);
        $title = (string) preg_replace('/^(?:iuran rutin spp|spp)\s*,/i', 'SPP Bulanan,', $title);

        return trim($title);
    }
}
