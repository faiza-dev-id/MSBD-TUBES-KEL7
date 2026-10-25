<?php

namespace App\Http\Controllers;

use App\Models\Staf;
use Illuminate\Http\Request;

class StafController extends Controller
{
    public function index(Request $request)
    {
        $query = Staf::query();

        if ($request->filled('cari')) {
            $cari = $request->cari;

            $query->where(function ($q) use ($cari) {
                $q->where('nama_staf', 'ilike', "%{$cari}%");
            });
        }

        $staf = $query
            ->orderBy('nama_staf')
            ->paginate(15)
            ->withQueryString();

        return view('tu.staf', compact('staf'));
    }

    public function show(int $id)
    {
        $staf = Staf::findOrFail($id);

        return view('tu.staf-detail', compact('staf'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_staf' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        Staf::create($data);

        return redirect()
            ->route('tu.staf.index')
            ->with('success', 'Data staf berhasil ditambahkan.');
    }

    public function update(Request $request, int $id)
    {
        $staf = Staf::findOrFail($id);

        $data = $request->validate([
            'nama_staf' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
        ]);

        $staf->update($data);

        return redirect()
            ->route('tu.staf.index')
            ->with('success', 'Data staf berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $staf = Staf::findOrFail($id);
        $staf->delete();

        return redirect()
            ->route('tu.staf.index')
            ->with('success', 'Data staf berhasil dihapus.');
    }
}