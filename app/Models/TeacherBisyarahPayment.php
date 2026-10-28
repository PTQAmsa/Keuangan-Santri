<?php

namespace App\Models;

use App\Enums\TransferChannel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherBisyarahPayment extends Model
{
    protected $fillable = [
        'teacher_id',
        'period_year',
        'period_month',
        'amount',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'notes',
        'recorded_by',
        'expense_id',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Kas keluar kategori Bisyarah yang mewakili pembayaran ini di buku kas.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function periodLabel(): string
    {
        return Carbon::create($this->period_year, $this->period_month, 1)
            ->translatedFormat('F Y');
    }

    public function paymentMethodLabel(): string
    {
        return $this->payment_method === 'cash' ? 'Tunai' : 'Transfer';
    }

    /**
     * Metode beserta salurannya: "Transfer BSI", "Transfer DANA", atau "Tunai".
     */
    public function paymentChannelLabel(): string
    {
        return TransferChannel::methodLabel((string) $this->payment_method, $this->bank_name);
    }

    public function hasBankAccount(): bool
    {
        return $this->payment_method === 'transfer' && filled($this->bank_account_number);
    }
}
