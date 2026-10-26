<?php

namespace App\Models;

use App\Services\BookClosingService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookClosing extends Model
{
    protected $fillable = [
        'period_year',
        'period_month',
        'opening_balance',
        'total_in',
        'total_out',
        'closing_balance',
        'payment_count',
        'expense_count',
        'closed_by',
        'closed_at',
        'reopen_reason',
        'reopened_by',
        'reopened_at',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'opening_balance' => 'decimal:2',
        'total_in' => 'decimal:2',
        'total_out' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'payment_count' => 'integer',
        'expense_count' => 'integer',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    /**
     * Buku dianggap tertutup bila sudah ditutup dan belum dibuka kembali.
     */
    public function isClosed(): bool
    {
        return $this->closed_at !== null && $this->reopened_at === null;
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereNotNull('closed_at')->whereNull('reopened_at');
    }

    public static function forPeriod(int $year, int $month): ?self
    {
        return static::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();
    }

    public static function forDate(CarbonInterface $date): ?self
    {
        return static::forPeriod((int) $date->year, (int) $date->month);
    }

    public function periodLabel(): string
    {
        return BookClosingService::periodLabel($this->period_year, $this->period_month);
    }
}
