<?php

namespace App\Http\Controllers;

use App\Models\LogLogin;
use App\Models\Pengguna;
use App\Services\PengaturanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class WebAuthController extends Controller
{
    public function login(Request $request, PengaturanService $pengaturan)
    {
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'role' => 'required|in:siswa,guru,tu,kepsek',
        ]);

        // Dari Pengaturan Sistem (DFD 1.4); bawaan 5 kali gagal, dikunci 15 menit.
        $maksGagal = max(1, (int) $pengaturan->nilai('max_login_gagal'));
        $detikKunci = max(60, (int) $pengaturan->nilai('durasi_kunci') * 60);

        $key = Str::lower($data['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $maksGagal)) {
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
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
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
            RateLimiter::hit($key, $detikKunci);

            return back()
                ->withErrors([
                    'username' => 'Login gagal. Periksa username, kata sandi, dan role yang dipilih.',
                ])
                ->withInput($request->only('username', 'role'));
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        // Nama yang tampil di topbar diambil dari data guru/staf/siswa terkait.
        $user->loadMissing(['guru', 'staf', 'siswa']);
        $nama = match ($user->role->nama_role) {
            'guru'   => $user->guru->first()->nama_guru ?? $user->username,
            'tu',
            'kepsek' => $user->staf->first()->nama_staf ?? $user->username,
            'siswa'  => $user->siswa->first()->nama_lengkap ?? $user->username,
            default  => $user->username,
        };

        $request->session()->put([
            'id_pengguna' => $user->id_pengguna,
            'role' => $user->role->nama_role,
            'nama' => $nama,
        ]);

        $user->forceFill(['terakhir_login' => now()])->save();

        $tujuan = match ($user->role->nama_role) {
            'siswa' => route('siswa.dashboard'),
            'guru' => route('guru.jadwal'),
            'tu' => route('tu.dashboard'),
            'kepsek' => route('kepsek.dashboard'),
        };

        return redirect()->to($tujuan);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'sandi_lama' => 'required|string',
            'sandi_baru' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
                'different:sandi_lama',
            ],
            'sandi_ulang' => 'required|same:sandi_baru',
        ], [
            'sandi_baru.min' => 'Kata sandi baru minimal 8 karakter.',
            'sandi_baru.regex' => 'Kata sandi baru harus mengandung huruf dan angka.',
            'sandi_baru.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
            'sandi_ulang.same' => 'Ulangi kata sandi dengan benar.',
        ]);

        $idPengguna = $request->session()->get('id_pengguna');

        $pengguna = Pengguna::find($idPengguna);

        if (!$pengguna || !$pengguna->is_aktif) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'username' => 'Akun tidak ditemukan atau sudah tidak aktif.',
                ]);
        }

        if (!Hash::check(
            $request->input('sandi_lama'),
            $pengguna->password_hash
        )) {
            return back()->withErrors([
                'sandi_lama' => 'Kata sandi lama tidak benar.',
            ]);
        }

        $pengguna->password_hash = Hash::make(
            $request->input('sandi_baru')
        );

        $pengguna->save();

        return redirect()
            ->route('akun.sandi')
            ->with('success', 'Kata sandi berhasil diubah.');
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
