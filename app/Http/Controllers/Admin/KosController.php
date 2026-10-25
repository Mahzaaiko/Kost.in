<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKos;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KosRequest;
use App\Models\Fasilitas;
use App\Models\Kos;
use App\Services\KosService;
use App\Support\Koordinat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KosController extends Controller
{
    public function __construct(private readonly KosService $service) {}

    public function index(): View
    {
        $kampus = Koordinat::kampus();

        $dataKos = Kos::query()
            ->with(['fotoUtama', 'fasilitas'])
            ->select('kos.*')
            ->selectRaw('ST_Distance(lokasi, ST_GeogFromText(?)) AS jarak_m', [$kampus->toEwkt()])
            ->latest()
            ->get();

        return view('admin.datakos', compact('dataKos'));
    }

    public function create(): View
    {
        return view('admin.datakosTambah', [
            'kos' => new Kos,
            'semuaFasilitas' => Fasilitas::orderBy('nama')->get(),
        ]);
    }

    public function store(KosRequest $request): RedirectResponse
    {
        $kos = $this->service->simpan(
            new Kos,
            $request->atributKos(),
            array_map('intval', (array) $request->validated('fasilitas', [])),
            $request->file('foto', []),
            [],
            $request->validated('foto_utama'),
        );

        return redirect()
            ->route('admin.datakos.detail', $kos)
            ->with('success', "{$kos->nama} berhasil disimpan sebagai Draft. Aktifkan dari halaman Data Kos kalau datanya sudah lengkap.");
    }

    public function show(Kos $kos): View
    {
        $kos->load(['foto', 'fasilitas']);
        $kampus = Koordinat::kampus();
        $jarakM = $kos->lokasi?->jarakMeterKe($kampus);

        return view('admin.datakosDetail', [
            'kos' => $kos,
            'kampus' => $kampus,
            'jarakM' => $jarakM,
            'kekurangan' => $kos->kekuranganUntukAktif(),
        ]);
    }

    public function edit(Kos $kos): View
    {
        $kos->load(['foto', 'fasilitas']);

        return view('admin.datakosEdit', [
            'kos' => $kos,
            'semuaFasilitas' => Fasilitas::orderBy('nama')->get(),
        ]);
    }

    public function update(KosRequest $request, Kos $kos): RedirectResponse
    {
        $this->service->simpan(
            $kos,
            $request->atributKos(),
            array_map('intval', (array) $request->validated('fasilitas', [])),
            $request->file('foto', []),
            array_map('intval', (array) $request->validated('hapus_foto', [])),
            $request->validated('foto_utama'),
        );

        return redirect()
            ->route('admin.datakos.detail', $kos)
            ->with('success', "Perubahan {$kos->nama} berhasil disimpan.");
    }

    public function destroy(Kos $kos): RedirectResponse
    {
        $nama = $kos->nama;
        $this->service->hapus($kos);

        return redirect()->route('admin.datakos')->with('success', "{$nama} berhasil dihapus.");
    }

    public function status(Request $request, Kos $kos): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(StatusKos::class)],
        ]);

        $this->service->ubahStatus($kos, StatusKos::from($data['status']));

        $label = StatusKos::from($data['status'])->label();

        return back()->with('success', "Status {$kos->nama} sekarang {$label}.");
    }
}
