<?php

namespace App\Http\Controllers;

use App\Models\LogLogin;
use App\Models\Pengguna;
use App\Support\DummyData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** DFD 1.1 Autentikasi Login: cek akun, catat ke log_login, kunci 15 menit setelah 5x gagal. */
class AuthController extends Controller
{
    const MAKS_GAGAL = 5;
    const MENIT_KUNCI = 15;

    public function showLogin()
    {
        return view('auth.login', ['roles' => DummyData::roles()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);
        $username = $data['username'];

        // hitung gagal beruntun: setelah login berhasil terakhir & dalam 15 menit terakhir
        $batas = now()->subMinutes(self::MENIT_KUNCI);
        $terakhirBerhasil = LogLogin::where('username_dicoba', $username)->where('berhasil', true)->max('waktu');
        if ($terakhirBerhasil && $terakhirBerhasil > $batas) {
            $batas = $terakhirBerhasil;
        }
        $gagal = LogLogin::where('username_dicoba', $username)
            ->where('berhasil', false)
            ->where('waktu', '>', $batas)
            ->count();

        if ($gagal >= self::MAKS_GAGAL) {
            return back()->withInput()->withErrors(['username' =>
                'Akun terkunci sementara karena ' . self::MAKS_GAGAL . ' kali gagal masuk. Coba lagi dalam ' . self::MENIT_KUNCI . ' menit atau hubungi admin.']);
        }

        $user = Pengguna::with('role')->where('username', $username)->first();
        $ok = $user && $user->is_aktif && Hash::check($data['password'], $user->password_hash);

        LogLogin::create([
            'id_pengguna'     => $user?->id_pengguna,
            'username_dicoba' => $username,
            'berhasil'        => $ok,
            'ip_address'      => $request->ip(),
            'user_agent'      => substr((string) $request->userAgent(), 0, 255),
            'waktu'           => now(),
        ]);

        if (! $ok) {
            $sisa = self::MAKS_GAGAL - ($gagal + 1);
            return back()->withInput()->withErrors(['username' =>
                'Username atau kata sandi salah. Sisa percobaan: ' . $sisa . ' dari ' . self::MAKS_GAGAL . '.']);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->update(['terakhir_login' => now()]);

        // layout lama masih membaca session('role') dan session('nama')
        $request->session()->put('role', $user->role->nama_role);
        $request->session()->put('nama', $user->namaTampil());

        return redirect()->route('beranda');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
