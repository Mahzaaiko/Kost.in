@extends('layouts.pengguna')

@section('title', 'Jelajah Kos | Kost.in')
@section('body_class', 'bg-[#f7f5f2]')

{{-- Atur lebar navbar khusus halaman ini agar selebar konten --}}
@section('nav_width', 'max-w-[1600px]')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; background: #ece8e3; }
        .leaflet-popup-content-wrapper { border-radius: 10px; }
        .scroll-thin::-webkit-scrollbar { width: 6px; }
        .scroll-thin::-webkit-scrollbar-thumb { background: #d6d3d1; border-radius: 6px; }
    </style>
@endpush

@section('content')
@php
    // Data berasal dari JelajahController; daftar kos diambil lewat API /api/kos.
    $fmtRingkas = fn ($n) => $n >= 1_000_000 ? 'Rp '.rtrim(rtrim(number_format($n / 1_000_000, 1, ',', '.'), '0'), ',').'jt' : 'Rp '.number_format($n / 1000, 0, ',', '.').'rb';
@endphp

    <main class="mx-auto grid max-w-[1600px] gap-4 p-3 sm:gap-5 sm:p-5 lg:h-[calc(100vh-69px)] lg:grid-cols-[300px_minmax(0,1fr)_330px]">

        {{-- ========== KOLOM KIRI: FILTER + DAFTAR ========== --}}
        <aside class="scroll-thin order-3 flex min-h-0 flex-col gap-5 lg:order-none lg:overflow-y-auto lg:pr-1">

            {{-- Filter --}}
            <section class="shrink-0 rounded-2xl border border-stone-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-stone-900">Filter Pencarian</h2>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#fbf5f3] text-[#B46C54]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4z"/></svg>
                    </span>
                </div>

                {{-- Harga --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs">
                        <label for="f-harga" class="font-semibold text-stone-800">Harga per bulan</label>
                        <span id="f-harga-label" class="rounded-md bg-[#fbf5f3] px-2 py-1 font-semibold text-[#B46C54]">Rp {{ number_format($hargaMax, 0, ',', '.') }}</span>
                    </div>
                    <input id="f-harga" type="range" min="{{ $hargaMin }}" max="{{ $hargaMax }}" step="50000" value="{{ $hargaMax }}" class="mt-3 w-full accent-[#B46C54]">
                    <div class="flex justify-between text-[11px] text-stone-500"><span>{{ $fmtRingkas($hargaMin) }}</span><span>{{ $fmtRingkas($hargaMax) }}</span></div>
                </div>

                {{-- Jarak --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs">
                        <label for="f-jarak" class="font-semibold text-stone-800">Jarak ke Kampus</label>
                        <span id="f-jarak-label" class="rounded-md bg-[#fbf5f3] px-2 py-1 font-semibold text-[#B46C54]">{{ $jarakMaks }} km</span>
                    </div>
                    <input id="f-jarak" type="range" min="0.5" max="{{ $jarakMaks }}" step="0.5" value="{{ $jarakMaks }}" class="mt-3 w-full accent-[#B46C54]">
                    <div class="flex justify-between text-[11px] text-stone-500"><span>0,5 km</span><span>{{ $jarakMaks }} km</span></div>
                </div>

                {{-- Tipe --}}
                <div class="mt-4">
                    <p class="text-xs font-semibold text-stone-800">Tipe Kos</p>
                    <div id="dd-tipe" class="dd relative mt-2" data-value="Semua">
                        <button type="button" class="dd-btn flex w-full items-center justify-between rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-700 transition hover:border-stone-300 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" aria-haspopup="listbox">
                            <span class="flex items-center gap-2"><span class="dd-dot h-2 w-2 rounded-full bg-[#B46C54]"></span><span class="dd-label">Semua Tipe</span></span>
                            <svg class="dd-chev h-4 w-4 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <ul class="dd-menu absolute z-30 mt-1.5 hidden w-full overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lg" role="listbox">
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2.5 text-sm transition-colors" data-value="Semua" data-label="Semua Tipe" role="option"><span class="flex items-center gap-2.5"><span class="dd-dot h-2 w-2 rounded-full bg-[#B46C54]"></span>Semua Tipe</span><svg class="dd-check hidden h-4 w-4 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2.5 text-sm transition-colors" data-value="Putri" data-label="Kos Putri" role="option"><span class="flex items-center gap-2.5"><span class="dd-dot h-2 w-2 rounded-full bg-pink-400"></span>Kos Putri</span><svg class="dd-check hidden h-4 w-4 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2.5 text-sm transition-colors" data-value="Putra" data-label="Kos Putra" role="option"><span class="flex items-center gap-2.5"><span class="dd-dot h-2 w-2 rounded-full bg-sky-400"></span>Kos Putra</span><svg class="dd-check hidden h-4 w-4 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2.5 text-sm transition-colors" data-value="Campur" data-label="Kos Campur" role="option"><span class="flex items-center gap-2.5"><span class="dd-dot h-2 w-2 rounded-full bg-purple-400"></span>Kos Campur</span><svg class="dd-check hidden h-4 w-4 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                        </ul>
                    </div>
                </div>

                {{-- Fasilitas --}}
                <div class="mt-4">
                    <p class="text-xs font-semibold text-stone-800">Fasilitas</p>
                    <div class="mt-2 grid grid-cols-2 gap-x-3 gap-y-2">
                        @forelse ($fasilitasFilter as $fas)
                            <label class="flex cursor-pointer items-start gap-2 text-xs text-stone-600">
                                <input type="checkbox" value="{{ $fas->id }}" class="f-fasilitas mt-0.5 h-4 w-4 shrink-0 rounded border-stone-300 accent-[#B46C54]">
                                <span>{{ $fas->nama }}</span>
                            </label>
                        @empty
                            <p class="col-span-2 text-xs text-stone-400">Belum ada data fasilitas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <button type="button" id="btn-reset" class="rounded-lg border border-[#B46C54] bg-white py-2 text-xs font-bold text-[#B46C54] transition hover:bg-[#fbf5f3]">Reset</button>
                    <button type="button" id="btn-terapkan" class="rounded-lg bg-[#B46C54] py-2 text-xs font-bold text-white transition hover:bg-[#955844]">Terapkan</button>
                </div>
            </section>

            {{-- Daftar kos --}}
            <section class="rounded-2xl border border-stone-200 bg-white p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm text-stone-600"><strong id="jumlah-kos" class="text-stone-900">0</strong> Kos ditemukan</p>
                    <div id="dd-urut" class="dd relative" data-value="rekomendasi">
                        <button type="button" class="dd-btn flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-700 transition hover:border-stone-300 focus:border-[#B46C54] focus:outline-none" aria-haspopup="listbox">
                            <span class="dd-label">Rekomendasi</span>
                            <svg class="dd-chev h-3.5 w-3.5 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <ul class="dd-menu absolute right-0 z-30 mt-1.5 hidden w-40 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lg" role="listbox">
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="rekomendasi" data-label="Rekomendasi" role="option"><span>Rekomendasi</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="jarak" data-label="Terdekat" role="option"><span>Terdekat</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="murah" data-label="Termurah" role="option"><span>Termurah</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="mahal" data-label="Termahal" role="option"><span>Termahal</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                        </ul>
                    </div>
                </div>
                <ul id="daftar-kos" class="mt-3 space-y-2.5"></ul>
                <p id="kosong" class="hidden py-6 text-center text-sm text-stone-500">Tidak ada kos yang cocok dengan filter.</p>
                <p id="ket-skor" class="mt-3 hidden text-[11px] leading-relaxed text-stone-400">Skor rekomendasi (0 sampai 1) dihitung dari jarak ke kampus (50%), harga per bulan (30%), dan jumlah fasilitas (20%), relatif terhadap kos yang tampil.</p>
            </section>
        </aside>

        {{-- ========== TENGAH: PETA ========== --}}
        <section class="relative isolate order-1 h-[460px] overflow-hidden rounded-2xl border border-stone-200 sm:h-[540px] lg:order-none lg:h-full">
            <div id="peta" class="absolute inset-0 z-0"></div>

            {{-- Pencarian + chip tipe --}}
            <div id="overlay-atas" class="absolute left-4 top-4 z-[1000] w-[calc(100%-6.5rem)] max-w-sm">
                <div class="relative">
                    <input id="f-cari" type="text" value="{{ $awal['q'] }}" maxlength="100" placeholder="Cari nama kos atau alamat..." class="block w-full rounded-xl border border-stone-200 bg-white py-3 pl-4 pr-10 text-sm shadow-sm placeholder:text-stone-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">
                    <svg class="pointer-events-none absolute right-3 top-3.5 h-4 w-4 text-stone-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m0 0A7.5 7.5 0 105.2 5.2a7.5 7.5 0 0010.6 10.6z"/></svg>
                </div>
                <div class="mt-3 flex flex-wrap gap-2" id="chip-tipe">
                    <button type="button" data-tipe="Semua" class="chip flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold shadow-sm transition"><span class="h-2 w-2 rounded-full bg-white chip-dot"></span>Semua</button>
                    <button type="button" data-tipe="Putri" class="chip flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold shadow-sm transition"><span class="h-2 w-2 rounded-full bg-pink-400"></span>Kos Putri</button>
                    <button type="button" data-tipe="Putra" class="chip flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold shadow-sm transition"><span class="h-2 w-2 rounded-full bg-sky-400"></span>Kos Putra</button>
                    <button type="button" data-tipe="Campur" class="chip flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold shadow-sm transition"><span class="h-2 w-2 rounded-full bg-purple-400"></span>Campur</button>
                </div>
            </div>

            {{-- Legenda --}}
            <div id="overlay-legenda" class="absolute bottom-4 left-4 z-[1000] hidden rounded-xl border border-stone-200 bg-white p-3 text-xs shadow-sm sm:block">
                <p class="mb-2 font-bold text-stone-900">Legenda</p>
                <ul class="space-y-1.5 text-stone-600">
                    <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-pink-400"></span>Kos Putri</li>
                    <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-400"></span>Kos Putra</li>
                    <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-purple-400"></span>Kos Campur</li>
                    <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-stone-900"></span>Kampus</li>
                </ul>
            </div>
        </section>

        {{-- ========== KANAN: DETAIL ========== --}}
        <aside class="scroll-thin order-2 min-h-0 overflow-hidden rounded-2xl border border-stone-200 bg-white lg:order-none lg:overflow-y-auto">
            <div id="detail-kosong" class="flex h-full min-h-[240px] items-center justify-center p-8 text-center text-sm text-stone-500">
                Pilih kos di daftar atau di peta untuk melihat detailnya.
            </div>

            <div id="detail" class="hidden">
                {{-- Galeri foto: panah < > + tombol perbesar --}}
                <div id="galeri" class="group relative aspect-[16/10] w-full overflow-hidden bg-stone-100">
                    <img id="d-foto-utama" src="" alt="" class="h-full w-full cursor-zoom-in select-none object-cover">

                    <button id="g-prev" type="button" aria-label="Foto sebelumnya" class="absolute left-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow transition hover:bg-white hover:text-[#B46C54]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button id="g-next" type="button" aria-label="Foto berikutnya" class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow transition hover:bg-white hover:text-[#B46C54]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    <button id="g-zoom" type="button" aria-label="Perbesar foto" class="absolute right-2 top-2 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow transition hover:bg-white hover:text-[#B46C54]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                    </button>

                    <span id="g-hitung" class="absolute bottom-2 left-2 rounded-full bg-stone-900/70 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-sm"></span>
                </div>

                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <h3 id="d-nama" class="text-lg font-bold leading-tight text-stone-900"></h3>
                        <span id="d-tipe" class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold"></span>
                    </div>
                    <p class="mt-3 text-xl font-extrabold text-[#B46C54]"><span id="d-harga"></span> <span class="text-xs font-medium text-stone-500">/ bulan</span></p>
                    <p id="d-alamat" class="mt-3 text-xs leading-relaxed text-stone-500"></p>
                    <p id="d-jarak" class="mt-1 text-xs text-stone-500"></p>

                    <hr class="my-4 border-stone-200">

                    <p id="d-deskripsi" class="hidden whitespace-pre-line text-xs leading-relaxed text-stone-600"></p>
                    <hr id="d-deskripsi-hr" class="my-4 hidden border-stone-200">

                    <p class="text-xs font-bold text-stone-900">Fasilitas</p>
                    <div id="d-fasilitas" class="mt-3 grid grid-cols-4 gap-x-2 gap-y-3"></div>

                    <hr class="my-4 border-stone-200">

                    <p class="text-xs font-bold text-stone-900">Detail</p>
                    <dl class="mt-3 space-y-2 text-xs">
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">Tipe Kos</dt><dd id="d-tipe-teks" class="font-semibold text-stone-800"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">Minimal sewa</dt><dd id="d-periode" class="font-semibold text-stone-800"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">No. telepon</dt><dd id="d-telepon" class="font-semibold text-stone-800"></dd></div>
                    </dl>

                    <button id="d-rute" type="button" class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg border border-[#B46C54] bg-[#B46C54] py-3 text-sm font-bold text-white transition hover:bg-[#955844]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.7 16.7L13.4 21a2 2 0 01-2.8 0l-4.3-4.3a8 8 0 1111.4 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span id="d-rute-teks">Lihat Rute</span>
                    </button>
                    <p id="d-rute-info" class="mt-2 hidden text-center text-[11px] leading-relaxed text-stone-500"></p>
                </div>
            </div>
        </aside>
    </main>

    {{-- ========== LIGHTBOX FOTO (pop up) ========== --}}
    <div id="lightbox" class="fixed inset-0 z-[5000] hidden flex-col bg-black/90 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Foto kos">
        <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6 sm:py-4">
            <div class="min-w-0 text-white">
                <p id="lb-nama" class="truncate text-sm font-bold sm:text-base"></p>
                <p id="lb-hitung" class="text-xs text-white/70"></p>
            </div>
            <button id="lb-tutup" type="button" aria-label="Tutup" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/25 sm:h-11 sm:w-11">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div id="lb-area" class="relative flex min-h-0 flex-1 items-center justify-center px-2 sm:px-16">
            <button id="lb-prev" type="button" aria-label="Foto sebelumnya" class="absolute left-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/30 sm:left-5 sm:h-12 sm:w-12">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <img id="lb-foto" src="" alt="" class="max-h-full max-w-full select-none rounded-lg object-contain">
            <button id="lb-next" type="button" aria-label="Foto berikutnya" class="absolute right-2 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-white/30 sm:right-5 sm:h-12 sm:w-12">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>

        <div id="lb-dots" class="flex items-center justify-center gap-2 px-4 py-4 sm:py-5"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const KAMPUS = @json($kampus);
        const IKON = @json($ikonSvg);
        const HARGA_MAX = {{ $hargaMax }};
        const JARAK_MAKS = {{ $jarakMaks }};
        const AWAL = @json($awal);
        const URL_API = @json(route('api.kos.index'));
        const URL_DILIHAT = id => @json(url('/api/kos')) + '/' + id + '/dilihat';
        const URL_RUTE = id => @json(url('/api/kos')) + '/' + id + '/rute';
        const WARNA = { 'Putri': '#f472b6', 'Putra': '#38bdf8', 'Campur': '#a78bfa' };
        const BADGE = {
            'Putri':  'border-pink-300 text-pink-600',
            'Putra':  'border-sky-300 text-sky-600',
            'Campur': 'border-purple-300 text-purple-600'
        };
        const COKLAT = '#B46C54';
        const RING_KM = [1, 3, 5, 10].filter(km => km <= JARAK_MAKS);

        const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');
        const jarakTeks = m => m < 1000 ? (Math.round(m / 10) * 10) + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km';
        const skorTeks = s => s === null || s === undefined ? '' : Number(s).toFixed(2).replace('.', ',');
        const $ = id => document.getElementById(id);
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        // ---------- Peta ----------
        const map = L.map('peta', { zoomControl: false }).setView([KAMPUS.lat, KAMPUS.lng], 13);
        L.control.zoom({ position: 'topright' }).addTo(map);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        ['overlay-atas', 'overlay-legenda'].forEach(id => {
            const el = $(id);
            L.DomEvent.disableClickPropagation(el);
            L.DomEvent.disableScrollPropagation(el);
        });

        // Buffer radius + label
        RING_KM.forEach(km => {
            L.circle([KAMPUS.lat, KAMPUS.lng], {
                radius: km * 1000, color: COKLAT, weight: 2, opacity: 0.55, dashArray: '10 8',
                fillColor: COKLAT, fillOpacity: 0.03, interactive: false
            }).addTo(map);
            L.marker([KAMPUS.lat + (km * 1000) / 111320, KAMPUS.lng], {
                icon: L.divIcon({
                    className: '',
                    html: '<span style="display:inline-block;transform:translate(-50%,-50%);background:#fff;color:#955844;font:700 12px/1 \'Plus Jakarta Sans\',sans-serif;padding:5px 9px;border-radius:8px;border:1px solid #ead9d2;box-shadow:0 1px 3px rgba(0,0,0,.12);white-space:nowrap">' + km + ' km</span>',
                    iconSize: [0, 0]
                }),
                interactive: false, keyboard: false
            }).addTo(map);
        });

        // Kampus
        L.marker([KAMPUS.lat, KAMPUS.lng], {
            icon: L.divIcon({
                className: '',
                html: '<div style="transform:translate(-50%,-50%);display:flex;flex-direction:column;align-items:center;gap:4px">' +
                      '<span style="display:flex;width:36px;height:36px;border-radius:10px;background:#1c1917;color:#fff;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.35)">' +
                      '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 21V9l8-5 8 5v12M9 21v-6h6v6"/></svg></span>' +
                      '<span style="background:#1c1917;color:#fff;font:700 11px/1 \'Plus Jakarta Sans\',sans-serif;padding:5px 8px;border-radius:6px;white-space:nowrap">' + esc(KAMPUS.nama) + '</span></div>',
                iconSize: [0, 0]
            }),
            zIndexOffset: 1000, keyboard: false
        }).addTo(map);

        // ---------- State ----------
        const state = {
            harga: HARGA_MAX, jarak: JARAK_MAKS, tipe: 'Semua', fasilitas: [], cari: AWAL.q || '',
            urut: 'rekomendasi', terpilih: null, rute: false
        };
        let daftar = [];            // hasil terakhir dari API
        let permintaan = 0;         // untuk mengabaikan respons lama
        const sudahDihitung = new Set();
        const markers = {};
        const layerKos = L.layerGroup().addTo(map);
        const cariKos = id => daftar.find(x => x.id === id);

        function ikonKos(k, aktif) {
            const w = WARNA[k.tipe] || COKLAT;
            const size = aktif ? 40 : 32;
            return L.divIcon({
                className: '',
                html: '<span style="display:flex;width:' + size + 'px;height:' + size + 'px;border-radius:50%;background:#fff;align-items:center;justify-content:center;transform:translate(-50%,-50%);' +
                      'border:' + (aktif ? '3px solid ' + COKLAT : '2px solid #fff') + ';box-shadow:' + (aktif ? '0 0 0 4px rgba(180,108,84,.25),' : '') + '0 2px 6px rgba(0,0,0,.25)">' +
                      '<svg width="' + (size - 14) + '" height="' + (size - 14) + '" viewBox="0 0 24 24" fill="' + w + '"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.3 7 13 7 13s7-7.7 7-13c0-3.9-3.1-7-7-7zm0 9.5A2.5 2.5 0 1112 6.5a2.5 2.5 0 010 5z"/></svg></span>',
                iconSize: [0, 0]
            });
        }

        // ---------- Ambil data dari API ----------
        async function muat(opsi) {
            const nomor = ++permintaan;
            const params = new URLSearchParams();
            if (state.harga < HARGA_MAX) params.set('harga_max', state.harga);
            if (state.jarak < JARAK_MAKS) params.set('jarak_max_km', state.jarak);
            if (state.tipe !== 'Semua') params.set('tipe', state.tipe.toLowerCase());
            state.fasilitas.forEach(id => params.append('fasilitas[]', id));
            if (state.cari.trim()) params.set('q', state.cari.trim());
            params.set('urut', state.urut);

            $('kosong').textContent = 'Memuat data kos...';
            $('kosong').classList.remove('hidden');
            try {
                const res = await fetch(URL_API + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (nomor !== permintaan) return; // sudah ada permintaan yang lebih baru
                if (!res.ok) throw new Error(json.message || ('HTTP ' + res.status));
                daftar = json.data;
                $('kosong').textContent = 'Tidak ada kos yang cocok dengan filter.';
                render(opsi);
            } catch (err) {
                if (nomor !== permintaan) return;
                daftar = [];
                render();
                $('kosong').textContent = 'Gagal memuat data kos. Periksa koneksi lalu coba lagi.';
                $('kosong').classList.remove('hidden');
            }
        }

        // ---------- Render ----------
        function render(opsi) {
            $('jumlah-kos').textContent = daftar.length;
            $('kosong').classList.toggle('hidden', daftar.length > 0);
            $('ket-skor').classList.toggle('hidden', state.urut !== 'rekomendasi' || daftar.length === 0);

            if (opsi && opsi.pilih && cariKos(opsi.pilih)) state.terpilih = opsi.pilih;
            if (state.terpilih && !cariKos(state.terpilih)) { state.terpilih = null; state.rute = false; }
            if (!state.terpilih && daftar.length && !(opsi && opsi.tanpaPilihOtomatis)) state.terpilih = daftar[0].id;

            // Daftar
            const ul = $('daftar-kos');
            ul.innerHTML = '';
            daftar.forEach((k, i) => {
                const li = document.createElement('li');
                const aktif = k.id === state.terpilih;
                const skor = state.urut === 'rekomendasi'
                    ? '<span class="ml-auto shrink-0 rounded-md bg-[#fbf5f3] px-1.5 py-0.5 text-[10px] font-bold text-[#B46C54]" title="Skor rekomendasi">#' + (i + 1) + ' · ' + skorTeks(k.skor) + '</span>'
                    : '';
                li.innerHTML =
                    '<button type="button" class="flex w-full gap-3 rounded-xl border p-2 text-left transition ' +
                    (aktif ? 'border-[#B46C54] bg-[#fbf5f3]' : 'border-stone-200 bg-white hover:border-stone-300') + '">' +
                    '<img src="' + esc(k.fotos[0] || '') + '" alt="" loading="lazy" class="h-[72px] w-[72px] shrink-0 rounded-lg bg-stone-100 object-cover">' +
                    '<span class="min-w-0 flex-1">' +
                    '<span class="flex items-start gap-2"><span class="block truncate text-sm font-bold text-stone-900">' + esc(k.nama) + '</span>' + skor + '</span>' +
                    '<span class="mt-0.5 block text-xs font-bold text-[#B46C54]">' + rupiah(k.harga) + ' <span class="font-medium text-stone-500">/ bulan</span></span>' +
                    '<span class="mt-0.5 block text-xs text-stone-500">' + jarakTeks(k.jarak_m) + ' dari kampus</span>' +
                    '<span class="mt-1 block truncate text-[10px] text-stone-500">' + esc(k.fasilitas.slice(0, 3).map(f => f.nama).join(' · ')) + '</span>' +
                    '</span></button>';
                li.firstChild.addEventListener('click', () => pilih(k.id, true));
                ul.appendChild(li);
            });

            // Marker
            layerKos.clearLayers();
            Object.keys(markers).forEach(id => delete markers[id]);
            daftar.forEach(k => {
                const m = L.marker([k.lat, k.lng], { icon: ikonKos(k, k.id === state.terpilih), zIndexOffset: k.id === state.terpilih ? 900 : 0 });
                m.on('click', () => pilih(k.id, false));
                m.bindTooltip(esc(k.nama) + ' · ' + jarakTeks(k.jarak_m), { direction: 'top', offset: [0, -18] });
                markers[k.id] = m;
                layerKos.addLayer(m);
            });

            tampilkanGarisTerpilih();

            // Chip tipe
            document.querySelectorAll('.chip').forEach(c => {
                const on = c.dataset.tipe === state.tipe;
                c.classList.toggle('bg-[#B46C54]', on);
                c.classList.toggle('text-white', on);
                c.classList.toggle('border-[#B46C54]', on);
                c.classList.toggle('bg-white', !on);
                c.classList.toggle('text-stone-700', !on);
                c.classList.toggle('border-stone-200', !on);
            });

            tampilkanDetail();

            if (opsi && opsi.fit) fitKeFilter();
            if (opsi && opsi.pilih && cariKos(opsi.pilih)) {
                const k = cariKos(opsi.pilih);
                map.flyTo([k.lat, k.lng], Math.max(map.getZoom(), 15), { duration: 0.6 });
            }
        }

        // ---------- Rute jalan kaki ----------
        // Rute diambil dari API (pgRouting) dan disimpan per kos selama halaman terbuka.
        // Bila rute tidak tersedia, ditampilkan garis lurus sebagai cadangan.
        const ruteKos = {};          // id -> hasil API, atau { gagal: true }
        let garisTerpilih = null;

        const labelPeta = teks => L.divIcon({
            className: '',
            html: '<span style="display:inline-block;transform:translate(-50%,-50%);background:#fff;color:#955844;font:700 11px/1 \'Plus Jakarta Sans\',sans-serif;padding:5px 8px;border-radius:8px;border:1px solid #ead9d2;box-shadow:0 1px 3px rgba(0,0,0,.15);white-space:nowrap">' + esc(teks) + '</span>',
            iconSize: [0, 0]
        });

        function infoRute(teks) {
            $('d-rute-info').textContent = teks || '';
            $('d-rute-info').classList.toggle('hidden', !teks);
        }

        function tampilkanGarisTerpilih(fit) {
            if (garisTerpilih) { map.removeLayer(garisTerpilih); garisTerpilih = null; }
            const k = cariKos(state.terpilih);
            if (!k || !state.rute) { infoRute(''); return; }

            const r = ruteKos[k.id];
            if (!r || r.memuat) { infoRute('Memuat rute...'); if (!r) muatRute(k.id, fit); return; }

            let lapisan, batas;
            if (r.tersedia) {
                const titik = r.geometri.coordinates.map(c => [c[1], c[0]]);
                const tengah = titik[Math.floor(titik.length / 2)];
                const garis = L.polyline(titik, { color: COKLAT, weight: 5, opacity: 0.9, lineJoin: 'round' });
                lapisan = [
                    L.polyline(titik, { color: '#fff', weight: 9, opacity: 0.9, lineJoin: 'round' }),
                    garis,
                    L.marker(tengah, { icon: labelPeta(jarakTeks(r.panjang_m) + ' · ' + r.durasi_menit + ' menit'), interactive: false, keyboard: false })
                ];
                batas = garis.getBounds();
                infoRute('Rute jalan kaki ' + jarakTeks(r.panjang_m) + ', sekitar ' + r.durasi_menit + ' menit.' + (r.catatan ? ' ' + r.catatan : ''));
            } else {
                const garis = L.polyline([[KAMPUS.lat, KAMPUS.lng], [k.lat, k.lng]], { color: COKLAT, weight: 2.5, dashArray: '2 7', lineCap: 'round' });
                lapisan = [
                    garis,
                    L.marker([(KAMPUS.lat + k.lat) / 2, (KAMPUS.lng + k.lng) / 2], { icon: labelPeta(jarakTeks(k.jarak_m) + ' (garis lurus)'), interactive: false, keyboard: false })
                ];
                batas = garis.getBounds();
                infoRute('Rute jalan belum tersedia, ditampilkan garis lurus.');
            }
            garisTerpilih = L.layerGroup(lapisan).addTo(map);
            if (fit) map.fitBounds(batas, { padding: [70, 70], maxZoom: 17 });
        }

        async function muatRute(id, fit) {
            if (ruteKos[id] && ruteKos[id].memuat) return;
            ruteKos[id] = { memuat: true };
            try {
                const res = await fetch(URL_RUTE(id), { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                ruteKos[id] = await res.json();
            } catch (err) {
                ruteKos[id] = { tersedia: false };
                setTimeout(() => { delete ruteKos[id]; }, 30000); // boleh dicoba lagi setelah 30 detik
            }
            if (state.terpilih === id && state.rute) tampilkanGarisTerpilih(fit);
        }

        function tampilkanDetail() {
            const k = cariKos(state.terpilih);
            $('detail').classList.toggle('hidden', !k);
            $('detail-kosong').classList.toggle('hidden', !!k);
            if (!k) return;

            if (galeri.kosId !== k.id) { galeri.kosId = k.id; galeri.idx = 0; }
            galeri.fotos = k.fotos;
            galeri.nama = k.nama;
            tampilFoto();

            $('d-nama').textContent = k.nama;
            $('d-tipe').textContent = 'Kos ' + k.tipe;
            $('d-tipe').className = 'shrink-0 rounded-full border px-3 py-1 text-xs font-semibold ' + (BADGE[k.tipe] || '');
            $('d-harga').textContent = rupiah(k.harga);
            $('d-alamat').textContent = k.alamat || '';
            $('d-jarak').textContent = jarakTeks(k.jarak_m) + ' dari ' + KAMPUS.nama + ' (garis lurus)';
            $('d-deskripsi').textContent = k.deskripsi || '';
            $('d-deskripsi').classList.toggle('hidden', !k.deskripsi);
            $('d-deskripsi-hr').classList.toggle('hidden', !k.deskripsi);
            $('d-fasilitas').innerHTML = k.fasilitas.map(f =>
                '<div class="flex flex-col items-center gap-1.5 text-center">' +
                '<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B46C54]">' + (IKON[f.ikon] || IKON['default']) + '</span>' +
                '<span class="text-[11px] leading-tight text-stone-500">' + esc(f.nama) + '</span></div>'
            ).join('') || '<p class="col-span-4 text-xs text-stone-400">Belum ada data fasilitas.</p>';
            $('d-tipe-teks').textContent = 'Kos ' + k.tipe;
            $('d-periode').textContent = k.periode || '-';
            $('d-telepon').textContent = k.telepon || '-';
            updateTombolRute();
            catatDilihat(k.id);
        }

        // Catat jumlah dilihat sekali per kos per kunjungan halaman.
        function catatDilihat(id) {
            if (sudahDihitung.has(id)) return;
            sudahDihitung.add(id);
            fetch(URL_DILIHAT(id), { method: 'POST', headers: { 'Accept': 'application/json' } }).catch(() => {});
        }

        function pilih(id, geser) {
            if (id !== state.terpilih) state.rute = false;
            state.terpilih = id;
            render();
            const k = cariKos(id);
            if (k && geser) map.flyTo([k.lat, k.lng], Math.max(map.getZoom(), 15), { duration: 0.6 });
        }

        // Filter jarak aktif: tampilkan lingkaran radiusnya. Tanpa filter jarak: sesuaikan dengan sebaran kos.
        function fitKeFilter() {
            if (state.jarak < JARAK_MAKS || daftar.length === 0) {
                map.fitBounds(L.latLng(KAMPUS.lat, KAMPUS.lng).toBounds(state.jarak * 2000), { padding: [30, 30] });
                return;
            }
            const batas = L.latLngBounds([[KAMPUS.lat, KAMPUS.lng], ...daftar.map(k => [k.lat, k.lng])]);
            map.fitBounds(batas, { padding: [60, 60], maxZoom: 16 });
        }

        // ---------- Galeri foto + lightbox ----------
        const galeri = { kosId: null, idx: 0, fotos: [], nama: '' };
        const lb = $('lightbox');
        const lbBuka = () => !lb.classList.contains('hidden');

        function tampilFoto() {
            const n = galeri.fotos.length;
            $('g-hitung').classList.toggle('hidden', n === 0);
            if (!n) { $('d-foto-utama').removeAttribute('src'); return; }
            const src = galeri.fotos[galeri.idx];
            $('d-foto-utama').src = src;
            $('d-foto-utama').alt = galeri.nama + ' - foto ' + (galeri.idx + 1);
            $('g-hitung').textContent = (galeri.idx + 1) + ' / ' + n;
            $('g-prev').classList.toggle('hidden', n < 2);
            $('g-next').classList.toggle('hidden', n < 2);

            if (lbBuka()) {
                $('lb-foto').src = src;
                $('lb-foto').alt = galeri.nama + ' - foto ' + (galeri.idx + 1);
                $('lb-hitung').textContent = 'Foto ' + (galeri.idx + 1) + ' dari ' + n;
                [...$('lb-dots').children].forEach((d, i) => {
                    d.className = 'h-2 rounded-full transition-all ' + (i === galeri.idx ? 'w-6 bg-white' : 'w-2 bg-white/40 hover:bg-white/70');
                });
                $('lb-prev').classList.toggle('hidden', n < 2);
                $('lb-next').classList.toggle('hidden', n < 2);
            }
        }
        function geserFoto(arah) {
            const n = galeri.fotos.length;
            if (n < 2) return;
            galeri.idx = (galeri.idx + arah + n) % n;
            tampilFoto();
        }
        function bukaLightbox() {
            if (!galeri.fotos.length) return;
            lb.classList.remove('hidden'); lb.classList.add('flex');
            document.body.style.overflow = 'hidden';
            $('lb-nama').textContent = galeri.nama;
            $('lb-dots').innerHTML = '';
            galeri.fotos.forEach((_, i) => {
                const d = document.createElement('button');
                d.type = 'button'; d.setAttribute('aria-label', 'Foto ' + (i + 1));
                d.addEventListener('click', () => { galeri.idx = i; tampilFoto(); });
                $('lb-dots').appendChild(d);
            });
            tampilFoto();
            $('lb-tutup').focus();
        }
        function tutupLightbox() {
            lb.classList.add('hidden'); lb.classList.remove('flex');
            document.body.style.overflow = '';
        }

        $('g-prev').addEventListener('click', () => geserFoto(-1));
        $('g-next').addEventListener('click', () => geserFoto(1));
        $('g-zoom').addEventListener('click', bukaLightbox);
        $('d-foto-utama').addEventListener('click', bukaLightbox);
        $('lb-prev').addEventListener('click', () => geserFoto(-1));
        $('lb-next').addEventListener('click', () => geserFoto(1));
        $('lb-tutup').addEventListener('click', tutupLightbox);
        lb.addEventListener('click', e => { if (e.target === lb || e.target === $('lb-area')) tutupLightbox(); });
        document.addEventListener('keydown', e => {
            if (!lbBuka()) return;
            if (e.key === 'Escape') tutupLightbox();
            if (e.key === 'ArrowLeft') geserFoto(-1);
            if (e.key === 'ArrowRight') geserFoto(1);
        });
        [$('d-foto-utama'), $('lb-area')].forEach(el => {
            let x0 = null;
            el.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
            el.addEventListener('touchend', e => {
                if (x0 === null) return;
                const dx = e.changedTouches[0].clientX - x0; x0 = null;
                if (Math.abs(dx) > 50) geserFoto(dx < 0 ? 1 : -1);
            });
        });

        // ---------- Tombol rute ----------
        function updateTombolRute() {
            const b = $('d-rute');
            if (!b) return;
            $('d-rute-teks').textContent = state.rute ? 'Sembunyikan Rute' : 'Lihat Rute';
            b.classList.toggle('bg-[#B46C54]', !state.rute);
            b.classList.toggle('text-white', !state.rute);
            b.classList.toggle('bg-white', state.rute);
            b.classList.toggle('text-[#B46C54]', state.rute);
            b.classList.toggle('hover:bg-[#955844]', !state.rute);
            b.classList.toggle('hover:bg-[#fbf5f3]', state.rute);
        }
        $('d-rute').addEventListener('click', () => {
            const k = cariKos(state.terpilih);
            if (!k) return;
            state.rute = !state.rute;
            updateTombolRute();
            tampilkanGarisTerpilih(true);
        });

        // ---------- Dropdown custom ----------
        function buatDropdown(id, onChange) {
            const root = $(id);
            const btn = root.querySelector('.dd-btn');
            const menu = root.querySelector('.dd-menu');
            const chev = root.querySelector('.dd-chev');
            const opts = [...root.querySelectorAll('.dd-opt')];

            function buka(v) {
                menu.classList.toggle('hidden', !v);
                chev.classList.toggle('rotate-180', v);
            }
            function set(value, diam) {
                const opt = opts.find(o => o.dataset.value === value) || opts[0];
                root.dataset.value = opt.dataset.value;
                root.querySelector('.dd-btn .dd-label').textContent = opt.dataset.label;
                const dotBtn = root.querySelector('.dd-btn .dd-dot');
                const dotOpt = opt.querySelector('.dd-dot');
                if (dotBtn && dotOpt) dotBtn.className = dotOpt.className;
                opts.forEach(o => {
                    const on = o === opt;
                    o.classList.toggle('bg-[#fbf5f3]', on);
                    o.classList.toggle('font-bold', on);
                    o.classList.toggle('text-[#B46C54]', on);
                    o.classList.toggle('text-stone-600', !on);
                    o.classList.toggle('hover:bg-stone-50', !on);
                    o.querySelector('.dd-check').classList.toggle('hidden', !on);
                });
                if (!diam && onChange) onChange(opt.dataset.value);
            }
            btn.addEventListener('click', e => { e.stopPropagation(); buka(menu.classList.contains('hidden')); });
            opts.forEach(o => o.addEventListener('click', () => { set(o.dataset.value); buka(false); }));
            document.addEventListener('click', e => { if (!root.contains(e.target)) buka(false); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') buka(false); });
            set(root.dataset.value, true);
            return { set, get: () => root.dataset.value };
        }

        // ---------- Kontrol filter ----------
        const inHarga = $('f-harga'), inJarak = $('f-jarak');
        const ddTipe = buatDropdown('dd-tipe');
        buatDropdown('dd-urut', v => { state.urut = v; muat(); });

        inHarga.addEventListener('input', () => { $('f-harga-label').textContent = rupiah(+inHarga.value); });
        inJarak.addEventListener('input', () => { $('f-jarak-label').textContent = (+inJarak.value).toString().replace('.', ',') + ' km'; });

        function terapkan() {
            state.harga = +inHarga.value;
            state.jarak = +inJarak.value;
            state.tipe = ddTipe.get();
            state.fasilitas = [...document.querySelectorAll('.f-fasilitas:checked')].map(c => +c.value);
            muat({ fit: true });
        }
        $('btn-terapkan').addEventListener('click', terapkan);

        $('btn-reset').addEventListener('click', () => {
            inHarga.value = HARGA_MAX; inJarak.value = JARAK_MAKS; ddTipe.set('Semua', true);
            document.querySelectorAll('.f-fasilitas').forEach(c => c.checked = false);
            $('f-cari').value = ''; state.cari = '';
            $('f-harga-label').textContent = rupiah(HARGA_MAX);
            $('f-jarak-label').textContent = JARAK_MAKS + ' km';
            terapkan();
        });

        document.querySelectorAll('.chip').forEach(c => c.addEventListener('click', () => {
            ddTipe.set(c.dataset.tipe, true);
            state.tipe = c.dataset.tipe;
            muat();
        }));

        let jedaCari = null;
        $('f-cari').addEventListener('input', e => {
            state.cari = e.target.value;
            clearTimeout(jedaCari);
            jedaCari = setTimeout(() => muat(), 300);
        });

        // ---------- Awal ----------
        muat({ fit: true, pilih: AWAL.kos });
        setTimeout(() => map.invalidateSize(), 200);
    });
    </script>
@endpush