<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalPinIsChanged
{
    /**
     * Paksa wali mengganti PIN sebelum memakai halaman portal lainnya.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $studentId = $request->session()->get('portal_student_id');

        if (! $studentId) {
            return $next($request);
        }

        $student = Student::find($studentId);

        if (! $student || ! $student->must_change_pin) {
            return $next($request);
        }

        if ($request->routeIs('portal.change-pin', 'portal.change-pin.post', 'portal.logout')) {
            return $next($request);
        }

        return redirect()
            ->route('portal.change-pin')
            ->with('info', 'Demi keamanan, silakan ganti PIN terlebih dahulu sebelum melanjutkan.');
    }
}
