<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'nisn',
        'nama_lengkap',
        'jenis_kelamin',
        'jenjang',
        'kelas',
        'kamar_asrama',
        'tahun_masuk',
        'status',
        'kota_asal',
        'alamat_lengkap',
        'nama_wali',
        'no_whatsapp_wali',
        'hubungan_wali',
        'pin_portal',
        'must_change_pin',
        'pin_changed_at',
        'pin_reset_requested_at',
        'diskon_beasiswa',
        'bebas_wakaf',
    ];

    protected $casts = [
        'diskon_beasiswa' => 'decimal:2',
        'bebas_wakaf' => 'boolean',
        'tahun_masuk' => 'integer',
        'must_change_pin' => 'boolean',
        'pin_changed_at' => 'datetime',
        'pin_reset_requested_at' => 'datetime',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Total tunggakan aktif yang belum lunas
     */
    public function getTotalTunggakanAttribute(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(function ($invoice) {
                return $invoice->remaining_amount;
            });
    }
}
