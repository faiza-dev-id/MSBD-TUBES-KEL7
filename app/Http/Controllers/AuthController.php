<?php

namespace App\Http\Controllers;

use App\Support\DummyData;
use Illuminate\Http\Request;

/** LOGIN TIRUAN: hanya memilih role untuk demo UI. Diganti backend dengan Auth::attempt(). */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login', ['roles' => DummyData::roles()]);
    }

    public function login(Request $request)
    {
        $request->validate(['role' => ['required', 'in:siswa,guru,tu,kepsek']]);

        // DEMO tampilan error (logika asli dikerjakan backend)
        if ($request->username === 'terkunci') {
            return back()->withInput()->withErrors(['username' => 'Akun terkunci sementara karena 5 kali gagal masuk. Coba lagi dalam 15 menit atau hubungi admin.']);
        }
        if ($request->username === 'salah') {
            return back()->withInput()->withErrors(['username' => 'Username atau kata sandi salah. Sisa percobaan: 2 dari 5.']);
        }

        $request->session()->put('role', $request->role);
        $request->session()->put('nama', DummyData::roles()[$request->role]['nama']);

        return redirect()->route('beranda');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();

        return redirect()->route('login');
    }
}