<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas halaman per role. Sementara membaca session('role') (login tiruan).
 * Setelah backend siap, ganti dengan $request->user()->role->nama_role.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->session()->get('role');

        if (! $role) {
            return redirect()->route('login');
        }
        if (! in_array($role, $roles, true)) {
            abort(403, 'Anda tidak punya hak akses ke halaman ini.');
        }

        return $next($request);
    }
}