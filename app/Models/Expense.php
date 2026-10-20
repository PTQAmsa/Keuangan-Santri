<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_code',
        'category',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'recipient',
        'notes',
        'recorded_by',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'category' => ExpenseCategory::class,
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'voided_at' => 'datetime',
    ];

    /**
     * Pengeluaran yang masih sah (belum dibatalkan).
     */
    public function scopeNotVoided($query)
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
