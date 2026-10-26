<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Batasi akses route hanya untuk peran yang diizinkan.
     *
     * Pengasuh bersifat hanya melihat: ia boleh membuka semua halaman yang
     * boleh dibuka Bendahara (permintaan GET/HEAD), tetapi setiap aksi yang
     * mengubah data (POST/PATCH/DELETE) tetap ditolak.
     *
     * @param  string  ...$roles  Daftar nilai peran yang diizinkan, mis. "admin,bendahara".
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }

        if (in_array($user->role?->value, $roles, true)) {
            return $next($request);
        }

        $isReadOnlyViewer = $user->role === UserRole::Pengasuh
            && in_array(UserRole::Bendahara->value, $roles, true)
            && $request->isMethodSafe();

        if (! $isReadOnlyViewer) {
            abort(
                Response::HTTP_FORBIDDEN,
                $user->role === UserRole::Pengasuh
                    ? 'Akun Pengasuh hanya dapat melihat data, tidak dapat mengubahnya.'
                    : 'Anda tidak memiliki hak akses untuk halaman ini.'
            );
        }

        return $next($request);
    }
}
