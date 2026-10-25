<?php

namespace App\Http\Middleware;

use App\Models\Pengguna;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class EnsureRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $idPengguna = $request->session()->get('id_pengguna');

        // Belum login
        if (!$idPengguna) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'username' => 'Silakan login terlebih dahulu.',
                ]);
        }

        // Ambil pengguna beserta role dari database
        $user = Pengguna::with('role')->find($idPengguna);

        // Pengguna tidak ditemukan atau akun dinonaktifkan
        if (!$user || !$user->is_aktif || !$user->role) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'username' => 'Akun tidak ditemukan atau tidak aktif. Silakan login kembali.',
                ]);
        }

        // Periksa izin role
        if (!in_array($user->role->nama_role, $roles, true)) {
            abort(403, 'Anda tidak punya hak akses ke halaman ini.');
        }

        return $next($request);
    }
}
