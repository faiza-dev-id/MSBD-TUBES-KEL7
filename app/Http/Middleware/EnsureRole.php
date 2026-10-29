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
        $username = $request->session()->get('username');
        $roleSesi = $request->session()->get('role');

        // Belum login
        abort_unless($idPengguna, 404);

        // Cocokkan juga username/peran sesi. Ini mencegah 404 ketika tabel pengguna
        // memiliki id yang sama untuk akun berbeda (mis. akibat urutan sequence lama),
        // sehingga find() bisa mengambil akun dengan peran yang salah.
        $query = Pengguna::with('role')->where('id_pengguna', $idPengguna);

        if ($username) {
            $query->where('username', $username);
        } elseif ($roleSesi) {
            $query->whereHas('role', fn ($q) => $q->where('nama_role', $roleSesi));
        }

        $user = $query->first();

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