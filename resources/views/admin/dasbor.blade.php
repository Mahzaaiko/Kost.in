@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb', 'Admin / Dashboard')

@section('content')

{{-- Kartu statistik --}}
<section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($stats as $s)
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-600">{{ $s['label'] }}</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ !empty($s['primary']) ? 'bg-brand text-white' : 'bg-brand-soft text-brand' }}">
                    @switch($s['icon'])
                        @case('building')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path stroke-linecap="round" d="M9 8h2m2 0h2M9 12h2m2 0h2M10 21v-4h4v4"/></svg>
                            @break
                        @case('check')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            @break
                        @case('sparkles')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/></svg>
                            @break
                        @default
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.686 7-11a7 7 0 10-14 0c0 5.314 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    @endswitch
                </span>
            </div>
            <p class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold">{{ $s['value'] }}</span>
                <span class="text-xs font-medium text-muted">{{ $s['unit'] }}</span>
            </p>
            <p class="mt-2 text-xs font-medium text-brand-dark">{{ $s['note'] }}</p>
        </div>
    @endforeach
</section>

{{-- Daftar kos & peta --}}
<section class="grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Data Kos Terbaru --}}
    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm xl:col-span-2">
        <div class="flex items-center justify-between px-6 py-5">
            <div>
                <h2 class="text-base font-bold">Data Kos Terbaru</h2>
                <p class="mt-0.5 text-xs text-muted">Pembaruan data kos terakhir</p>
            </div>
            <a href="{{ route('admin.datakos') }}" class="flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-brand-dark">
                Lihat semua
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5l5 5-5 5"/></svg>
            </a>
        </div>

        <ul class="divide-y divide-stone-200 border-t border-stone-200">
            @forelse ($kosTerbaru as $kos)
                <li>
                    <a href="{{ route('admin.datakos.detail', $kos) }}" class="flex items-center gap-4 px-6 py-4 transition-colors hover:bg-stone-50">
                        @if ($kos->fotoUtama)
                            <img src="{{ $kos->fotoUtama->url() }}" alt="Foto {{ $kos->nama }}" class="h-11 w-11 shrink-0 rounded-md object-cover">
                        @else
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-[#5d4a41] text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($kos->nama, 0, 1)) }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold">{{ $kos->nama }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted">{{ $kos->alamat ?? 'Alamat belum diisi' }}</p>
                        </div>
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-bold">{{ \App\Support\Format::rupiah($kos->harga_per_bulan) }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ $kos->updated_at?->diffForHumans() }}</p>
                        </div>
                        @if ($kos->status === \App\Enums\StatusKos::Aktif)
                            <span class="rounded-full bg-brand-soft px-3 py-1.5 text-xs font-semibold text-brand-dark">Aktif</span>
                        @else
                            <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">Draft</span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-muted">Belum ada data kos.</li>
            @endforelse
        </ul>
    </div>

    {{-- Persebaran Kos --}}
    <div class="flex flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="px-6 py-5">
            <h2 class="text-base font-bold">Persebaran Kos</h2>
            <p class="mt-0.5 text-xs text-muted">Area sekitar kampus</p>
        </div>

        {{-- Peta persebaran: semua kos yang sudah punya titik --}}
        <div id="peta-dasbor" class="relative z-0 min-h-[260px] flex-1 border-t border-stone-200 bg-[#EFE4DF]"></div>

        <div class="border-t border-stone-200 px-6 py-4 text-xs text-slate-600">
            <span class="font-bold text-ink">{{ $titikKos->count() }}</span> titik lokasi
            <span class="ml-3 inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[#B46C54]"></span>Aktif</span>
            <span class="ml-2 inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Draft</span>
        </div>
    </div>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const KAMPUS = @js(['lat' => $kampus->lat, 'lng' => $kampus->lng, 'nama' => config('kostin.kampus.nama')]);
    const TITIK = @js($titikKos);

    const peta = L.map('peta-dasbor', { scrollWheelZoom: false }).setView([KAMPUS.lat, KAMPUS.lng], 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(peta);

    L.marker([KAMPUS.lat, KAMPUS.lng], {
        icon: L.divIcon({ className: '', html: '<span style="display:flex;width:22px;height:22px;border-radius:6px;background:#1c1917;transform:translate(-50%,-50%);box-shadow:0 1px 4px rgba(0,0,0,.4)"></span>', iconSize: [0, 0] }),
        interactive: false, keyboard: false
    }).addTo(peta).bindTooltip(KAMPUS.nama, { direction: 'top', offset: [0, -12] });

    const batas = [[KAMPUS.lat, KAMPUS.lng]];
    TITIK.forEach(t => {
        L.circleMarker([t.lat, t.lng], {
            radius: 7, weight: 2, color: '#ffffff',
            fillColor: t.aktif ? '#B46C54' : '#fbbf24', fillOpacity: 1
        }).addTo(peta)
          .bindTooltip(t.nama + (t.aktif ? '' : ' (Draft)'), { direction: 'top' })
          .on('click', () => { window.location.href = t.url; });
        batas.push([t.lat, t.lng]);
    });
    if (batas.length > 1) peta.fitBounds(batas, { padding: [30, 30], maxZoom: 16 });
    setTimeout(() => peta.invalidateSize(), 200);
});
</script>
@endsection