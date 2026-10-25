<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aturan: belum login ATAU peran tidak diizinkan -> halaman 404,
 * supaya orang yang mengetik URL manual tidak tahu halaman itu ada.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $idPengguna = $request->session()->get('id_pengguna');

        // Belum login
        abort_unless($idPengguna, 404);

        $user = Pengguna::with('role')->find($idPengguna);

        // Akun sudah dihapus atau dinonaktifkan setelah login: keluarkan dengan pesan
        if (!$user || !$user->is_aktif || !$user->role) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['username' => 'Akun tidak ditemukan atau tidak aktif. Silakan login kembali.']);
        }

        // Peran tidak diizinkan
        abort_unless(in_array($user->role->nama_role, $roles, true), 404);

        return $next($request);
    }
}