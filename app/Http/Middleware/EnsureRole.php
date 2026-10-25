<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pembatas halaman per role, membaca role dari tabel role (via pengguna). */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }
        if (! in_array($user->role->nama_role, $roles, true)) {
            abort(403, 'Anda tidak punya hak akses ke halaman ini.');
        }

        return $next($request);
    }
}
