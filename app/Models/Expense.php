<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Catatan bisyarah guru yang menghasilkan kas keluar ini (bila ada).
     */
    public function bisyarahPayment(): HasOne
    {
        return $this->hasOne(TeacherBisyarahPayment::class, 'expense_id');
    }

    /**
     * Kas keluar yang lahir dari modul bisyarah hanya boleh diubah atau
     * dibatalkan lewat halaman Bisyarah agar dua catatan tetap sinkron.
     */
    public function isManagedByBisyarah(): bool
    {
        if ($this->relationLoaded('bisyarahPayment')) {
            return $this->bisyarahPayment !== null;
        }

        return $this->bisyarahPayment()->exists();
    }

    /**
     * Kode kas keluar berurutan per bulan: EXP-{YYYYMM}-{URUT}.
     * Urutan dihitung dari nomor terbesar yang sudah ada pada bulan tersebut.
     */
    public static function generateCode(?CarbonInterface $date = null): string
    {
        $prefix = 'EXP-'.($date ?? now())->format('Ym').'-';

        $highest = static::query()
            ->where('expense_code', 'like', $prefix.'%')
            ->pluck('expense_code')
            ->reduce(fn (int $max, string $code): int => max($max, (int) substr($code, strlen($prefix))), 0);

        return $prefix.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
