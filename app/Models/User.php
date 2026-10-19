<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'jabatan', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Jabatan yang ditampilkan pada antarmuka, dengan fallback ke nama peran.
     */
    public function displayTitle(): string
    {
        return $this->jabatan ?: ($this->role?->label() ?? '-');
    }

    /**
     * Gelar penanda tangan yang dicetak pada kuitansi.
     * Bendahara 1 & 2 memakai jabatannya, sedangkan akun lain memakai sebutan umum.
     */
    public function receiptTitle(): string
    {
        if ($this->role === UserRole::Bendahara && $this->jabatan) {
            return $this->jabatan;
        }

        return 'Bendahara / Kasir Keuangan';
    }
}
