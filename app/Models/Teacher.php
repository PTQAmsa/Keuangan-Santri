<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    /**
     * Status guru/pengajar yang dikenali sistem.
     *
     * @var array<string, string>
     */
    public const STATUSES = [
        'pengasuh' => 'Majelis Kyai/Nyai',
        'guru_tetap' => 'Guru Tetap',
        'guru_tidak_tetap' => 'Guru Tidak Tetap',
        'staf' => 'Staf',
    ];

    protected $fillable = [
        'nama_lengkap',
        'kota_asal',
        'no_whatsapp',
        'status',
        'notes',
        'is_active',
        'inactive_reason',
        'inactivated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'inactivated_at' => 'datetime',
    ];

    /**
     * Hanya guru yang masih aktif mengajar/bekerja.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function bisyarahPayments(): HasMany
    {
        return $this->hasMany(TeacherBisyarahPayment::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Seragamkan nomor WhatsApp ke format internasional 62xxxxxxxxxx.
     */
    public static function normalizeWhatsApp(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }
}
