<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $query = Guru::query();

        if ($request->filled('cari')) {
            $cari = $request->cari;

            $query->where(function ($q) use ($cari) {
                $q->where('nama_guru', 'ilike', "%{$cari}%")
                    ->orWhere('nip', 'ilike', "%{$cari}%");
            });
        }

        $guru = $query->orderBy('nama_guru')
            ->paginate(15)
            ->withQueryString();

        return view('tu.guru', compact('guru'));
    }

    public function show(int $id)
    {
        $guru = Guru::findOrFail($id);

        return view('tu.guru-detail', compact('guru'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_guru' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        Guru::create($data);

        return redirect()
            ->route('tu.guru.index')
            ->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $guru = Guru::findOrFail($id);

        $data = $request->validate([
            'nama_guru' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        $guru->update($data);

        return redirect()
            ->route('tu.guru.index')
            ->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $guru = Guru::findOrFail($id);
        $guru->delete();

        return redirect()
            ->route('tu.guru.index')
            ->with('success', 'Data guru berhasil dihapus.');
    }
}