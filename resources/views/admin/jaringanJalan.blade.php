@extends('layouts.admin')

@section('title', 'Jaringan Jalan')
@section('page_title', 'Jaringan Jalan')
@section('breadcrumb', 'Admin / Jaringan Jalan')

@section('content')
@php
    $kampus = \App\Support\Koordinat::kampus();
    $toleransi = (int) config('kostin.routing.toleransi_snapping_m');
@endphp

@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
@endif
@unless ($adaJaringanOsm)
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <p class="font-bold">Jaringan jalan OSM belum diimpor.</p>
        <p class="mt-0.5 text-xs">Jalankan <code class="rounded bg-white px-1">php artisan jalan:import-osm</code> dulu. Gang hasil survei disambungkan ke jalan OSM, jadi belum bisa diunggah.</p>
    </div>
@endunless

{{-- ================= HEADER ================= --}}
<div>
    <h2 class="text-2xl font-bold text-ink">Jaringan Jalan</h2>
    <p class="mt-1 text-sm text-slate-500">Tambahkan gang hasil survei yang belum ada di peta OpenStreetMap. Gang langsung dipakai rute semua kos bila lebih pendek.</p>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- ================= KOLOM KIRI: UPLOAD + DAFTAR ================= --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-ink">Unggah Rekaman Gang</h3>
            <p class="mt-1 text-xs leading-relaxed text-slate-500">File GPX dari aplikasi GPS, atau GeoJSON berisi satu garis. Satu file untuk satu gang. Mulai dan akhiri rekaman di jalan yang sudah ada (maksimal {{ $toleransi }} m). Gang buntu boleh, asal satu ujungnya di jalan.</p>

            <form method="POST" action="{{ route('admin.jaringanJalan.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="berkas" class="mb-1.5 block text-sm font-semibold text-slate-700">File rekaman <span class="text-red-500">*</span></label>
                    <input type="file" id="berkas" name="berkas" required accept=".gpx,.geojson,.json" class="block w-full rounded-xl border border-stone-200 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-[#fbf5f3] file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-[#B46C54]">
                    <p class="mt-1 text-[11px] text-slate-500">.gpx, .geojson, atau .json, maksimal {{ (int) config('kostin.routing.berkas_survei_maks_kb') / 1024 }} MB.</p>
                </div>
                <div>
                    <label for="nama" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama gang</label>
                    <input type="text" id="nama" name="nama" maxlength="150" value="{{ old('nama') }}" placeholder="Contoh: Gang Malabar Ujung ke Sancang" class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">
                </div>
                <div>
                    <label for="catatan" class="mb-1.5 block text-sm font-semibold text-slate-700">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="2" maxlength="500" placeholder="Contoh: lewat jembatan kecil, direkam Andi 2 Nov" class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">{{ old('catatan') }}</textarea>
                </div>
                <button type="submit" @disabled(! $adaJaringanOsm) class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#955844] disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Unggah dan Sambungkan
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-stone-200 px-6 py-4">
                <h3 class="text-base font-bold text-ink">Gang Hasil Survei</h3>
                <span class="text-xs font-medium text-slate-500">{{ $rekaman->count() }} gang, {{ number_format($rekaman->sum('panjang_m'), 0, ',', '.') }} m</span>
            </div>
            <div class="max-h-[420px] divide-y divide-stone-100 overflow-y-auto">
                @forelse ($rekaman as $r)
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <button type="button" data-lihat-gang="{{ $r->id }}" class="text-left">
                                <p class="text-sm font-bold text-ink hover:text-[#B46C54]">{{ $r->namaTampil() }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ number_format($r->panjang_m, 0, ',', '.') }} m · {{ $r->created_at->translatedFormat('d M Y') }}{{ $r->pengunggah ? ' · '.$r->pengunggah->name : '' }}</p>
                            </button>
                            <form method="POST" action="{{ route('admin.jaringanJalan.hapus', $r) }}" onsubmit="return confirm('Hapus {{ addslashes($r->namaTampil()) }}? Rute kos akan dihitung ulang tanpa gang ini.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-500" aria-label="Hapus gang">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m5 4v6m4-6v6M4 7h16M9 7V4h6v3"/></svg>
                                </button>
                            </form>
                        </div>
                        @if ($r->ujung_tersambung === 0)
                            <p class="mt-2 rounded-lg bg-red-50 px-2 py-1 text-[11px] font-semibold text-red-600">Tidak tersambung ke jalan setelah import ulang OSM, jadi tidak dipakai rute. Hapus lalu unggah ulang bila perlu.</p>
                        @elseif ($r->ujung_tersambung === 1)
                            <p class="mt-2 text-[11px] font-semibold text-amber-700">Gang buntu (satu ujung tersambung)</p>
                        @endif
                        @if ($r->catatan)
                            <p class="mt-1 text-[11px] text-slate-500">{{ $r->catatan }}</p>
                        @endif
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-slate-500">Belum ada gang yang diunggah.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ================= KOLOM KANAN: PETA PEMBANDING ================= --}}
    <div class="lg:col-span-2">
        <div id="kartu-peta" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-ink">Bandingkan Rute</h3>
                    <p class="mt-1 text-xs text-slate-500">Pilih kos untuk melihat rute dengan dan tanpa gang hasil survei.</p>
                </div>
                <select id="pilih-kos" class="rounded-xl border border-stone-200 bg-white px-3 py-2 text-sm text-ink focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54] sm:w-64">
                    <option value="">Pilih kos...</option>
                    @foreach ($kos as $k)
                        <option value="{{ $k['id'] }}">{{ $k['nama'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="relative z-0 mt-4 overflow-hidden rounded-xl border border-stone-200">
                <div id="peta-jaringan" class="h-[460px] w-full"></div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-[11px] font-semibold text-slate-600">
                <span class="flex items-center gap-2"><span class="inline-block h-1 w-6 rounded bg-[#B46C54]"></span>Gang hasil survei</span>
                <span class="flex items-center gap-2"><span class="inline-block h-0 w-6 border-t-2 border-dashed border-red-500"></span>Gang tidak tersambung</span>
                <span class="flex items-center gap-2"><span class="inline-block h-0 w-6 border-t-[3px] border-dashed border-slate-500"></span>Rute OSM saja</span>
                <span class="flex items-center gap-2"><span class="inline-block h-1 w-6 rounded bg-[#2563eb]"></span>Rute OSM + survei</span>
                <span class="flex items-center gap-2"><span class="inline-block h-3 w-3 rounded-[3px] bg-stone-900"></span>Kampus</span>
            </div>

            <div id="info-banding" class="mt-4 hidden grid-cols-2 gap-3 sm:grid-cols-4"></div>
        </div>
    </div>
</div>

{{-- ================= TABEL VALIDASI ================= --}}
<div class="rounded-2xl border border-stone-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-stone-200 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-bold text-ink">Tabel Validasi Jarak</h3>
            <p class="mt-1 text-xs text-slate-500">Semua kos yang punya titik lokasi. Rasio = panjang rute dibagi garis lurus, makin dekat ke 1 makin langsung. <span id="status-tabel" class="font-semibold text-[#B46C54]"></span></p>
        </div>
        <a href="{{ route('admin.jaringanJalan.ekspor') }}" class="flex shrink-0 items-center justify-center gap-2 rounded-xl border border-[#B46C54] px-4 py-2 text-xs font-bold text-[#B46C54] transition hover:bg-[#B46C54] hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Unduh CSV
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="bg-stone-50 text-[11px] font-bold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">Nama Kos</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Garis Lurus</th>
                    <th class="px-5 py-3 text-right">Rute OSM</th>
                    <th class="px-5 py-3 text-right">OSM + Survei</th>
                    <th class="px-5 py-3 text-right">Selisih</th>
                    <th class="px-5 py-3 text-right">Rasio OSM</th>
                    <th class="px-5 py-3 text-right">Rasio + Survei</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($kos as $k)
                    <tr data-kos="{{ $k['id'] }}" class="hover:bg-stone-50">
                        <td class="px-5 py-3 font-semibold text-ink">{{ $k['nama'] }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $k['status'] }}</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums">{{ number_format($k['garis_lurus_m'], 0, ',', '.') }} m</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-400" data-kolom="rute_osm_m">...</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-400" data-kolom="rute_survei_m">...</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-400" data-kolom="selisih_m">...</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-400" data-kolom="rasio_osm">...</td>
                        <td class="whitespace-nowrap px-5 py-3 text-right tabular-nums text-slate-400" data-kolom="rasio_survei">...</td>
                        <td class="px-5 py-3 text-right"><button type="button" data-lihat-kos="{{ $k['id'] }}" class="text-xs font-bold text-[#B46C54] hover:underline">Lihat</button></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-8 text-center text-sm text-slate-500">Belum ada kos yang punya titik lokasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ================= LEAFLET ================= --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const KAMPUS = @js(['lat' => $kampus->lat, 'lng' => $kampus->lng, 'nama' => config('kostin.kampus.nama')]);
    const GARIS = @js($garis);
    const REKAMAN = @js($rekaman->map(fn ($r) => ['id' => $r->id, 'nama' => $r->namaTampil(), 'ujung' => $r->ujung_tersambung])->keyBy('id'));
    const KOS = @js($kos->keyBy('id'));
    const URL_BANDINGKAN = @js(route('admin.jaringanJalan.bandingkan', ['kos' => '__ID__']));

    const fmtM = m => m === null || m === undefined ? '-' : Math.round(m).toLocaleString('id-ID') + ' m';
    const fmtRasio = r => r === null || r === undefined ? '-' : r.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // ---------- Peta ----------
    const peta = L.map('peta-jaringan').setView([KAMPUS.lat, KAMPUS.lng], 15);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(peta);

    L.marker([KAMPUS.lat, KAMPUS.lng], {
        icon: L.divIcon({ className: '', html: '<span style="display:flex;width:18px;height:18px;border-radius:5px;background:#1c1917;transform:translate(-50%,-50%);box-shadow:0 1px 4px rgba(0,0,0,.4)"></span>', iconSize: [0, 0] }),
        keyboard: false
    }).addTo(peta).bindTooltip(KAMPUS.nama, { direction: 'top', offset: [0, -10] });

    // Gang hasil survei
    const lapisanGang = {};
    Object.entries(GARIS).forEach(([id, geo]) => {
        if (!geo) return;
        const info = REKAMAN[id] || { nama: 'Gang', ujung: 0 };
        const putus = info.ujung === 0;
        lapisanGang[id] = L.geoJSON(geo, {
            style: { color: putus ? '#ef4444' : '#B46C54', weight: 5, opacity: 0.9, dashArray: putus ? '6 6' : null }
        }).addTo(peta).bindTooltip(info.nama, { sticky: true });
    });
    const semuaGang = Object.values(lapisanGang);
    if (semuaGang.length) {
        peta.fitBounds(L.featureGroup(semuaGang).getBounds().extend([KAMPUS.lat, KAMPUS.lng]), { padding: [30, 30], maxZoom: 17 });
    }

    // Titik kos
    Object.values(KOS).forEach(k => {
        L.circleMarker([k.lat, k.lng], { radius: 5, color: '#fff', weight: 1.5, fillColor: '#64748b', fillOpacity: 1 })
            .addTo(peta).bindTooltip(k.nama, { direction: 'top' })
            .on('click', () => pilihKos(k.id));
    });

    document.querySelectorAll('[data-lihat-gang]').forEach(el => el.addEventListener('click', () => {
        const lapisan = lapisanGang[el.dataset.lihatGang];
        if (lapisan) {
            peta.fitBounds(lapisan.getBounds(), { padding: [60, 60], maxZoom: 18 });
            lapisan.openTooltip(lapisan.getBounds().getCenter());
        }
    }));

    // ---------- Data banding per kos (dipakai tabel dan peta) ----------
    const hasil = {};
    function ambil(id) {
        if (!hasil[id]) {
            hasil[id] = fetch(URL_BANDINGKAN.replace('__ID__', id), { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject(r))
                .catch(err => { delete hasil[id]; throw err; });
        }
        return hasil[id];
    }

    // ---------- Pembanding rute ----------
    const lapisanRute = L.layerGroup().addTo(peta);
    const infoEl = document.getElementById('info-banding');
    const pilihEl = document.getElementById('pilih-kos');
    let kosTerpilih = null;

    function kotakInfo(judul, nilai, warna) {
        return `<div class="rounded-xl bg-stone-50 px-3 py-2"><p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">${judul}</p><p class="mt-0.5 text-sm font-extrabold ${warna || 'text-ink'}">${nilai}</p></div>`;
    }

    async function pilihKos(id) {
        id = String(id);
        kosTerpilih = id;
        pilihEl.value = id;
        lapisanRute.clearLayers();
        infoEl.classList.remove('hidden');
        infoEl.classList.add('grid');
        infoEl.innerHTML = '<p class="col-span-full text-xs text-slate-500">Menghitung rute...</p>';

        let d;
        try {
            d = await ambil(id);
        } catch (e) {
            infoEl.innerHTML = '<p class="col-span-full text-xs font-semibold text-red-600">Rute gagal dihitung. Coba lagi.</p>';
            return;
        }
        if (kosTerpilih !== id) return;

        const k = KOS[id];
        if (d.geometri_osm) L.geoJSON(d.geometri_osm, { style: { color: '#475569', weight: 4, opacity: 0.85, dashArray: '8 8' } }).addTo(lapisanRute);
        if (d.geometri_survei) L.geoJSON(d.geometri_survei, { style: { color: '#2563eb', weight: 5, opacity: 0.85 } }).addTo(lapisanRute);
        L.circleMarker([k.lat, k.lng], { radius: 8, color: '#fff', weight: 2, fillColor: '#B46C54', fillOpacity: 1 }).addTo(lapisanRute).bindTooltip(k.nama, { permanent: true, direction: 'top', offset: [0, -8] });

        const batas = lapisanRute.getLayers().length ? L.featureGroup(lapisanRute.getLayers()).getBounds() : L.latLngBounds([[k.lat, k.lng]]);
        peta.fitBounds(batas.extend([KAMPUS.lat, KAMPUS.lng]), { padding: [40, 40], maxZoom: 18 });

        const hemat = d.selisih_m;
        infoEl.innerHTML = kotakInfo('Garis lurus', fmtM(d.garis_lurus_m))
            + kotakInfo('Rute OSM', fmtM(d.rute_osm_m))
            + kotakInfo('OSM + survei', fmtM(d.rute_survei_m), 'text-[#2563eb]')
            + kotakInfo('Lebih pendek', hemat ? fmtM(hemat) : 'Sama', hemat ? 'text-emerald-600' : 'text-slate-500');
    }

    pilihEl.addEventListener('change', () => { if (pilihEl.value) pilihKos(pilihEl.value); });
    document.querySelectorAll('[data-lihat-kos]').forEach(el => el.addEventListener('click', () => {
        pilihKos(el.dataset.lihatKos);
        document.getElementById('kartu-peta').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));

    // ---------- Isi tabel bertahap (3 kos sekaligus) ----------
    const statusEl = document.getElementById('status-tabel');
    const antrean = Object.keys(KOS);
    const total = antrean.length;
    let selesai = 0;

    function isiBaris(id, d) {
        const tr = document.querySelector(`tr[data-kos="${id}"]`);
        if (!tr) return;
        const isi = {
            rute_osm_m: fmtM(d.rute_osm_m),
            rute_survei_m: fmtM(d.rute_survei_m),
            selisih_m: d.selisih_m ? fmtM(d.selisih_m) : (d.selisih_m === 0 ? '0 m' : '-'),
            rasio_osm: fmtRasio(d.rasio_osm),
            rasio_survei: fmtRasio(d.rasio_survei),
        };
        Object.entries(isi).forEach(([kolom, teks]) => {
            const td = tr.querySelector(`[data-kolom="${kolom}"]`);
            td.textContent = teks;
            td.classList.remove('text-slate-400');
            if (kolom === 'selisih_m' && d.selisih_m > 0) td.classList.add('font-bold', 'text-emerald-600');
        });
    }

    async function pekerja() {
        while (antrean.length) {
            const id = antrean.shift();
            try {
                isiBaris(id, await ambil(id));
            } catch (e) {
                const tr = document.querySelector(`tr[data-kos="${id}"]`);
                tr?.querySelectorAll('[data-kolom]').forEach(td => td.textContent = 'gagal');
            }
            selesai++;
            statusEl.textContent = selesai < total ? `Menghitung rute ${selesai}/${total}...` : '';
        }
    }
    if (total) {
        statusEl.textContent = `Menghitung rute 0/${total}...`;
        for (let i = 0; i < Math.min(3, total); i++) pekerja();
    }

    setTimeout(() => peta.invalidateSize(), 200);
});
</script>
@endsection