<?php

namespace App\Http\Controllers;

use App\Models\LogLogin;
use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class WebAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'role' => 'required|in:siswa,guru,tu,kepsek',
        ]);

        $key = Str::lower($data['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors([
                    'username' => 'Terlalu banyak percobaan login. Coba lagi dalam '
                        . RateLimiter::availableIn($key)
                        . ' detik.',
                ])
                ->withInput($request->only('username', 'role'));
        }

        $user = Pengguna::with('role')
            ->where('username', $data['username'])
            ->first();

        $valid = $user
            && $user->is_aktif
            && Hash::check($data['password'], $user->password_hash)
            && $user->role
            && $user->role->nama_role === $data['role'];

        try {
            LogLogin::create([
                'id_pengguna' => $user?->id_pengguna,
                'username_dicoba' => $data['username'],
                'berhasil' => (bool) $valid,
                'ip_address' => $request->ip(),
                'user_agent' => substr(
                    (string) $request->userAgent(),
                    0,
                    255
                ),
                'waktu' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors([
                    'username' => 'Login belum dapat diproses karena pencatatan login bermasalah.',
                ])
                ->withInput($request->only('username', 'role'));
        }

        if (!$valid) {
            RateLimiter::hit($key, 300);

            return back()
                ->withErrors([
                    'username' => 'Login gagal. Periksa username, kata sandi, dan role yang dipilih.',
                ])
                ->withInput($request->only('username', 'role'));
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        $request->session()->put([
            'id_pengguna' => $user->id_pengguna,
            'role' => $user->role->nama_role,
        ]);

        $user->forceFill([
            'terakhir_login' => now(),
        ])->save();

        $tujuan = match ($user->role->nama_role) {
            'siswa' => route('siswa.dashboard'),
            'guru' => route('guru.jadwal'),
            'tu' => route('tu.dashboard'),
            'kepsek' => route('kepsek.dashboard'),
        };

        return redirect()->to($tujuan);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Kamu berhasil keluar.');
    }
}