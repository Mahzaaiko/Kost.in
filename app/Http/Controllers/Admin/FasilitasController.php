<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FasilitasRequest;
use App\Models\Fasilitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FasilitasController extends Controller
{
    public function index(): View
    {
        $dataFasilitas = Fasilitas::withCount('kos')->orderBy('nama')->get();

        return view('admin.fasilitas', compact('dataFasilitas'));
    }

    public function store(FasilitasRequest $request): RedirectResponse
    {
        $fasilitas = Fasilitas::create([
            'nama' => $request->validated('nama_fasilitas'),
            'ikon' => $request->validated('ikon') ?: null,
        ]);

        return back()->with('success', "Fasilitas {$fasilitas->nama} ditambahkan.");
    }

    public function update(FasilitasRequest $request, Fasilitas $fasilitas): RedirectResponse
    {
        $fasilitas->update([
            'nama' => $request->validated('nama_fasilitas'),
            'ikon' => $request->validated('ikon') ?: null,
        ]);

        return back()->with('success', "Fasilitas {$fasilitas->nama} diperbarui.");
    }

    public function destroy(Fasilitas $fasilitas): RedirectResponse
    {
        $nama = $fasilitas->nama;
        $jumlahKos = $fasilitas->kos()->count();
        $fasilitas->delete(); // relasi di fasilitas_kos ikut terhapus (cascade)

        $pesan = $jumlahKos > 0
            ? "Fasilitas {$nama} dihapus dan dilepas dari {$jumlahKos} kos."
            : "Fasilitas {$nama} dihapus.";

        return back()->with('success', $pesan);
    }
}
