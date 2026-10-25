@extends('layouts.admin')

@section('title', 'Detail Data Kos')
@section('page_title', 'Detail Data Kos')
@section('breadcrumb', 'Admin / Detail Data Kos')

@section('content')
{{-- Wrapper Utama Alpine.js untuk Modal Hapus --}}
<div x-data="{ isDeleteModalOpen: false }">

    {{-- ================= HEADER ================= --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.datakos') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition-colors hover:text-brand">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Kembali ke Data Kos
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-ink">Detail Data Kos</h2>
                @if ($kos->status === \App\Enums\StatusKos::Aktif)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700 ring-1 ring-inset ring-green-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>
                        Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Draft
                    </span>
                @endif
            </div>
            <p class="mt-1.5 text-sm text-slate-500">Pratinjau informasi yang tampil pada website{{ $kos->status === \App\Enums\StatusKos::Draft ? ' setelah kos diaktifkan' : '' }}.</p>
        </div>

        <div class="flex w-full items-center gap-3 sm:w-auto">
            {{-- Tombol Hapus (Trigger Modal) --}}
            <button 
                @click="isDeleteModalOpen = true"
                type="button" 
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 sm:w-auto"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Data
            </button>
            
            {{-- Tombol Edit --}}
            <a 
                href="{{ route('admin.datakos.edit', $kos) }}" 
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#955844] sm:w-auto"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Data
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif
    @if ($kekurangan !== [])
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span class="font-bold">Belum bisa diaktifkan.</span> Lengkapi dulu: {{ implode(', ', $kekurangan) }}.
        </div>
    @endif

    {{-- ================= KONTEN UTAMA ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        
        {{-- KOLOM KIRI (Foto, Harga, Deskripsi) - Proporsi 60% --}}
        <div class="space-y-6 lg:col-span-3">
            
            {{-- Card Foto & Info Utama --}}
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                
                {{-- Alpine.js Slider Foto --}}
                <div 
                    x-data="{ 
                        activeSlide: 1, 
                        slides: @js($kos->foto->map(fn ($f) => $f->url())->values()) 
                    }" 
                    class="relative aspect-[4/3] w-full bg-stone-100 group"
                >
                    {{-- Gambar --}}
                    <template x-for="(slide, index) in slides" :key="index">
                        <img 
                            x-show="activeSlide === index + 1"
                            :src="slide" 
                            x-transition:enter="transition duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="absolute inset-0 h-full w-full object-cover"
                            alt="Foto Kos"
                        >
                    </template>

                    {{-- Placeholder kalau belum ada foto --}}
                    <div x-show="slides.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                        <span class="mt-2 text-sm font-semibold">Belum ada foto</span>
                    </div>

                    {{-- Tombol Kiri --}}
                    <button x-show="slides.length > 1" type="button" 
                        @click="activeSlide = activeSlide > 1 ? activeSlide - 1 : slides.length" 
                        class="absolute left-4 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/30 text-white backdrop-blur-md transition hover:bg-white hover:text-slate-800 opacity-0 group-hover:opacity-100"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    {{-- Tombol Kanan --}}
                    <button x-show="slides.length > 1" type="button" 
                        @click="activeSlide = activeSlide < slides.length ? activeSlide + 1 : 1" 
                        class="absolute right-4 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/30 text-white backdrop-blur-md transition hover:bg-white hover:text-slate-800 opacity-0 group-hover:opacity-100"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    {{-- Counter Badge --}}
                    <div x-show="slides.length > 0" class="absolute bottom-4 right-4 flex items-center gap-2 rounded-lg bg-black/60 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-md">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="activeSlide"></span> dari <span x-text="slides.length"></span> foto
                    </div>
                </div>

                {{-- Detail Teks Utama --}}
                <div class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-extrabold text-ink">{{ $kos->nama }}</h1>
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                                <svg class="h-4 w-4 text-brand" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $kos->alamat ?? 'Alamat belum diisi' }}
                            </p>
                        </div>
                        <span class="inline-flex shrink-0 whitespace-nowrap rounded-lg bg-brand-soft px-3 py-1.5 text-xs font-bold text-brand-dark">{{ $kos->tipe ? 'Kos '.$kos->tipe->label() : 'Tipe belum diisi' }}</span>
                    </div>

                    <hr class="my-6 border-stone-100">

                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">Harga per bulan</p>
                            <p class="mt-1 text-3xl font-extrabold text-[#c57b5f]">{{ \App\Support\Format::rupiah($kos->harga_per_bulan) }}</p>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-xs">
                            <dt class="text-slate-500">Minimal sewa</dt><dd class="font-semibold text-ink">{{ $kos->labelPeriode() ?? '-' }}</dd>
                            <dt class="text-slate-500">No. telepon</dt><dd class="font-semibold text-ink">{{ $kos->no_telepon ?? '-' }}</dd>
                            <dt class="text-slate-500">Dilihat</dt><dd class="font-semibold text-ink">{{ number_format($kos->jumlah_dilihat, 0, ',', '.') }} kali</dd>
                        </dl>
                    </div>
                </div>
            </div>

            @if ($kos->catatan_import)
            {{-- Catatan dari import spreadsheet: hal yang perlu dicek admin --}}
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-amber-800">Catatan import spreadsheet</h3>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-xs leading-relaxed text-amber-800">
                    @foreach (explode("\n", $kos->catatan_import) as $catatan)
                        <li class="break-words">{{ $catatan }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Card Deskripsi --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-6 sm:p-8 shadow-sm">
                <h3 class="text-base font-bold text-ink">Deskripsi</h3>
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $kos->deskripsi ?? 'Belum ada deskripsi.' }}</p>
            </div>
        </div>

        {{-- KOLOM KANAN (Fasilitas & Lokasi) - Proporsi 40% --}}
        <div class="space-y-6 lg:col-span-2">
            
            {{-- Fasilitas (Dengan Logo Khusus) --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 text-base font-bold text-ink">Fasilitas</h3>
                <div class="flex flex-col gap-3">
                    @forelse ($kos->fasilitas as $fas)
                    <div class="flex items-center gap-4 rounded-xl bg-white px-1 py-1 transition">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#c57b5f]">
                            <x-ikon-fasilitas :ikon="$fas->ikon" />
                        </span>
                        <span class="text-sm font-bold text-slate-700">{{ $fas->nama }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-slate-500">Belum ada fasilitas yang dipilih.</p>
                    @endforelse
                </div>
            </div>

            {{-- Lokasi & Map --}}
            <div class="rounded-2xl border border-stone-200 bg-white shadow-sm overflow-hidden sticky top-24">
                <div class="p-6 pb-4">
                    <h3 class="text-base font-bold text-ink">Lokasi Kos</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $jarakM !== null ? \App\Support\Format::jarak($jarakM).' dari kampus (garis lurus)' : 'Titik lokasi belum ditentukan' }}</p>
                </div>
                
                {{-- Peta: titik kos dan kampus --}}
                <div id="peta-detail" class="relative z-0 h-56 w-full bg-[#EFE4DF]"></div>

                <div class="flex border-t border-stone-200">
                    <div class="flex-1 border-r border-stone-200 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Latitude</p>
                        <p class="mt-0.5 font-mono text-sm font-bold text-ink">{{ $kos->lokasi ? number_format($kos->lokasi->lat, 7, '.', '') : '-' }}</p>
                    </div>
                    <div class="flex-1 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Longitude</p>
                        <p class="mt-0.5 font-mono text-sm font-bold text-ink">{{ $kos->lokasi ? number_format($kos->lokasi->lng, 7, '.', '') : '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL HAPUS DATA ================= --}}
    <div 
        x-show="isDeleteModalOpen" 
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center"
    >
        {{-- Overlay Background Blur --}}
        <div 
            x-show="isDeleteModalOpen"
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm"
            @click="isDeleteModalOpen = false"
        ></div>

        {{-- Modal Content --}}
        <div 
            x-show="isDeleteModalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative mx-4 w-full max-w-lg overflow-hidden rounded-2xl bg-white text-left shadow-2xl"
        >
            <div class="border-b border-stone-100 px-6 py-5">
                <h3 class="text-lg font-bold text-ink">Hapus Data Kos?</h3>
            </div>

            <div class="px-6 py-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-ink">{{ $kos->nama }}</h4>
                        <p class="mt-1 text-sm text-slate-500">Apakah kamu yakin ingin menghapus data ini? Data kos tidak akan ditampilkan kembali pada website.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                <button 
                    @click="isDeleteModalOpen = false" 
                    type="button" 
                    class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800"
                >
                    Batal
                </button>
                <form method="POST" action="{{ route('admin.datakos.hapus', $kos) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-700 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const KAMPUS = @js(['lat' => $kampus->lat, 'lng' => $kampus->lng, 'nama' => config('kostin.kampus.nama')]);
    const KOS = @js($kos->lokasi?->toArray());

    const peta = L.map('peta-detail', { scrollWheelZoom: false, attributionControl: true });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(peta);

    L.marker([KAMPUS.lat, KAMPUS.lng], {
        icon: L.divIcon({ className: '', html: '<span style="display:flex;width:22px;height:22px;border-radius:6px;background:#1c1917;transform:translate(-50%,-50%);box-shadow:0 1px 4px rgba(0,0,0,.4)"></span>', iconSize: [0, 0] }),
        interactive: false, keyboard: false
    }).addTo(peta).bindTooltip(KAMPUS.nama, { direction: 'top', offset: [0, -12] });

    if (KOS) {
        L.marker([KOS.lat, KOS.lng]).addTo(peta);
        L.polyline([[KAMPUS.lat, KAMPUS.lng], [KOS.lat, KOS.lng]], { color: '#c57b5f', weight: 2, dashArray: '4 6' }).addTo(peta);
        peta.fitBounds([[KAMPUS.lat, KAMPUS.lng], [KOS.lat, KOS.lng]], { padding: [30, 30], maxZoom: 17 });
    } else {
        peta.setView([KAMPUS.lat, KAMPUS.lng], 15);
    }
    setTimeout(() => peta.invalidateSize(), 200);
});
</script>
@endsection