<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatas halaman per role. Sementara membaca session('role') (login tiruan).
 * Setelah backend siap, ganti dengan $request->user()->role->nama_role.
 *
 * Aturan (sesuai dokumen): belum login ATAU peran tidak diizinkan -> halaman 404,
 * supaya orang yang mengetik URL manual tidak tahu halaman itu ada.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->session()->get('role');

        abort_unless($role && in_array($role, $roles, true), 404);

        return $next($request);
    }
}