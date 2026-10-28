<?php

namespace App\Models;

use App\Enums\IncomeCategory;
use App\Enums\TransferChannel;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pemasukan di luar pembayaran santri (donasi, infaq, unit usaha, dsb.).
 */
class OtherIncome extends Model
{
    protected $fillable = [
        'income_code',
        'category',
        'description',
        'amount',
        'income_date',
        'payment_method',
        'transfer_channel',
        'source',
        'notes',
        'recorded_by',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'category' => IncomeCategory::class,
        'amount' => 'decimal:2',
        'income_date' => 'date',
        'voided_at' => 'datetime',
    ];

    /**
     * Pemasukan yang masih sah (belum dibatalkan).
     */
    public function scopeNotVoided(Builder $query): Builder
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

    public function paymentMethodLabel(): string
    {
        return TransferChannel::methodLabel((string) $this->payment_method, $this->transfer_channel);
    }

    /**
     * Kode pemasukan berurutan per bulan: INC-{YYYYMM}-{URUT}.
     */
    public static function generateCode(?CarbonInterface $date = null): string
    {
        $prefix = 'INC-'.($date ?? now())->format('Ym').'-';

        $highest = static::query()
            ->where('income_code', 'like', $prefix.'%')
            ->pluck('income_code')
            ->reduce(fn (int $max, string $code): int => max($max, (int) substr($code, strlen($prefix))), 0);

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
