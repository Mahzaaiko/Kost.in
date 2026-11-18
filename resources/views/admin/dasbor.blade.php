@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb', 'Admin / Dashboard')

@section('content')

{{-- Kartu statistik (Responsif di semua ukuran laptop & layar) --}}
<section class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 xl:gap-5">
    @foreach ($stats as $s)
        <div class="rounded-2xl border border-stone-200/90 bg-white p-4 sm:p-5 shadow-xs transition-all hover:border-brand/40 hover:shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <p class="text-xs sm:text-sm font-semibold text-stone-600 truncate">{{ $s['label'] }}</p>
                <span class="flex h-9 w-9 sm:h-10 sm:w-10 shrink-0 items-center justify-center rounded-xl {{ !empty($s['primary']) ? 'bg-brand text-white shadow-xs' : 'bg-brand-soft text-brand' }}">
                    @switch($s['icon'])
                        @case('building')
                            <svg class="h-4.5 w-4.5 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path stroke-linecap="round" d="M9 8h2m2 0h2M9 12h2m2 0h2M10 21v-4h4v4"/></svg>
                            @break
                        @case('check')
                            <svg class="h-4.5 w-4.5 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            @break
                        @case('sparkles')
                            <svg class="h-4.5 w-4.5 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/></svg>
                            @break
                        @default
                            <svg class="h-4.5 w-4.5 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.686 7-11a7 7 0 10-14 0c0 5.314 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    @endswitch
                </span>
            </div>
            <p class="mt-3 sm:mt-4 flex items-baseline gap-1.5">
                <span class="text-2xl sm:text-3xl font-extrabold tracking-tight text-ink">{{ $s['value'] }}</span>
                <span class="text-xs font-semibold text-muted">{{ $s['unit'] }}</span>
            </p>
            <p class="mt-1.5 sm:mt-2 text-[11px] sm:text-xs font-medium text-brand truncate">{{ $s['note'] }}</p>
        </div>
    @endforeach
</section>

{{-- Daftar kos & peta --}}
<section class="grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Data Kos Terbaru --}}
    <div class="overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-xs xl:col-span-2">
        <div class="flex items-center justify-between px-6 py-5">
            <div>
                <h2 class="text-base font-extrabold text-ink">Data Kos Terbaru</h2>
                <p class="mt-0.5 text-xs font-medium text-muted">Pembaruan data kos terakhir</p>
            </div>
            <a href="{{ route('admin.datakos') }}" class="flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-stone-600 transition hover:text-brand">
                <span>Lihat semua</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5l5 5-5 5"/></svg>
            </a>
        </div>

        <ul class="divide-y divide-stone-100 border-t border-stone-100">
            @forelse ($kosTerbaru as $kos)
                <li>
                    <a href="{{ route('admin.datakos.detail', $kos) }}" class="flex items-center gap-4 px-6 py-4 transition-colors hover:bg-stone-50/80">
                        @if ($kos->fotoUtama)
                            <img src="{{ $kos->fotoUtama->url() }}" alt="Foto {{ $kos->nama }}" class="h-11 w-11 shrink-0 rounded-xl object-cover border border-stone-200/60">
                        @else
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ink text-sm font-bold text-white">{{ mb_strtoupper(mb_substr($kos->nama, 0, 1)) }}</span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-ink">{{ $kos->nama }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted">{{ $kos->alamat ?? 'Alamat belum diisi' }}</p>
                        </div>
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-bold text-ink">{{ \App\Support\Format::rupiah($kos->harga_per_bulan) }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ $kos->updated_at?->diffForHumans() }}</p>
                        </div>
                        @if ($kos->status === \App\Enums\StatusKos::Aktif)
                            <span class="rounded-full bg-brand-soft border border-[#E8DDD5] px-3 py-1 text-xs font-bold text-brand">Aktif</span>
                        @else
                            <span class="rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-xs font-bold text-amber-700">Draft</span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-muted">Belum ada data kos.</li>
            @endforelse
        </ul>
    </div>

    {{-- Persebaran Kos --}}
    <div class="flex flex-col overflow-hidden rounded-2xl border border-stone-200/90 bg-white shadow-xs">
        <div class="px-6 py-5">
            <h2 class="text-base font-extrabold text-ink">Persebaran Kos</h2>
            <p class="mt-0.5 text-xs font-medium text-muted">Area sekitar kampus Sekolah Vokasi IPB</p>
        </div>

        {{-- Peta persebaran: semua kos yang sudah punya titik --}}
        <div id="peta-dasbor" class="relative z-0 min-h-[280px] xl:min-h-[320px] flex-1 border-t border-stone-100 bg-page"></div>

        <div class="border-t border-stone-100 px-6 py-3.5 text-xs font-medium text-stone-600 flex items-center justify-between">
            <div>
                <span class="font-extrabold text-ink">{{ $titikKos->count() }}</span> titik lokasi
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-brand"></span>Aktif</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>Draft</span>
            </div>
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
        icon: L.divIcon({ 
            className: '', 
            html: '<span style="display:flex;width:24px;height:24px;border-radius:8px;background:#362C29;color:white;align-items:center;justify-content:center;transform:translate(-50%,-50%);box-shadow:0 4px 8px rgba(0,0,0,.3);border:2px solid #fff;"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg></span>', 
            iconSize: [0, 0] 
        }),
        interactive: false, 
        keyboard: false
    }).addTo(peta).bindTooltip(KAMPUS.nama, { direction: 'top', offset: [0, -14] });

    const batas = [[KAMPUS.lat, KAMPUS.lng]];
    TITIK.forEach(t => {
        L.circleMarker([t.lat, t.lng], {
            radius: 7, 
            weight: 2, 
            color: '#ffffff',
            fillColor: t.aktif ? '#B8694C' : '#FBBF24', 
            fillOpacity: 1
        }).addTo(peta)
          .bindTooltip(t.nama + (t.aktif ? '' : ' (Draft)'), { direction: 'top' })
          .on('click', () => { window.location.href = t.url; });
        batas.push([t.lat, t.lng]);
    });
    if (batas.length > 1) peta.fitBounds(batas, { padding: [30, 30], maxZoom: 16 });
    setTimeout(() => peta.invalidateSize(), 250);
});
</script>
@endsection
