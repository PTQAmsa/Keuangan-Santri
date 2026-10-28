<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Saldo kas awal saat sistem mulai dipakai (kas tunai atau satu rekening bank).
 */
class OpeningBalance extends Model
{
    protected $fillable = [
        'effective_date',
        'payment_method',
        'amount',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Nama tempat uang untuk ditampilkan, mis. "Kas Tunai" atau "BSI 1202020212".
     */
    public function placeLabel(): string
    {
        if ($this->payment_method === 'cash') {
            return 'Kas Tunai';
        }

        return trim(($this->bank_name ?: 'Rekening').' '.($this->bank_account_number ?: ''));
    }
}
