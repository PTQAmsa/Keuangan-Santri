<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Enums\ActivityModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * Log bersifat append-only, sehingga tidak ada kolom updated_at yang perlu diisi.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'subject_id',
        'student_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected $casts = [
        'module' => ActivityModule::class,
        'action' => ActivityAction::class,
        'properties' => 'array',
    ];

    /**
     * Pengurus yang melakukan perubahan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Santri yang data keuangannya terpengaruh (bila ada).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
