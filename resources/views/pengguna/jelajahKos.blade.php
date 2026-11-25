@extends('layouts.pengguna')

@section('title', 'Jelajah Kos | Kost.in')
@section('body_class', 'bg-[#f7f5f2] overflow-hidden')
@section('header_class', 'hidden lg:block')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        #peta {
            position: fixed !important;
            inset: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }
        .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; background: #ece8e3; }

        /* Popup kustom peta */
        .leaflet-popup-content-wrapper {
            border-radius: 18px !important;
            padding: 0 !important;
            overflow: hidden !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid #D4EAD2 !important;
        }
        .leaflet-popup-content {
            margin: 0 !important;
            line-height: 1.4 !important;
        }
        .leaflet-popup-tip-container {
            margin-top: -1px;
        }

        /* Scrollbar terracotta */
        .scroll-thin::-webkit-scrollbar { width: 5px; }
        .scroll-thin::-webkit-scrollbar-thumb { background: #B8694C; border-radius: 5px; }
        .scroll-thin::-webkit-scrollbar-thumb:hover { background: #955844; }
        .scroll-thin::-webkit-scrollbar-track { background: transparent; }

        /* Sembunyikan scrollbar horizontal pada deretan chip mobile */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Sembunyikan kontrol zoom bawaan Leaflet */
        .leaflet-control-zoom { display: none !important; }

        /* Di mobile sembunyikan attribution bar bawah agar tidak tertutup bottom sheet */
        @media (max-width: 1023px) {
            .leaflet-control-attribution {
                font-size: 9px !important;
                background: rgba(255,255,255,0.65) !important;
                padding: 1px 5px !important;
                border-radius: 6px 0 0 0;
            }
        }

        /* Transisi mulus tinggi bottom sheet saat tidak sedang di-drag jari */
        #m-bottom-sheet.sheet-anim,
        #m-filter-panel.sheet-anim {
            transition: height 0.32s cubic-bezier(0.22, 1, 0.36, 1);
        }
        #m-map-tools.tools-anim {
            transition: bottom 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.2s ease;
        }
    </style>
@endpush

@section('content')
@php
    // Data berasal dari JelajahController; daftar kos diambil lewat API /api/kos.
    $fmtRingkas = fn ($n) => $n >= 1_000_000 ? 'Rp '.rtrim(rtrim(number_format($n / 1_000_000, 1, ',', '.'), '0'), ',').'jt' : 'Rp '.number_format($n / 1000, 0, ',', '.').'rb';
@endphp

    {{-- ========== PETA FULL SCREEN (BACKGROUND PENUH 100vw x 100vh) ========== --}}
    <div id="peta" class="fixed inset-0 h-screen w-screen z-0"></div>

    {{-- ===================================================================== --}}
    {{-- ==================== 1. DESKTOP WEB UI (>= 1024px) ================== --}}
    {{-- ===================================================================== --}}

    {{-- OVERLAY SEARCH BAR TENGAH ATAS (DESKTOP) --}}
    <div id="overlay-search" class="hidden lg:block fixed top-20 left-1/2 -translate-x-1/2 z-[400] w-[calc(100%-2rem)] max-w-md pointer-events-auto">
        <div class="relative flex items-center rounded-full border border-stone-200/90 bg-white/95 px-4 py-2.5 shadow-lg backdrop-blur-md transition-all focus-within:border-[#B8694C] focus-within:ring-2 focus-within:ring-[#B8694C]/20">
            <svg class="h-4 w-4 shrink-0 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m0 0A7.5 7.5 0 105.2 5.2a7.5 7.5 0 0010.6 10.6z"/></svg>
            <input id="f-cari" type="text" value="{{ $awal['q'] }}" maxlength="100" placeholder="Cari nama kos atau alamat..." class="w-full bg-transparent pl-3 pr-2 text-sm font-medium text-stone-800 placeholder:text-stone-400 focus:outline-none">
            <button type="button" id="btn-search-clear" class="hidden text-stone-400 hover:text-stone-700" title="Hapus pencarian">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- TOMBOL MUNCULKAN PANEL KIRI KETIKA DI-HIDE (DESKTOP) --}}
    <button type="button" id="btn-tampilkan-kiri" class="max-lg:!hidden fixed top-20 left-4 z-[400] hidden items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3.5 py-2 text-xs font-bold text-stone-800 shadow-md backdrop-blur-md transition hover:border-[#B8694C] hover:text-[#B8694C]">
        <svg class="h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4z"/></svg>
        <span>Filter & Kos</span>
        <svg class="h-3.5 w-3.5 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>

    {{-- KONTROL ZOOM & LOCATE SISI KIRI PETA (DESKTOP) --}}
    <div id="kontrol-zoom" class="hidden lg:flex fixed top-[126px] z-[400] flex-col gap-2 pointer-events-auto transition-all duration-300 ease-out" style="left: 355px;">
        <div class="flex flex-col items-center rounded-2xl border border-stone-200/90 bg-white/95 shadow-md backdrop-blur-md overflow-hidden">
            <button type="button" id="btn-zoom-in" aria-label="Perbesar peta" class="flex h-9 w-9 items-center justify-center text-sm font-bold text-stone-800 transition hover:bg-[#fbf5f3] hover:text-[#B8694C]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>
            <div class="h-px w-6 bg-stone-200"></div>
            <button type="button" id="btn-zoom-out" aria-label="Perkecil peta" class="flex h-9 w-9 items-center justify-center text-sm font-bold text-stone-800 transition hover:bg-[#fbf5f3] hover:text-[#B8694C]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
            </button>
        </div>

        <button type="button" id="btn-recenter" aria-label="Pusatkan ke Kampus SV IPB" class="flex h-9 w-9 items-center justify-center rounded-2xl border border-stone-200/90 bg-white/95 text-stone-800 shadow-md backdrop-blur-md transition hover:scale-105 hover:bg-[#fbf5f3] hover:text-[#B8694C]" title="Pusatkan ke Kampus SV IPB">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 2v2m0 16v2m-10-10h2m16 0h2"/></svg>
        </button>
    </div>

    {{-- KONTROL BAWAH: LAYER PETA & CHIPS TIPE KOS (DESKTOP) --}}
    <div id="kontrol-bawah" class="hidden lg:flex fixed bottom-5 z-[400] pointer-events-auto flex-col items-start gap-2 transition-all duration-300 ease-out" style="left: 355px;">
        <div id="panel-layer" class="relative">
            <button id="tombol-layer" type="button" aria-expanded="false" aria-controls="isi-layer"
                    class="flex items-center gap-2 rounded-full border border-stone-200/90 bg-white/95 px-4 py-2 text-xs font-bold text-stone-800 shadow-lg backdrop-blur-md transition hover:border-[#B8694C] hover:text-[#B8694C]">
                <svg class="h-4 w-4 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l-8 4l8 4l8 -4l-8 -4M4 12l8 4l8 -4M4 16l8 4l8 -4"/></svg>
                <span>Layer peta</span>
                <svg id="panah-layer" class="h-3.5 w-3.5 transition" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 15l6-6 6 6"/></svg>
            </button>

            <div id="isi-layer" data-lenis-scroll class="scroll-thin absolute bottom-full left-0 mb-2 hidden max-h-[min(55vh,400px)] w-64 overflow-y-auto rounded-2xl border border-stone-200/90 bg-white/95 text-xs text-stone-600 shadow-xl backdrop-blur-md">
                <div data-lenis-content class="p-3.5">
                    <ul class="space-y-3">
                        <li>
                            <label class="flex cursor-pointer items-center gap-2 font-bold text-stone-800">
                                <input type="checkbox" data-layer="kos" checked class="h-3.5 w-3.5 accent-[#B8694C]">Kos
                            </label>
                            <div data-legenda="kos" class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 pl-5">
                                <span class="flex items-center gap-1.5 font-medium"><span class="h-2.5 w-2.5 rounded-full bg-pink-400"></span>Putri</span>
                                <span class="flex items-center gap-1.5 font-medium"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Putra</span>
                                <span class="flex items-center gap-1.5 font-medium"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>Campur</span>
                            </div>
                        </li>
                        <li>
                            <label class="flex cursor-pointer items-center gap-2 font-bold text-stone-800">
                                <input type="checkbox" data-layer="area" checked class="h-3.5 w-3.5 accent-[#B8694C]">Area jarak jalan kaki
                            </label>
                            <div data-legenda="area" class="mt-1.5 pl-5">
                                <div class="flex items-center gap-1.5"><span class="h-2.5 w-4 rounded-sm border-2 border-[#B8694C] bg-[#B8694C]/10"></span><span id="leg-area">Geser slider jarak untuk melihat area.</span></div>
                                <p class="mt-1 text-[11px] leading-snug text-stone-500">Area yang bisa dicapai dari kampus lewat jalan.</p>
                            </div>
                        </li>
                        <li>
                            <label class="flex cursor-pointer items-center gap-2 font-bold text-stone-800">
                                <input type="checkbox" data-layer="buffer" class="h-3.5 w-3.5 accent-[#B8694C]">Lingkaran garis lurus
                            </label>
                            <div data-legenda="buffer" class="mt-1.5 hidden pl-5">
                                <div class="flex items-center gap-1.5 font-medium"><span class="h-2.5 w-4 rounded-full border-2 border-dashed border-[#B8694C]"></span>Radius dari kampus</div>
                                <p class="mt-1 text-[11px] leading-snug text-stone-500">Pembanding: radius jarak udara.</p>
                            </div>
                        </li>
                        <li>
                            <label class="flex cursor-pointer items-center gap-2 font-bold text-stone-800">
                                <input type="checkbox" data-layer="jangkauan" class="h-3.5 w-3.5 accent-[#B8694C]">Pita waktu jalan kaki
                            </label>
                            <div data-legenda="jangkauan" class="mt-1.5 hidden pl-5">
                                <div id="leg-jangkauan" class="text-stone-500">Memuat...</div>
                            </div>
                        </li>
                        <li>
                            <label class="flex cursor-pointer items-center gap-2 font-bold text-stone-800">
                                <input type="checkbox" data-layer="jalan" class="h-3.5 w-3.5 accent-[#B8694C]">Jaringan jalan
                            </label>
                            <div data-legenda="jalan" class="mt-1.5 hidden pl-5">
                                <div id="leg-jalan" class="text-stone-500">Memuat...</div>
                            </div>
                        </li>
                        <li class="flex items-center gap-2 border-t border-stone-200 pt-2 font-medium">
                            <span class="h-2.5 w-2.5 rounded-sm bg-stone-900"></span>Kampus SV IPB
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="chip-tipe" class="flex items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 p-1 shadow-lg backdrop-blur-md">
            <button type="button" data-tipe="Semua" class="chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-[#B8694C] bg-[#B8694C] px-3 py-1 text-xs font-semibold text-white shadow-2xs transition"><span class="h-2 w-2 rounded-full bg-white chip-dot"></span>Semua</button>
            <button type="button" data-tipe="Putri" class="chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs transition"><span class="h-2.5 w-2.5 rounded-full bg-pink-400"></span>Kos Putri</button>
            <button type="button" data-tipe="Putra" class="chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs transition"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Kos Putra</button>
            <button type="button" data-tipe="Campur" class="chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3 py-1 text-xs font-semibold text-stone-700 shadow-2xs transition"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>Campur</button>
        </div>
    </div>

    {{-- PANEL KIRI MELAYANG: FILTER PENCARIAN & DAFTAR KOS (DESKTOP) --}}
    <aside id="panel-kiri" class="hidden lg:flex fixed top-20 bottom-4 left-4 z-[400] w-[335px] flex-col rounded-3xl border border-stone-200/90 bg-white/95 shadow-xl backdrop-blur-md overflow-hidden pointer-events-auto transition-transform duration-300 ease-out">
        <div class="flex shrink-0 items-center justify-between border-b border-stone-100 bg-white/90 px-4 py-2.5 backdrop-blur-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#fbf5f3] text-[#B8694C]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4z"/></svg>
                </span>
                <h2 class="whitespace-nowrap text-xs sm:text-sm font-bold text-stone-900">Filter Pencarian</h2>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" id="btn-reset" class="text-[11px] font-bold text-[#B8694C] hover:underline px-1 py-0.5" title="Reset filter">Reset</button>
                <button type="button" id="btn-sembunyikan-kiri" class="flex items-center gap-1 rounded-full border border-stone-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-stone-600 transition hover:border-[#B8694C] hover:text-[#B8694C]" title="Sembunyikan filter pencarian">
                    <span>Sembunyikan</span>
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
            </div>
        </div>

        <div id="panel-kiri-scroll" data-lenis-scroll class="scroll-thin flex-1 overflow-y-auto">
            <div data-lenis-content class="p-4 space-y-4">
                {{-- Harga --}}
                <div class="rounded-2xl border border-stone-200/70 bg-[#fbf5f3]/40 p-3">
                    <div class="flex items-center justify-between text-xs">
                        <label for="f-harga" class="font-semibold text-stone-800">Harga per bulan</label>
                        <span id="f-harga-label" class="rounded-md border border-[#EFE3CC] bg-[#FDF7EA] px-2 py-0.5 font-bold text-[#B8694C]">Rp {{ number_format($hargaMax, 0, ',', '.') }}</span>
                    </div>
                    <input id="f-harga" type="range" min="{{ $hargaMin }}" max="{{ $hargaMax }}" step="50000" value="{{ $hargaMax }}" class="mt-2.5 w-full accent-[#B8694C]">
                    <div class="flex justify-between text-[11px] font-medium text-stone-500"><span>{{ $fmtRingkas($hargaMin) }}</span><span>{{ $fmtRingkas($hargaMax) }}</span></div>
                </div>

                {{-- Jarak --}}
                <div class="rounded-2xl border border-stone-200/70 bg-[#fbf5f3]/40 p-3">
                    <div class="flex items-center justify-between text-xs">
                        <label for="f-jarak" class="font-semibold text-stone-800">Jarak dari SV IPB</label>
                        <span id="f-jarak-label" class="rounded-md border border-[#EFE3CC] bg-[#FDF7EA] px-2 py-0.5 font-bold text-[#B8694C]">{{ $jarakMaks }} km</span>
                    </div>
                    <input id="f-jarak" type="range" min="0.5" max="{{ $jarakMaks }}" step="0.5" value="{{ $jarakMaks }}" class="mt-2.5 w-full accent-[#B8694C]">
                    <div class="flex justify-between text-[11px] font-medium text-stone-500"><span>0,5 km</span><span>{{ $jarakMaks }} km</span></div>
                    <p id="f-jarak-ket" class="mt-1 text-[11px] leading-snug text-stone-500">Semua jarak lewat jalan.</p>
                </div>

                {{-- Tipe Kos --}}
                <div class="rounded-2xl border border-stone-200/70 bg-[#fbf5f3]/40 p-3">
                    <p class="text-xs font-semibold text-stone-800">Tipe Kos</p>
                    <div id="dd-tipe" class="dd relative mt-1.5" data-value="Semua">
                        <button type="button" class="dd-btn flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs text-stone-700 transition hover:border-[#B8694C] focus:border-[#B8694C] focus:outline-none" aria-haspopup="listbox">
                            <span class="flex items-center gap-2"><span class="dd-dot h-2 w-2 rounded-full bg-[#B8694C]"></span><span class="dd-label font-medium">Semua Tipe</span></span>
                            <svg class="dd-chev h-3.5 w-3.5 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <ul class="dd-menu absolute z-30 mt-1.5 hidden w-full overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lg" role="listbox">
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="Semua" data-label="Semua Tipe" role="option"><span class="flex items-center gap-2"><span class="dd-dot h-2 w-2 rounded-full bg-[#B8694C]"></span>Semua Tipe</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="Putri" data-label="Kos Putri" role="option"><span class="flex items-center gap-2"><span class="dd-dot h-2 w-2 rounded-full bg-pink-400"></span>Kos Putri</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="Putra" data-label="Kos Putra" role="option"><span class="flex items-center gap-2"><span class="dd-dot h-2 w-2 rounded-full bg-emerald-500"></span>Kos Putra</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="Campur" data-label="Kos Campur" role="option"><span class="flex items-center gap-2"><span class="dd-dot h-2 w-2 rounded-full bg-blue-500"></span>Kos Campur</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                        </ul>
                    </div>
                </div>

                {{-- Fasilitas --}}
                <div class="rounded-2xl border border-stone-200/70 bg-[#fbf5f3]/40 p-3">
                    <p class="text-xs font-semibold text-stone-800">Fasilitas</p>
                    <div class="mt-2 grid grid-cols-2 gap-x-2 gap-y-2">
                        @forelse ($fasilitasFilter as $fas)
                            <label class="flex cursor-pointer items-start gap-2 text-xs text-stone-600 hover:text-stone-900">
                                <input type="checkbox" value="{{ $fas->id }}" class="f-fasilitas mt-0.5 h-3.5 w-3.5 shrink-0 rounded border-stone-300 accent-[#B8694C]">
                                <span class="truncate">{{ $fas->nama }}</span>
                            </label>
                        @empty
                            <p class="col-span-2 text-xs text-stone-400">Belum ada fasilitas.</p>
                        @endforelse
                    </div>
                </div>

                <button type="button" id="btn-terapkan" class="w-full rounded-xl bg-[#B8694C] py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#955844]">Terapkan Filter</button>

                <hr class="border-stone-200">

                {{-- Bagian Daftar Kos --}}
                <div id="bagian-daftar-kos">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs text-stone-600"><strong id="jumlah-kos" class="font-bold text-stone-900">0</strong> Kos ditemukan</p>
                        <div id="dd-urut" class="dd relative" data-value="rekomendasi">
                            <button type="button" class="dd-btn flex items-center gap-1.5 rounded-lg border border-stone-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-stone-700 transition hover:border-[#B8694C] focus:border-[#B8694C] focus:outline-none" aria-haspopup="listbox">
                                <span class="dd-label">Rekomendasi</span>
                                <svg class="dd-chev h-3 w-3 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <ul class="dd-menu absolute right-0 z-30 mt-1.5 hidden w-36 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lg" role="listbox">
                                <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-1.5 text-[11px] transition-colors" data-value="rekomendasi" data-label="Rekomendasi" role="option"><span>Rekomendasi</span><svg class="dd-check hidden h-3 w-3 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                                <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-1.5 text-[11px] transition-colors" data-value="jarak" data-label="Terdekat" role="option"><span>Terdekat</span><svg class="dd-check hidden h-3 w-3 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                                <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-1.5 text-[11px] transition-colors" data-value="murah" data-label="Termurah" role="option"><span>Termurah</span><svg class="dd-check hidden h-3 w-3 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                                <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-1.5 text-[11px] transition-colors" data-value="mahal" data-label="Termahal" role="option"><span>Termahal</span><svg class="dd-check hidden h-3 w-3 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            </ul>
                        </div>
                    </div>

                    <ul id="daftar-kos" class="mt-3 space-y-2.5"></ul>
                    <p id="kosong" class="hidden py-6 text-center text-xs font-medium text-stone-500">Tidak ada kos yang cocok dengan filter.</p>
                    <p id="ket-skor" class="mt-2.5 hidden text-[10px] leading-relaxed text-stone-400">Skor rekomendasi (0-1) dihitung dari jarak rute jalan (50%), harga (30%), dan fasilitas (20%).</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- TOMBOL MUNCULKAN PANEL KANAN KETIKA DI-HIDE (DESKTOP) --}}
    <button type="button" id="btn-tampilkan-kanan" class="max-lg:!hidden fixed top-20 right-4 z-[400] hidden items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3.5 py-2 text-xs font-bold text-stone-800 shadow-md backdrop-blur-md transition hover:border-[#B8694C] hover:text-[#B8694C]">
        <span>Detail Kos</span>
        <svg class="h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </button>

    {{-- PANEL KANAN MELAYANG: DETAIL KOS (DESKTOP) --}}
    <aside id="panel-kanan" class="hidden lg:flex fixed top-20 bottom-4 right-4 z-[400] w-[350px] xl:w-[370px] flex-col rounded-3xl border border-stone-200/90 bg-white/95 shadow-xl backdrop-blur-md pointer-events-auto transition-transform duration-300 ease-out overflow-hidden">
        <div class="flex shrink-0 items-center justify-between border-b border-stone-100 bg-white/80 px-4 py-2.5">
            <span class="flex items-center gap-1.5 text-xs font-bold text-stone-900">
                <svg class="h-4 w-4 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Detail Kos</span>
            </span>
            <button type="button" id="btn-sembunyikan-kanan" class="flex items-center gap-1 rounded-full border border-stone-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-stone-600 transition hover:border-[#B8694C] hover:text-[#B8694C]" title="Sembunyikan detail kos">
                <span>Sembunyikan</span>
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="panel-kanan-scroll" data-lenis-scroll class="scroll-thin flex-1 overflow-y-auto">
            <div data-lenis-content class="flex min-h-full flex-col">
                <div id="detail-kosong" class="flex flex-1 min-h-[260px] items-center justify-center p-8 text-center text-xs text-stone-500">
                    Pilih kos di daftar atau di peta untuk melihat detailnya.
                </div>

                <div id="detail" class="hidden">
                    <div class="p-3.5 pb-0">
                        <div id="galeri" class="group relative aspect-[16/10] w-full overflow-hidden rounded-2xl border border-stone-200/90 bg-stone-100 shadow-sm">
                            <img id="d-foto-utama" src="" alt="" class="h-full w-full cursor-zoom-in select-none object-cover">

                            <button id="g-prev" type="button" aria-label="Foto sebelumnya" class="absolute left-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-md transition hover:bg-white hover:text-[#B8694C]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button id="g-next" type="button" aria-label="Foto berikutnya" class="absolute right-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-md transition hover:bg-white hover:text-[#B8694C]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>

                            <button id="g-zoom" type="button" aria-label="Perbesar foto" class="absolute left-2.5 top-2.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-sm transition hover:bg-white hover:text-[#B8694C]">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                            </button>

                            <span id="g-hitung" class="absolute right-2.5 top-2.5 rounded-full bg-black/60 px-2.5 py-0.5 text-[11px] font-bold text-white backdrop-blur-sm shadow-sm"></span>
                        </div>
                    </div>

                    <div class="p-4 pt-3">
                        <div class="flex items-start justify-between gap-2">
                            <h3 id="d-nama" class="text-base font-bold leading-tight text-stone-900"></h3>
                            <span id="d-tipe" class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold"></span>
                        </div>
                        <p class="mt-2 text-lg font-extrabold text-[#B8694C]"><span id="d-harga"></span> <span class="text-xs font-medium text-stone-500">/ bulan</span></p>
                        <p id="d-alamat" class="mt-2 text-xs leading-relaxed text-stone-600"></p>
                        <p id="d-jarak" class="mt-1 text-xs text-stone-500"></p>

                        <hr class="my-3.5 border-stone-200">

                        <p id="d-deskripsi" class="hidden whitespace-pre-line text-xs leading-relaxed text-stone-600"></p>
                        <hr id="d-deskripsi-hr" class="my-3.5 hidden border-stone-200">

                        <p class="text-xs font-bold text-stone-900">Fasilitas</p>
                        <div id="d-fasilitas" class="mt-2.5 grid grid-cols-4 gap-x-2 gap-y-2.5"></div>

                        <hr class="my-3.5 border-stone-200">

                        <p class="text-xs font-bold text-stone-900">Detail</p>
                        <dl class="mt-2.5 space-y-1.5 text-xs">
                            <div class="flex justify-between gap-4"><dt class="text-stone-500">Tipe Kos</dt><dd id="d-tipe-teks" class="font-semibold text-stone-800"></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-stone-500">Minimal sewa</dt><dd id="d-periode" class="font-semibold text-stone-800"></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-stone-500">No. telepon</dt><dd id="d-telepon" class="font-semibold text-stone-800"></dd></div>
                        </dl>

                        <div class="mt-4">
                            <button type="button" id="d-rute" class="flex w-full items-center justify-center gap-2 rounded-xl border border-[#B8694C] bg-[#B8694C] py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-[#955844]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                <span id="d-rute-teks">Lihat Rute</span>
                            </button>
                            <p id="d-rute-info" class="mt-1.5 hidden text-center text-[11px] text-stone-500"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- ===================================================================== --}}
    {{-- ========= 2. DEDICATED NATIVE-STYLE MOBILE UI (< 1024px) ============ --}}
    {{-- ===================================================================== --}}

    {{-- A. MOBILE TOP UNIFIED SEARCH BAR + FLOATING CHIPS --}}
    <div id="m-top-bar" class="fixed top-3 inset-x-3 z-[450] lg:hidden pointer-events-none">
        {{-- Baris 1: Back Beranda + Search Input + Tombol Filter Terpadu --}}
        <div class="pointer-events-auto flex h-11 items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 p-1.5 shadow-lg backdrop-blur-md">
            <a href="{{ route('pengguna.beranda') }}" aria-label="Kembali ke Beranda" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#fbf5f3] text-[#B8694C] transition active:scale-95">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>

            <div class="flex min-w-0 flex-1 items-center">
                <svg class="ml-1 h-3.5 w-3.5 shrink-0 text-stone-400" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m0 0A7.5 7.5 0 105.2 5.2a7.5 7.5 0 0010.6 10.6z"/></svg>
                <input id="m-cari" type="text" value="{{ $awal['q'] }}" maxlength="100" placeholder="Cari kos atau alamat..." class="min-w-0 flex-1 bg-transparent px-2 text-xs font-medium text-stone-800 placeholder:text-stone-400 focus:outline-none">
                <button type="button" id="m-search-clear" class="hidden p-1 text-stone-400 hover:text-stone-700" aria-label="Hapus pencarian">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <button type="button" id="m-btn-filter" class="relative flex h-8 shrink-0 items-center gap-1.5 rounded-full bg-[#B8694C] px-3 text-[11px] font-bold text-white shadow-xs transition active:scale-95">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Filter</span>
                <span id="m-filter-badge" class="hidden h-4 min-w-4 items-center justify-center rounded-full bg-white px-1 text-[10px] font-extrabold text-[#B8694C]">0</span>
            </button>
        </div>

        {{-- Baris 2: Individual Airy Category Chips (Tanpa Kotak Pembungkus Berat) --}}
        <div id="m-chip-tipe" class="pointer-events-auto no-scrollbar mt-2 flex items-center gap-1.5 overflow-x-auto pb-1">
            <button type="button" data-tipe="Semua" class="m-chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-[#B8694C] bg-[#B8694C] px-3 py-1 text-[11px] font-bold text-white shadow-md backdrop-blur-md transition active:scale-95"><span class="h-2 w-2 rounded-full bg-current opacity-80"></span>Semua</button>
            <button type="button" data-tipe="Putri" class="m-chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3 py-1 text-[11px] font-bold text-stone-700 shadow-md backdrop-blur-md transition active:scale-95"><span class="h-2 w-2 rounded-full bg-pink-400"></span>Putri</button>
            <button type="button" data-tipe="Putra" class="m-chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3 py-1 text-[11px] font-bold text-stone-700 shadow-md backdrop-blur-md transition active:scale-95"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Putra</button>
            <button type="button" data-tipe="Campur" class="m-chip flex shrink-0 whitespace-nowrap items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 px-3 py-1 text-[11px] font-bold text-stone-700 shadow-md backdrop-blur-md transition active:scale-95"><span class="h-2 w-2 rounded-full bg-blue-500"></span>Campur</button>
        </div>
    </div>

    {{-- B. MOBILE FLOATING MAP ACTION BUTTONS (Kanan Peta, Tepat di Atas Bottom Sheet) --}}
    <div id="m-map-tools" class="tools-anim fixed right-3 z-[440] flex flex-col gap-2 lg:hidden pointer-events-auto" style="bottom: 46vh;">
        <button type="button" id="m-btn-layer" aria-label="Layer peta" class="flex h-10 w-10 items-center justify-center rounded-full border border-stone-200/90 bg-white/95 text-[#B8694C] shadow-lg backdrop-blur-md transition active:scale-95">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l-8 4l8 4l8 -4l-8 -4M4 12l8 4l8 -4M4 16l8 4l8 -4"/></svg>
        </button>
        <button type="button" id="m-btn-recenter" aria-label="Pusatkan ke Kampus SV IPB" class="flex h-10 w-10 items-center justify-center rounded-full border border-stone-200/90 bg-white/95 text-stone-700 shadow-lg backdrop-blur-md transition active:scale-95">
            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 2v2m0 16v2m-10-10h2m16 0h2"/></svg>
        </button>
    </div>

    {{-- C. MOBILE INTERACTIVE DRAGGABLE BOTTOM SHEET (DAFTAR KOS <-> DETAIL KOS) --}}
    <section id="m-bottom-sheet" class="sheet-anim fixed inset-x-0 bottom-0 z-[500] flex flex-col rounded-t-[28px] border-t border-stone-200/90 bg-white shadow-[0_-10px_32px_rgba(0,0,0,0.14)] lg:hidden pointer-events-auto overflow-hidden" style="height: 44vh;">
        {{-- Drag Handle Area --}}
        <div id="m-sheet-handle" data-lenis-prevent class="flex shrink-0 cursor-grab touch-none flex-col items-center pt-2.5 pb-1.5 select-none active:cursor-grabbing">
            <span class="h-1.5 w-10 rounded-full bg-stone-300"></span>
        </div>

        {{-- VIEW 1: DAFTAR KOS MOBILE --}}
        <div id="m-view-daftar" class="flex min-h-0 flex-1 flex-col">
            {{-- Subheader Daftar --}}
            <div data-lenis-prevent class="flex shrink-0 items-center justify-between border-b border-stone-100 px-4 pb-2.5 pt-0.5">
                <button type="button" id="m-toggle-sheet-daftar" class="text-left">
                    <p class="text-xs font-extrabold text-stone-900"><span id="m-jumlah-kos" class="text-[#B8694C]">0</span> Kos ditemukan</p>
                    <p id="m-sub-hint" class="text-[10px] font-medium text-stone-400">Geser ke atas untuk daftar penuh</p>
                </button>
                <div class="flex items-center gap-1.5">
                    <div id="m-dd-urut" class="dd relative" data-value="rekomendasi">
                        <button type="button" aria-label="Urutkan kos" aria-haspopup="listbox" aria-expanded="false" class="dd-btn flex h-7 items-center gap-1.5 rounded-full border border-stone-200 bg-white px-3 text-[11px] font-semibold text-stone-700 shadow-2xs transition hover:border-[#B8694C] focus:border-[#B8694C] focus:outline-none">
                            <span class="dd-label">Rekomendasi</span>
                            <svg class="dd-chev h-3 w-3 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <ul class="dd-menu absolute right-0 top-full z-50 mt-1.5 hidden w-38 overflow-hidden rounded-2xl border border-stone-200/90 bg-white py-1.5 shadow-[0_10px_28px_rgba(0,0,0,0.12)]" role="listbox">
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3.5 py-2 text-xs text-stone-700 transition-colors hover:bg-[#fbf5f3]/70 active:bg-[#fbf5f3]" data-value="rekomendasi" data-label="Rekomendasi" role="option">
                                <span>Rekomendasi</span>
                                <svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3.5 py-2 text-xs text-stone-700 transition-colors hover:bg-[#fbf5f3]/70 active:bg-[#fbf5f3]" data-value="jarak" data-label="Terdekat" role="option">
                                <span>Terdekat</span>
                                <svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3.5 py-2 text-xs text-stone-700 transition-colors hover:bg-[#fbf5f3]/70 active:bg-[#fbf5f3]" data-value="murah" data-label="Termurah" role="option">
                                <span>Termurah</span>
                                <svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3.5 py-2 text-xs text-stone-700 transition-colors hover:bg-[#fbf5f3]/70 active:bg-[#fbf5f3]" data-value="mahal" data-label="Termahal" role="option">
                                <span>Termahal</span>
                                <svg class="dd-check hidden h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </li>
                        </ul>
                    </div>
                    <button type="button" id="m-btn-peek" class="flex h-7 items-center gap-1 rounded-full border border-stone-200 bg-white px-2.5 text-[11px] font-bold text-stone-600 transition active:scale-95">
                        <span id="m-btn-peek-teks">Peta</span>
                    </button>
                </div>
            </div>

            {{-- Isi Daftar Kos Scrollable --}}
            <div id="m-daftar-scroll" data-lenis-scroll class="scroll-thin min-h-0 flex-1 overflow-y-auto">
                <div data-lenis-content class="p-3.5 pb-8">
                    <ul id="m-daftar-kos" class="space-y-2.5"></ul>
                    <p id="m-kosong" class="hidden py-8 text-center text-xs font-medium text-stone-500">Tidak ada kos yang cocok dengan filter.</p>
                </div>
            </div>
        </div>

        {{-- VIEW 2: DETAIL KOS MOBILE --}}
        <div id="m-view-detail" class="hidden min-h-0 flex-1 flex-col">
            {{-- Subheader Detail: Kembali ke Daftar + Toggle Rute Cepat --}}
            <div data-lenis-prevent class="flex shrink-0 items-center justify-between gap-2 border-b border-stone-100 px-4 pb-2.5 pt-0.5">
                <button type="button" id="m-kembali-daftar" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-3 py-1 text-[11px] font-bold text-stone-700 transition active:scale-95">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    <span>Daftar Kos</span>
                </button>
                <div class="flex items-center gap-1.5">
                    <button type="button" id="m-rute-cepat" class="inline-flex items-center gap-1 rounded-full border border-[#B8694C] bg-[#B8694C] px-3 py-1 text-[11px] font-bold text-white shadow-2xs transition active:scale-95">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <span id="m-rute-cepat-teks">Rute</span>
                    </button>
                    <button type="button" id="m-expand-detail" aria-label="Perbesar atau perkecil panel" class="flex h-7 w-7 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-600">
                        <svg id="m-expand-ikon" class="h-3.5 w-3.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- Isi Detail Kos Scrollable --}}
            <div id="m-detail-scroll" data-lenis-scroll class="scroll-thin min-h-0 flex-1 overflow-y-auto">
                <div data-lenis-content class="p-4 pt-3 pb-8">
                    {{-- Ringkasan Utama di Paling Atas Agar Terlihat di Half-Sheet --}}
                    <div class="flex items-start justify-between gap-2">
                        <h3 id="m-d-nama" class="text-base font-extrabold leading-snug text-stone-900"></h3>
                        <span id="m-d-tipe" class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-bold"></span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-base font-extrabold text-[#B8694C]"><span id="m-d-harga"></span> <span class="text-xs font-medium text-stone-500">/ bulan</span></p>
                        <p id="m-d-jarak" class="text-[11px] font-semibold text-stone-500"></p>
                    </div>
                    <p id="m-rute-info" class="mt-1.5 hidden rounded-xl bg-[#fbf5f3] px-3 py-1.5 text-[11px] font-semibold text-[#B8694C]"></p>

                    {{-- Galeri Foto Mobile --}}
                    <div id="m-galeri" class="relative mt-3 aspect-[16/9] w-full overflow-hidden rounded-2xl border border-stone-200/90 bg-stone-100 shadow-xs">
                        <img id="m-d-foto" src="" alt="" class="h-full w-full select-none object-cover">
                        <button id="m-g-prev" type="button" aria-label="Foto sebelumnya" class="absolute left-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-md">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button id="m-g-next" type="button" aria-label="Foto berikutnya" class="absolute right-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-md">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <button id="m-g-zoom" type="button" aria-label="Perbesar foto" class="absolute left-2.5 top-2.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-sm">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                        </button>
                        <span id="m-g-hitung" class="absolute right-2.5 top-2.5 rounded-full bg-black/60 px-2.5 py-0.5 text-[11px] font-bold text-white backdrop-blur-sm"></span>
                    </div>

                    <p id="m-d-alamat" class="mt-3 text-xs leading-relaxed text-stone-600"></p>

                    <hr class="my-3.5 border-stone-200">

                    <p id="m-d-deskripsi" class="hidden whitespace-pre-line text-xs leading-relaxed text-stone-600"></p>
                    <hr id="m-d-deskripsi-hr" class="my-3.5 hidden border-stone-200">

                    <p class="text-xs font-bold text-stone-900">Fasilitas</p>
                    <div id="m-d-fasilitas" class="mt-2.5 grid grid-cols-4 gap-x-2 gap-y-2.5"></div>

                    <hr class="my-3.5 border-stone-200">

                    <p class="text-xs font-bold text-stone-900">Informasi Sewa</p>
                    <dl class="mt-2 space-y-1.5 text-xs">
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">Tipe Kos</dt><dd id="m-d-tipe-teks" class="font-semibold text-stone-800"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">Minimal sewa</dt><dd id="m-d-periode" class="font-semibold text-stone-800"></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-stone-500">No. telepon</dt><dd id="m-d-telepon" class="font-semibold text-stone-800"></dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- D. MOBILE FILTER BOTTOM SHEET MODAL (SAMA TINGGI AWAL 44vh & BISA DITARIK / ADJUST) --}}
    <div id="m-filter-modal" class="fixed inset-0 z-[650] hidden items-end bg-black/20 lg:hidden" role="dialog" aria-modal="true">
        <div id="m-filter-panel" class="sheet-anim flex w-full flex-col rounded-t-[28px] border-t border-stone-200/90 bg-white shadow-[0_-10px_32px_rgba(0,0,0,0.16)] overflow-hidden" style="height: 44vh;">
            {{-- Area Drag Strip + Header Filter --}}
            <div id="m-filter-drag-zone" data-lenis-prevent class="shrink-0 cursor-grab touch-none border-b border-stone-100 select-none active:cursor-grabbing">
                <div id="m-filter-handle" class="flex flex-col items-center pt-2.5 pb-1.5">
                    <span class="h-1.5 w-10 rounded-full bg-stone-300"></span>
                </div>
                <div class="flex items-center justify-between px-4 pb-2.5 pt-0.5">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B8694C]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4z"/></svg>
                        </span>
                        <div>
                            <h2 class="text-xs font-extrabold leading-tight text-stone-900">Filter Pencarian</h2>
                            <p id="m-filter-hint" class="text-[10px] font-medium text-stone-400">Tarik strip ke atas untuk memperluas</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" id="m-btn-reset" class="rounded-full px-2.5 py-1 text-[11px] font-bold text-[#B8694C] hover:bg-[#fbf5f3]">Reset</button>
                        <button type="button" id="m-filter-tutup" aria-label="Tutup filter" class="flex h-7 w-7 items-center justify-center rounded-full bg-stone-100 text-stone-600 transition active:scale-95">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div id="m-filter-scroll" data-lenis-scroll class="scroll-thin min-h-0 flex-1 overflow-y-auto">
                <div data-lenis-content class="space-y-3.5 p-3.5">
                    {{-- Harga Mobile --}}
                    <div class="rounded-2xl border border-stone-200/80 bg-[#fbf5f3]/40 p-3.5">
                        <div class="flex items-center justify-between text-xs">
                            <label for="m-harga" class="font-bold text-stone-800">Harga per bulan</label>
                            <span id="m-harga-label" class="rounded-md border border-[#EFE3CC] bg-[#FDF7EA] px-2 py-0.5 font-bold text-[#B8694C]">Rp {{ number_format($hargaMax, 0, ',', '.') }}</span>
                        </div>
                        <input id="m-harga" type="range" min="{{ $hargaMin }}" max="{{ $hargaMax }}" step="50000" value="{{ $hargaMax }}" class="mt-3 w-full accent-[#B8694C]">
                        <div class="mt-1 flex justify-between text-[11px] font-medium text-stone-500"><span>{{ $fmtRingkas($hargaMin) }}</span><span>{{ $fmtRingkas($hargaMax) }}</span></div>
                    </div>

                    {{-- Jarak Mobile --}}
                    <div class="rounded-2xl border border-stone-200/80 bg-[#fbf5f3]/40 p-3.5">
                        <div class="flex items-center justify-between text-xs">
                            <label for="m-jarak" class="font-bold text-stone-800">Jarak dari SV IPB</label>
                            <span id="m-jarak-label" class="rounded-md border border-[#EFE3CC] bg-[#FDF7EA] px-2 py-0.5 font-bold text-[#B8694C]">{{ $jarakMaks }} km</span>
                        </div>
                        <input id="m-jarak" type="range" min="0.5" max="{{ $jarakMaks }}" step="0.5" value="{{ $jarakMaks }}" class="mt-3 w-full accent-[#B8694C]">
                        <div class="mt-1 flex justify-between text-[11px] font-medium text-stone-500"><span>0,5 km</span><span>{{ $jarakMaks }} km</span></div>
                        <p id="m-jarak-ket" class="mt-1 text-[11px] text-stone-500">Semua jarak lewat jalan.</p>
                    </div>

                    {{-- Tipe Kos Mobile --}}
                    <div class="rounded-2xl border border-stone-200/80 bg-[#fbf5f3]/40 p-3.5">
                        <p class="text-xs font-bold text-stone-800">Tipe Kos</p>
                        <div id="m-filter-tipe" class="mt-2.5 grid grid-cols-2 gap-2">
                            <button type="button" data-tipe="Semua" class="m-tipe-opt flex items-center justify-center gap-1.5 rounded-xl border border-[#B8694C] bg-[#B8694C] py-2 text-xs font-bold text-white transition">Semua Tipe</button>
                            <button type="button" data-tipe="Putri" class="m-tipe-opt flex items-center justify-center gap-1.5 rounded-xl border border-stone-200 bg-white py-2 text-xs font-bold text-stone-700 transition"><span class="h-2 w-2 rounded-full bg-pink-400"></span>Kos Putri</button>
                            <button type="button" data-tipe="Putra" class="m-tipe-opt flex items-center justify-center gap-1.5 rounded-xl border border-stone-200 bg-white py-2 text-xs font-bold text-stone-700 transition"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Kos Putra</button>
                            <button type="button" data-tipe="Campur" class="m-tipe-opt flex items-center justify-center gap-1.5 rounded-xl border border-stone-200 bg-white py-2 text-xs font-bold text-stone-700 transition"><span class="h-2 w-2 rounded-full bg-blue-500"></span>Campur</button>
                        </div>
                    </div>

                    {{-- Fasilitas Mobile --}}
                    <div class="rounded-2xl border border-stone-200/80 bg-[#fbf5f3]/40 p-3.5">
                        <p class="text-xs font-bold text-stone-800">Fasilitas</p>
                        <div class="mt-2.5 grid grid-cols-2 gap-2">
                            @forelse ($fasilitasFilter as $fas)
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-stone-200/80 bg-white px-2.5 py-2 text-xs text-stone-700">
                                    <input type="checkbox" value="{{ $fas->id }}" class="m-fasilitas h-3.5 w-3.5 shrink-0 rounded border-stone-300 accent-[#B8694C]">
                                    <span class="truncate font-medium">{{ $fas->nama }}</span>
                                </label>
                            @empty
                                <p class="col-span-2 text-xs text-stone-400">Belum ada fasilitas.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="shrink-0 border-t border-stone-100 bg-white px-4 py-2.5">
                <button type="button" id="m-btn-terapkan" class="w-full rounded-full bg-[#B8694C] py-2.5 text-xs font-extrabold text-white shadow-md transition active:scale-[0.99]">
                    Terapkan Filter
                </button>
            </div>
        </div>
    </div>

    {{-- E. MOBILE LAYER PETA MODAL --}}
    <div id="m-layer-modal" class="fixed inset-0 z-[650] hidden items-end bg-black/45 backdrop-blur-xs lg:hidden" role="dialog" aria-modal="true">
        <div id="m-layer-panel" class="w-full rounded-t-[28px] bg-white p-5 pb-7 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B8694C]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l-8 4l8 4l8 -4l-8 -4M4 12l8 4l8 -4M4 16l8 4l8 -4"/></svg>
                    </span>
                    <h3 class="text-sm font-extrabold text-stone-900">Layer & Informasi Peta</h3>
                </div>
                <button type="button" id="m-layer-tutup" aria-label="Tutup layer peta" class="flex h-8 w-8 items-center justify-center rounded-full bg-stone-100 text-stone-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <ul class="space-y-3 text-xs text-stone-700">
                <li class="flex items-center justify-between rounded-2xl border border-stone-200/80 px-3.5 py-2.5">
                    <div>
                        <p class="font-bold text-stone-900">Titik Lokasi Kos</p>
                        <p class="mt-0.5 text-[11px] text-stone-500">Putri (Pink) · Putra (Hijau) · Campur (Biru)</p>
                    </div>
                    <input type="checkbox" data-m-layer="kos" checked class="h-4 w-4 accent-[#B8694C]">
                </li>
                <li class="flex items-center justify-between rounded-2xl border border-stone-200/80 px-3.5 py-2.5">
                    <div>
                        <p class="font-bold text-stone-900">Area Jarak Jalan Kaki</p>
                        <p class="mt-0.5 text-[11px] text-stone-500">Batas jangkauan sesuai filter jarak</p>
                    </div>
                    <input type="checkbox" data-m-layer="area" checked class="h-4 w-4 accent-[#B8694C]">
                </li>
                <li class="flex items-center justify-between rounded-2xl border border-stone-200/80 px-3.5 py-2.5">
                    <div>
                        <p class="font-bold text-stone-900">Radius Garis Lurus</p>
                        <p class="mt-0.5 text-[11px] text-stone-500">Pembanding lingkaran jarak udara</p>
                    </div>
                    <input type="checkbox" data-m-layer="buffer" class="h-4 w-4 accent-[#B8694C]">
                </li>
                <li class="flex items-center justify-between rounded-2xl border border-stone-200/80 px-3.5 py-2.5">
                    <div>
                        <p class="font-bold text-stone-900">Pita Waktu Jalan Kaki</p>
                        <p class="mt-0.5 text-[11px] text-stone-500">Zona waktu tempuh dari Kampus SV IPB</p>
                    </div>
                    <input type="checkbox" data-m-layer="jangkauan" class="h-4 w-4 accent-[#B8694C]">
                </li>
                <li class="flex items-center justify-between rounded-2xl border border-stone-200/80 px-3.5 py-2.5">
                    <div>
                        <p class="font-bold text-stone-900">Jaringan Jalan & Gang</p>
                        <p class="mt-0.5 text-[11px] text-stone-500">Jalur OSM dan gang hasil survei</p>
                    </div>
                    <input type="checkbox" data-m-layer="jalan" class="h-4 w-4 accent-[#B8694C]">
                </li>
            </ul>
        </div>
    </div>

    {{-- ========== LIGHTBOX FOTO PENUH (SHARED DESKTOP & MOBILE) ========== --}}
    <div id="lightbox" class="fixed inset-0 z-[3000] hidden items-center justify-center bg-black/90 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
        <button id="lb-tutup" type="button" aria-label="Tutup" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <button id="lb-prev" type="button" aria-label="Foto sebelumnya" class="absolute left-3 sm:left-4 top-1/2 flex h-10 w-10 sm:h-12 sm:w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button id="lb-next" type="button" aria-label="Foto berikutnya" class="absolute right-3 sm:right-4 top-1/2 flex h-10 w-10 sm:h-12 sm:w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>

        <div class="flex max-h-full max-w-4xl flex-col items-center">
            <div id="lb-area" class="relative max-h-[80vh] overflow-hidden rounded-xl">
                <img id="lb-foto" src="" alt="" class="max-h-[80vh] w-auto select-none object-contain">
            </div>
            <div class="mt-3 flex items-center gap-4 text-xs font-semibold text-white">
                <span id="lb-nama"></span>
                <span class="text-white/40">·</span>
                <span id="lb-hitung"></span>
            </div>
            <div id="lb-dots" class="mt-2 flex gap-1.5"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Data dari backend
        const HARGA_MIN = @json((int) $hargaMin);
        const HARGA_MAX = @json((int) $hargaMax);
        const JARAK_MAKS = @json((float) $jarakMaks);
        const KAMPUS = @json($kampus);
        const AWAL = @json($awal);
        const M_PER_MENIT = @json((float) config('kostin.routing.kecepatan_jalan_kmh') * 1000 / 60);
        const IKON = @json($ikonSvg);
        const URL_API = @json(route('api.kos.index'));
        const URL_DILIHAT = id => @json(url('/api/kos')) + '/' + id + '/dilihat';
        const URL_RUTE = id => @json(url('/api/kos')) + '/' + id + '/rute';
        const URL_JANGKAUAN = @json(route('api.peta.jangkauan'));
        const URL_JALAN = @json(route('api.peta.jaringanJalan'));
        const WARNA = { 'Putri': '#f472b6', 'Putra': '#10b981', 'Campur': '#3b82f6' };
        const BADGE = {
            'Putri':  'border-pink-300 text-pink-600 bg-pink-50',
            'Putra':  'border-emerald-300 text-emerald-600 bg-emerald-50',
            'Campur': 'border-blue-300 text-blue-600 bg-blue-50'
        };
        const COKLAT = '#B8694C';
        const RING_KM = [1, 3, 5, 10].filter(km => km <= JARAK_MAKS);

        const rupiah = n => 'Rp ' + Number(n).toLocaleString('id-ID');
        const jarakTeks = m => m < 1000 ? (Math.round(m / 10) * 10) + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km';
        const kmTeks = km => (+km).toString().replace('.', ',') + ' km';
        const menitDari = m => Math.max(1, Math.ceil(m / M_PER_MENIT));

        const jarakKos = k => k.jarak_rute_m !== null && k.jarak_rute_m !== undefined
            ? jarakTeks(k.jarak_rute_m) + ' lewat jalan · ± ' + k.waktu_jalan_menit + ' menit jalan kaki'
            : jarakTeks(k.jarak_m) + ' dari kampus (garis lurus)';
        const skorTeks = s => s === null || s === undefined ? '' : Number(s).toFixed(2).replace('.', ',');
        const $ = id => document.getElementById(id);
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const isMobileUI = () => window.innerWidth < 1024;

        // ---------- Inisialisasi Peta ----------
        const map = L.map('peta', { zoomControl: false }).setView([KAMPUS.lat, KAMPUS.lng], 14);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        [
            'overlay-search', 'kontrol-zoom', 'kontrol-bawah', 'panel-kiri', 'panel-kanan',
            'btn-tampilkan-kiri', 'btn-tampilkan-kanan',
            'm-top-bar', 'm-map-tools', 'm-bottom-sheet', 'm-filter-modal', 'm-layer-modal'
        ].forEach(id => {
            const el = $(id);
            if (el) {
                L.DomEvent.disableClickPropagation(el);
                L.DomEvent.disableScrollPropagation(el);
            }
        });

        // ---------- Kamera Peta Cerdas (Memperhitungkan Tinggi Bottom Sheet di Mobile) ----------
        const AWAL_PILIH = +(AWAL.kos || AWAL.pilih || 0) || null;
        let sheetSnap = 'half'; // 'peek' | 'half' | 'full'
        let mobileMode = AWAL_PILIH ? 'detail' : 'daftar'; // 'daftar' | 'detail'

        function tinggiSheetPx() {
            if (!isMobileUI()) return 0;
            const el = $('m-bottom-sheet');
            return el ? el.getBoundingClientRect().height : 0;
        }

        function terbangKeTitik(lat, lng, zoomTarget) {
            const z = zoomTarget || Math.max(map.getZoom(), 15);
            if (!isMobileUI()) {
                map.flyTo([lat, lng], z, { duration: 0.6 });
                return;
            }
            // Offset kamera ke bawah agar pin muncul tepat di tengah ruang peta di atas bottom sheet
            const sheetH = tinggiSheetPx();
            const topBarH = 96;
            const offsetY = Math.round((sheetH - topBarH) * 0.42);
            const pt = map.project([lat, lng], z);
            pt.y += offsetY;
            const target = map.unproject(pt, z);
            map.flyTo(target, z, { duration: 0.55 });
        }

        // Kontrol Zoom & Recenter Desktop + Mobile
        $('btn-zoom-in').addEventListener('click', () => map.zoomIn());
        $('btn-zoom-out').addEventListener('click', () => map.zoomOut());
        $('btn-recenter').addEventListener('click', () => terbangKeTitik(KAMPUS.lat, KAMPUS.lng, 15));
        $('m-btn-recenter').addEventListener('click', () => {
            if (sheetSnap === 'full') setSheetSnap('half');
            terbangKeTitik(KAMPUS.lat, KAMPUS.lng, 15);
        });

        // Urutan tumpukan layer
        map.createPane('jangkauan').style.zIndex = 350;
        map.createPane('jalan').style.zIndex = 360;

        // Layer: lingkaran garis lurus (pembanding)
        const layerBuffer = L.layerGroup();
        function labelCincin(km) {
            return L.marker([KAMPUS.lat + (km * 1000) / 111320, KAMPUS.lng], {
                icon: L.divIcon({
                    className: '',
                    html: '<span style="display:inline-block;transform:translate(-50%,-50%);background:#fff;color:#955844;font:700 12px/1 \'Plus Jakarta Sans\',sans-serif;padding:5px 9px;border-radius:8px;border:1px solid #ead9d2;box-shadow:0 1px 3px rgba(0,0,0,.12);white-space:nowrap">' + kmTeks(km) + ' garis lurus</span>',
                    iconSize: [0, 0]
                }),
                interactive: false, keyboard: false
            });
        }
        function gambarBuffer() {
            layerBuffer.clearLayers();
            const cincin = state.jarak < JARAK_MAKS ? [state.jarak] : RING_KM;
            cincin.forEach(km => {
                L.circle([KAMPUS.lat, KAMPUS.lng], {
                    radius: km * 1000, color: COKLAT, weight: 2, opacity: 0.55, dashArray: '10 8',
                    fill: false, interactive: false
                }).addTo(layerBuffer);
                labelCincin(km).addTo(layerBuffer);
            });
        }

        // Layer: area jarak jalan kaki (network buffer)
        const layerArea = L.layerGroup().addTo(map);
        let nomorArea = 0;
        async function gambarArea() {
            const nomor = ++nomorArea;
            layerArea.clearLayers();
            if (state.jarak >= JARAK_MAKS) {
                $('leg-area').textContent = 'Geser slider jarak untuk melihat area.';
                return;
            }
            $('leg-area').textContent = 'Memuat area ' + kmTeks(state.jarak) + '...';
            try {
                const res = await fetch(URL_JANGKAUAN + '?jarak_km=' + state.jarak, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (nomor !== nomorArea) return;
                const f = (data.features || [])[0];
                if (!f) { $('leg-area').textContent = data.alasan || 'Area belum tersedia.'; return; }
                L.geoJSON(f, {
                    pane: 'jangkauan', interactive: false,
                    style: { color: COKLAT, weight: 2, opacity: 0.8, fillColor: COKLAT, fillOpacity: 0.08 }
                }).addTo(layerArea);
                $('leg-area').textContent = kmTeks(state.jarak) + ' lewat jalan · ± ' + f.properties.menit + ' menit';
            } catch (e) {
                if (nomor === nomorArea) $('leg-area').textContent = 'Gagal memuat area.';
                console.error('Gagal memuat area jarak', e);
            }
        }

        // Layer: jangkauan jalan kaki dari kampus (isochrone)
        const HIJAU = '#0f766e';
        const OPASITAS_PITA = 0.1;
        const layerJangkauan = L.layerGroup();
        function gambarJangkauan(data) {
            const pita = (data.features || []).slice().sort((a, b) => b.properties.menit - a.properties.menit);
            pita.forEach((f, i) => {
                L.geoJSON(f, {
                    pane: 'jangkauan', interactive: false,
                    style: { stroke: i === 0, color: HIJAU, weight: 1, opacity: 0.45, fillColor: HIJAU, fillOpacity: OPASITAS_PITA }
                }).addTo(layerJangkauan);
            });
            if (!pita.length) {
                $('leg-jangkauan').textContent = data.alasan || 'Belum tersedia.';
                return;
            }
            const naik = pita.slice().reverse();
            $('leg-jangkauan').innerHTML =
                '<div class="flex overflow-hidden rounded">' + naik.map((f, i) => {
                    const pekat = 1 - Math.pow(1 - OPASITAS_PITA, naik.length - i);
                    return '<span class="h-2.5 flex-1" style="background:rgba(15,118,110,' + pekat.toFixed(2) + ')"></span>';
                }).join('') + '</div>' +
                '<div class="mt-1 flex text-[11px] text-stone-500">' + naik.map(f =>
                    '<span class="flex-1 text-right">' + f.properties.menit + '</span>').join('') + '</div>' +
                '<p class="text-[11px] text-stone-500">menit jalan kaki dari kampus</p>';
        }

        // Layer: jaringan jalan sekitar kampus
        const GAYA_JALAN = {
            osm: { color: '#57534e', weight: 1, opacity: 0.45, interactive: false },
            survei: { color: '#2563eb', weight: 4, opacity: 0.9 }
        };
        const layerJalan = L.layerGroup();
        function gambarJalan(data) {
            const fitur = data.features || [];
            fitur.forEach(f => {
                const garis = L.geoJSON(f, { pane: 'jalan', style: GAYA_JALAN[f.properties.kategori] || GAYA_JALAN.osm });
                if (f.properties.kategori === 'survei') garis.bindTooltip(esc(f.properties.label));
                garis.addTo(layerJalan);
            });
            const ada = k => fitur.find(f => f.properties.kategori === k);
            $('leg-jalan').innerHTML = ['osm', 'survei'].map(k => {
                const f = ada(k);
                const label = k === 'osm' ? 'Jalan OSM' : 'Gang hasil survei';
                const g = GAYA_JALAN[k];
                return '<div class="flex items-center gap-1.5"><span class="w-4 rounded-full" style="height:' + Math.max(2, g.weight) + 'px;background:' + g.color + '"></span>' +
                    label + (f ? ' · ' + f.properties.panjang_km.toFixed(1).replace('.', ',') + ' km' : ' · belum ada') + '</div>';
            }).join('');
        }

        async function muatLayer(layer, url, gambar) {
            if (layer.getLayers().length || layer.dimuat) return;
            layer.dimuat = true;
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                gambar(await res.json());
            } catch (e) {
                layer.dimuat = false;
                console.error('Gagal memuat layer', url, e);
            }
        }

        // Marker Kampus SV IPB
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

        // ---------- State Aplikasi ----------
        const state = {
            harga: HARGA_MAX, jarak: JARAK_MAKS, tipe: 'Semua', fasilitas: [], cari: AWAL.q || '',
            urut: 'rekomendasi', terpilih: null, rute: false
        };
        let daftar = [];
        let permintaan = 0;
        const sudahDihitung = new Set();
        const markers = {};
        const layerKos = L.layerGroup().addTo(map);

        // ---------- Kontrol Panel Desktop (>= 1024px) ----------
        function updatePosisiKontrolKiri(panelTerbuka) {
            const leftVal = panelTerbuka ? '355px' : '16px';
            $('kontrol-zoom').style.left = leftVal;
            $('kontrol-bawah').style.left = leftVal;
        }

        function tutupPanelKiri() {
            $('panel-kiri').classList.add('-translate-x-[calc(100%+2rem)]', 'pointer-events-none');
            $('btn-tampilkan-kiri').classList.remove('hidden');
            $('btn-tampilkan-kiri').classList.add('flex');
            updatePosisiKontrolKiri(false);
        }

        function bukaPanelKiri() {
            $('panel-kiri').classList.remove('-translate-x-[calc(100%+2rem)]', 'pointer-events-none');
            $('btn-tampilkan-kiri').classList.add('hidden');
            $('btn-tampilkan-kiri').classList.remove('flex');
            updatePosisiKontrolKiri(true);
            if (window.kostinResizeLenis) window.kostinResizeLenis();
        }

        function tutupPanelKanan() {
            $('panel-kanan').classList.add('translate-x-[calc(100%+2rem)]', 'pointer-events-none');
            $('btn-tampilkan-kanan').classList.remove('hidden');
            $('btn-tampilkan-kanan').classList.add('flex');
        }

        function bukaPanelKanan() {
            $('panel-kanan').classList.remove('translate-x-[calc(100%+2rem)]', 'pointer-events-none');
            $('btn-tampilkan-kanan').classList.add('hidden');
            $('btn-tampilkan-kanan').classList.remove('flex');
            if (window.kostinResizeLenis) window.kostinResizeLenis();
        }

        $('btn-sembunyikan-kiri').addEventListener('click', tutupPanelKiri);
        $('btn-tampilkan-kiri').addEventListener('click', bukaPanelKiri);
        $('btn-sembunyikan-kanan').addEventListener('click', tutupPanelKanan);
        $('btn-tampilkan-kanan').addEventListener('click', bukaPanelKanan);

        // ---------- Kontrol Mobile Bottom Sheet (< 1024px) ----------
        const mSheet = $('m-bottom-sheet');
        const mTools = $('m-map-tools');

        function ukuranSnapPx(snap) {
            const vh = window.innerHeight;
            if (snap === 'peek') return 74;
            if (snap === 'full') return Math.round(vh * 0.85);
            return Math.round(vh * 0.44); // 'half'
        }

        function setSheetSnap(snap) {
            sheetSnap = snap;
            const px = ukuranSnapPx(snap);
            mSheet.classList.add('sheet-anim');
            mTools.classList.add('tools-anim');
            mSheet.style.height = px + 'px';

            // Posisikan tombol melayang peta tepat di atas bottom sheet (sembunyikan saat full)
            if (snap === 'full') {
                mTools.style.opacity = '0';
                mTools.style.pointerEvents = 'none';
            } else {
                mTools.style.opacity = '1';
                mTools.style.pointerEvents = 'auto';
                mTools.style.bottom = (px + 12) + 'px';
            }

            $('m-btn-peek-teks').textContent = snap === 'peek' ? 'Daftar' : 'Peta';
            $('m-sub-hint').textContent = snap === 'peek'
                ? 'Ketuk untuk membuka daftar kos'
                : (snap === 'full' ? 'Geser ke bawah untuk melihat peta' : 'Geser ke atas untuk daftar penuh');
            $('m-expand-ikon').classList.toggle('rotate-180', snap === 'full');

            if (window.kostinResizeLenis) {
                setTimeout(() => window.kostinResizeLenis(), 340);
            }
        }

        function setMobileView(mode, snapOverride) {
            mobileMode = mode;
            $('m-view-daftar').classList.toggle('hidden', mode !== 'daftar');
            $('m-view-daftar').classList.toggle('flex', mode === 'daftar');
            $('m-view-detail').classList.toggle('hidden', mode !== 'detail');
            $('m-view-detail').classList.toggle('flex', mode === 'detail');
            if (snapOverride) setSheetSnap(snapOverride);
            if (window.kostinResizeLenis) window.kostinResizeLenis();
        }

        // Interaksi Drag Sentuh & Mouse pada Handle Bottom Sheet Mobile (Daftar & Detail)
        (function initMobileSheetDrag() {
            const handle = $('m-sheet-handle');
            let startY = 0;
            let startH = 0;
            let dragging = false;
            let moved = false;

            handle.addEventListener('pointerdown', e => {
                if (e.button !== undefined && e.button !== 0) return;
                dragging = true;
                moved = false;
                startY = e.clientY;
                startH = mSheet.getBoundingClientRect().height;
                mSheet.classList.remove('sheet-anim');
                mTools.classList.remove('tools-anim');
                if (handle.setPointerCapture) handle.setPointerCapture(e.pointerId);
            });

            handle.addEventListener('pointermove', e => {
                if (!dragging) return;
                const dy = startY - e.clientY;
                if (Math.abs(dy) > 5) moved = true;
                const minH = 70;
                const maxH = Math.round(window.innerHeight * 0.88);
                const nextH = Math.max(minH, Math.min(maxH, startH + dy));
                mSheet.style.height = nextH + 'px';
                if (nextH < window.innerHeight * 0.72) {
                    mTools.style.opacity = '1';
                    mTools.style.bottom = (nextH + 12) + 'px';
                } else {
                    mTools.style.opacity = '0';
                }
            });

            function finishSheetDrag(e) {
                if (!dragging) return;
                dragging = false;
                if (e && handle.releasePointerCapture && handle.hasPointerCapture?.(e.pointerId)) {
                    handle.releasePointerCapture(e.pointerId);
                }
                if (!moved) {
                    setSheetSnap(sheetSnap === 'peek' ? 'half' : (sheetSnap === 'half' ? 'full' : 'half'));
                    return;
                }
                const curH = mSheet.getBoundingClientRect().height;
                const vh = window.innerHeight;
                if (curH < vh * 0.24) setSheetSnap('peek');
                else if (curH > vh * 0.64) setSheetSnap('full');
                else setSheetSnap('half');
            }

            handle.addEventListener('pointerup', finishSheetDrag);
            handle.addEventListener('pointercancel', finishSheetDrag);

            // Klik area judul daftar saat peek -> buka half
            $('m-toggle-sheet-daftar').addEventListener('click', () => {
                setSheetSnap(sheetSnap === 'peek' ? 'half' : (sheetSnap === 'half' ? 'full' : 'half'));
            });

            $('m-btn-peek').addEventListener('click', () => {
                setSheetSnap(sheetSnap === 'peek' ? 'half' : 'peek');
            });

            $('m-expand-detail').addEventListener('click', () => {
                setSheetSnap(sheetSnap === 'full' ? 'half' : 'full');
            });

            $('m-kembali-daftar').addEventListener('click', () => {
                setMobileView('daftar', sheetSnap === 'peek' ? 'half' : sheetSnap);
            });
        })();

        // Tap langsung pada area kosong peta di mobile: perkecil sheet full -> half agar nyaman melihat peta
        map.on('click', () => {
            if (isMobileUI() && sheetSnap === 'full') {
                setSheetSnap('half');
            }
        });

        window.addEventListener('resize', () => {
            map.invalidateSize();
            if (isMobileUI()) {
                setMobileView(mobileMode, sheetSnap);
            }
        });

        // Set posisi awal mobile sheet
        if (isMobileUI()) {
            setMobileView(mobileMode, 'half');
        }

        // ---------- Layer Peta Setup (Desktop + Mobile Sync) ----------
        const LAYER = {
            kos: { layer: layerKos },
            area: { layer: layerArea },
            buffer: { layer: layerBuffer },
            jangkauan: { layer: layerJangkauan, muat: () => muatLayer(layerJangkauan, URL_JANGKAUAN, gambarJangkauan) },
            jalan: { layer: layerJalan, muat: () => muatLayer(layerJalan, URL_JALAN, gambarJalan) }
        };

        function setLayerAktif(key, aktif) {
            const l = LAYER[key];
            if (!l) return;
            if (aktif) { l.layer.addTo(map); l.muat && l.muat(); } else { map.removeLayer(l.layer); }
            const leg = document.querySelector('[data-legenda="' + key + '"]');
            if (leg) leg.classList.toggle('hidden', !aktif);
            const cbDesk = document.querySelector('#isi-layer [data-layer="' + key + '"]');
            if (cbDesk) cbDesk.checked = aktif;
            const cbMob = document.querySelector('#m-layer-modal [data-m-layer="' + key + '"]');
            if (cbMob) cbMob.checked = aktif;
        }

        document.querySelectorAll('#isi-layer [data-layer]').forEach(cb => {
            cb.addEventListener('change', () => setLayerAktif(cb.dataset.layer, cb.checked));
        });
        document.querySelectorAll('#m-layer-modal [data-m-layer]').forEach(cb => {
            cb.addEventListener('change', () => setLayerAktif(cb.dataset.mLayer, cb.checked));
        });

        $('tombol-layer').addEventListener('click', () => {
            const buka = $('isi-layer').classList.toggle('hidden') === false;
            $('tombol-layer').setAttribute('aria-expanded', String(buka));
            $('panah-layer').classList.toggle('rotate-180', buka);
        });

        // Modal Layer Mobile
        function bukaModalLayer(v) {
            $('m-layer-modal').classList.toggle('hidden', !v);
            $('m-layer-modal').classList.toggle('flex', v);
        }
        $('m-btn-layer').addEventListener('click', () => bukaModalLayer(true));
        $('m-layer-tutup').addEventListener('click', () => bukaModalLayer(false));
        $('m-layer-modal').addEventListener('click', e => { if (e.target === $('m-layer-modal')) bukaModalLayer(false); });

        // Modal Filter Mobile (Tinggi Awal Sama 44vh & Bisa Di-adjust Bebas dengan Strip)
        const mFilterPanel = $('m-filter-panel');
        let filterHeightPx = null;

        function setFilterHeight(px, anim) {
            const vh = window.innerHeight;
            const minH = Math.max(210, Math.round(vh * 0.30));
            const maxH = Math.round(vh * 0.88);
            const clamped = Math.max(minH, Math.min(maxH, px));
            filterHeightPx = clamped;
            mFilterPanel.classList.toggle('sheet-anim', !!anim);
            mFilterPanel.style.height = clamped + 'px';
            $('m-filter-hint').textContent = clamped > vh * 0.66
                ? 'Tarik ke bawah untuk mengecilkan'
                : 'Tarik strip ke atas untuk memperluas';
            if (window.kostinResizeLenis) {
                setTimeout(() => window.kostinResizeLenis(), anim ? 330 : 0);
            }
        }

        function bukaModalFilter(v) {
            $('m-filter-modal').classList.toggle('hidden', !v);
            $('m-filter-modal').classList.toggle('flex', v);
            if (v) {
                // Buka tepat setinggi panel daftar tengah (44vh) agar tidak ketinggian
                setFilterHeight(ukuranSnapPx('half'), true);
            }
        }
        $('m-btn-filter').addEventListener('click', () => bukaModalFilter(true));
        $('m-filter-tutup').addEventListener('click', () => bukaModalFilter(false));
        $('m-filter-modal').addEventListener('click', e => { if (e.target === $('m-filter-modal')) bukaModalFilter(false); });

        (function initMobileFilterDrag() {
            const dragZone = $('m-filter-drag-zone');
            let startY = 0;
            let startH = 0;
            let dragging = false;
            let moved = false;

            dragZone.addEventListener('pointerdown', e => {
                if (e.target.closest('button')) return;
                if (e.button !== undefined && e.button !== 0) return;
                dragging = true;
                moved = false;
                startY = e.clientY;
                startH = mFilterPanel.getBoundingClientRect().height;
                mFilterPanel.classList.remove('sheet-anim');
                if (dragZone.setPointerCapture) dragZone.setPointerCapture(e.pointerId);
            });

            dragZone.addEventListener('pointermove', e => {
                if (!dragging) return;
                const dy = startY - e.clientY;
                if (Math.abs(dy) > 5) moved = true;
                const maxH = Math.round(window.innerHeight * 0.88);
                const nextH = Math.max(120, Math.min(maxH, startH + dy));
                mFilterPanel.style.height = nextH + 'px';
            });

            function finishFilterDrag(e) {
                if (!dragging) return;
                dragging = false;
                if (e && dragZone.releasePointerCapture && dragZone.hasPointerCapture?.(e.pointerId)) {
                    dragZone.releasePointerCapture(e.pointerId);
                }
                const vh = window.innerHeight;
                const curH = mFilterPanel.getBoundingClientRect().height;
                if (!moved) {
                    // Ketuk strip: toggle antara tinggi tengah (44vh) dan tinggi penuh (84vh)
                    const isTall = curH > vh * 0.62;
                    setFilterHeight(isTall ? ukuranSnapPx('half') : Math.round(vh * 0.84), true);
                    return;
                }
                // Tarik jauh ke bawah -> tutup filter
                if (curH < vh * 0.24) {
                    bukaModalFilter(false);
                    return;
                }
                // Pertahankan di posisi tinggi sesuai tarikan user (adjustable bebas)
                setFilterHeight(curH, true);
            }

            dragZone.addEventListener('pointerup', finishFilterDrag);
            dragZone.addEventListener('pointercancel', finishFilterDrag);
        })();

        const cariKos = id => daftar.find(x => x.id === id);

        function ikonKos(k, aktif) {
            const w = WARNA[k.tipe] || COKLAT;
            const size = aktif ? 40 : 32;
            return L.divIcon({
                className: '',
                html: '<span style="display:flex;width:' + size + 'px;height:' + size + 'px;border-radius:50%;background:#fff;align-items:center;justify-content:center;transform:translate(-50%,-50%);' +
                      'border:' + (aktif ? '3px solid ' + COKLAT : '2px solid #fff') + ';box-shadow:' + (aktif ? '0 0 0 4px rgba(184,105,76,.25),' : '') + '0 2px 6px rgba(0,0,0,.25)">' +
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
            $('m-kosong').textContent = 'Memuat data kos...';
            $('m-kosong').classList.remove('hidden');
            try {
                const res = await fetch(URL_API + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (nomor !== permintaan) return;
                if (!res.ok) throw new Error(json.message || ('HTTP ' + res.status));
                daftar = json.data;
                $('kosong').textContent = 'Tidak ada kos yang cocok dengan filter.';
                $('m-kosong').textContent = 'Tidak ada kos yang cocok dengan filter.';
                render(opsi);
            } catch (err) {
                if (nomor !== permintaan) return;
                daftar = [];
                render();
                $('kosong').textContent = 'Gagal memuat data kos. Periksa koneksi lalu coba lagi.';
                $('kosong').classList.remove('hidden');
                $('m-kosong').textContent = 'Gagal memuat data kos. Periksa koneksi lalu coba lagi.';
                $('m-kosong').classList.remove('hidden');
            }
        }

        // Delegasi klik pada konten popup peta (Desktop)
        map.on('popupopen', e => {
            const el = e.popup.getElement();
            if (el) {
                const row = el.querySelector('[data-kos-id]');
                if (row && !row.dataset.bound) {
                    row.dataset.bound = '1';
                    row.addEventListener('click', () => {
                        const id = +row.dataset.kosId;
                        pilih(id, false);
                        if (isMobileUI()) {
                            setMobileView('detail', 'half');
                        } else {
                            bukaPanelKanan();
                        }
                    });
                }
            }
        });

        function updateBadgeFilterMobile() {
            let aktifCount = 0;
            if (state.harga < HARGA_MAX) aktifCount++;
            if (state.jarak < JARAK_MAKS) aktifCount++;
            if (state.tipe !== 'Semua') aktifCount++;
            aktifCount += state.fasilitas.length;
            const badge = $('m-filter-badge');
            badge.textContent = aktifCount;
            badge.classList.toggle('hidden', aktifCount === 0);
            badge.classList.toggle('inline-flex', aktifCount > 0);
        }

        // ---------- Render Seluruh Tampilan ----------
        function render(opsi) {
            $('jumlah-kos').textContent = daftar.length;
            $('m-jumlah-kos').textContent = daftar.length;
            $('kosong').classList.toggle('hidden', daftar.length > 0);
            $('m-kosong').classList.toggle('hidden', daftar.length > 0);
            $('ket-skor').classList.toggle('hidden', state.urut !== 'rekomendasi' || daftar.length === 0);
            updateBadgeFilterMobile();

            if (opsi && opsi.pilih && cariKos(opsi.pilih)) state.terpilih = opsi.pilih;
            if (state.terpilih && !cariKos(state.terpilih)) {
                state.terpilih = null;
                state.rute = false;
                if (isMobileUI() && mobileMode === 'detail') setMobileView('daftar');
            }
            if (!state.terpilih && daftar.length && !(opsi && opsi.tanpaPilihOtomatis)) state.terpilih = daftar[0].id;

            // 1. Render Daftar Kos Desktop
            const ul = $('daftar-kos');
            ul.innerHTML = '';

            // 2. Render Daftar Kos Mobile
            const mUl = $('m-daftar-kos');
            mUl.innerHTML = '';

            daftar.forEach((k, i) => {
                const aktif = k.id === state.terpilih;
                const skor = state.urut === 'rekomendasi'
                    ? '<span class="ml-auto shrink-0 rounded-md bg-[#fbf5f3] px-1.5 py-0.5 text-[10px] font-bold text-[#B8694C]" title="Skor rekomendasi">#' + (i + 1) + ' · ' + skorTeks(k.skor) + '</span>'
                    : '';

                // Item Desktop
                const li = document.createElement('li');
                li.innerHTML =
                    '<button type="button" class="flex w-full gap-2.5 rounded-2xl border p-2 text-left transition ' +
                    (aktif ? 'border-[#B8694C] bg-[#fbf5f3] shadow-xs' : 'border-stone-200/80 bg-white hover:border-[#B8694C]/60 hover:bg-[#fbf5f3]/30') + '">' +
                    '<img src="' + esc(k.fotos[0] || '') + '" alt="" loading="lazy" class="h-16 w-16 shrink-0 rounded-xl bg-stone-100 object-cover">' +
                    '<span class="min-w-0 flex-1">' +
                    '<span class="flex items-start gap-1.5"><span class="block truncate text-xs font-bold text-stone-900">' + esc(k.nama) + '</span>' + skor + '</span>' +
                    '<span class="mt-0.5 block text-xs font-bold text-[#B8694C]">' + rupiah(k.harga) + ' <span class="font-normal text-stone-500 text-[10px]">/ bln</span></span>' +
                    '<span class="mt-0.5 block truncate text-[11px] text-stone-500">' + jarakKos(k) + '</span>' +
                    '<span class="mt-0.5 block truncate text-[10px] text-stone-400">' + esc(k.fasilitas.slice(0, 3).map(f => f.nama).join(' · ')) + '</span>' +
                    '</span></button>';
                li.firstChild.addEventListener('click', () => {
                    pilih(k.id, true);
                    bukaPanelKanan();
                });
                ul.appendChild(li);

                // Item Mobile (Kartu Mobile Rapi & Proporsional)
                const mLi = document.createElement('li');
                const badgeCls = BADGE[k.tipe] || 'border-stone-200 text-stone-600 bg-stone-50';
                mLi.innerHTML =
                    '<button type="button" class="flex w-full items-center gap-3 rounded-2xl border p-2.5 text-left transition active:scale-[0.99] ' +
                    (aktif ? 'border-[#B8694C] bg-[#fbf5f3]/70 shadow-xs' : 'border-stone-200/90 bg-white') + '">' +
                    '<img src="' + esc(k.fotos[0] || '') + '" alt="" loading="lazy" class="h-[72px] w-[72px] shrink-0 rounded-xl bg-stone-100 object-cover">' +
                    '<span class="min-w-0 flex-1">' +
                    '<span class="flex items-center justify-between gap-1.5">' +
                        '<span class="truncate text-xs font-extrabold text-stone-900">' + esc(k.nama) + '</span>' +
                        '<span class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-bold ' + badgeCls + '">' + esc(k.tipe) + '</span>' +
                    '</span>' +
                    '<span class="mt-0.5 block text-xs font-extrabold text-[#B8694C]">' + rupiah(k.harga) + ' <span class="font-medium text-stone-400 text-[10px]">/ bulan</span></span>' +
                    '<span class="mt-1 block truncate text-[11px] font-medium text-stone-500">' + jarakKos(k) + '</span>' +
                    '</span></button>';
                mLi.firstChild.addEventListener('click', () => {
                    setMobileView('detail', sheetSnap === 'peek' ? 'half' : sheetSnap);
                    pilih(k.id, true);
                });
                mUl.appendChild(mLi);
            });

            // 3. Render Marker di Peta
            layerKos.clearLayers();
            Object.keys(markers).forEach(id => delete markers[id]);
            daftar.forEach(k => {
                const m = L.marker([k.lat, k.lng], { icon: ikonKos(k, k.id === state.terpilih), zIndexOffset: k.id === state.terpilih ? 900 : 0 });

                const popupHtml = `
                    <div class="flex items-center gap-3 p-2 bg-white cursor-pointer hover:bg-[#fbf5f3] transition" data-kos-id="${k.id}">
                        <img src="${esc(k.fotos[0] || '')}" class="w-12 h-12 rounded-xl object-cover shrink-0 bg-stone-100">
                        <div class="min-w-0 pr-1">
                            <p class="font-bold text-xs text-stone-900 truncate">${esc(k.nama)}</p>
                            <p class="font-black text-xs text-[#B8694C] mt-0.5">${rupiah(k.harga)} <span class="font-normal text-[10px] text-stone-400">/bln</span></p>
                            <p class="text-[10px] text-stone-500 truncate">${jarakTeks(k.jarak_m)} dari kampus</p>
                        </div>
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#fbf5f3] text-[#B8694C] text-xs font-bold shrink-0 ml-auto">›</span>
                    </div>
                `;
                m.bindPopup(popupHtml, { offset: [0, -14], closeButton: false });

                m.on('click', () => {
                    if (isMobileUI()) {
                        m.closePopup();
                        setMobileView('detail', sheetSnap === 'peek' ? 'half' : (sheetSnap === 'full' ? 'half' : sheetSnap));
                        pilih(k.id, true);
                    } else {
                        pilih(k.id, false);
                        bukaPanelKanan();
                        if (markers[k.id]) markers[k.id].openPopup();
                    }
                });

                markers[k.id] = m;
                layerKos.addLayer(m);
            });

            tampilkanGarisTerpilih();

            // Status Chip Tipe Desktop
            document.querySelectorAll('#chip-tipe .chip').forEach(c => {
                const on = c.dataset.tipe === state.tipe;
                c.classList.toggle('bg-[#B8694C]', on);
                c.classList.toggle('text-white', on);
                c.classList.toggle('border-[#B8694C]', on);
                c.classList.toggle('bg-white/95', !on);
                c.classList.toggle('text-stone-700', !on);
                c.classList.toggle('border-stone-200/90', !on);
            });

            // Status Chip Tipe Mobile & Opsi Modal Filter Mobile
            document.querySelectorAll('#m-chip-tipe .m-chip').forEach(c => {
                const on = c.dataset.tipe === state.tipe;
                c.classList.toggle('bg-[#B8694C]', on);
                c.classList.toggle('text-white', on);
                c.classList.toggle('border-[#B8694C]', on);
                c.classList.toggle('bg-white/95', !on);
                c.classList.toggle('text-stone-700', !on);
                c.classList.toggle('border-stone-200/90', !on);
            });

            document.querySelectorAll('#m-filter-tipe .m-tipe-opt').forEach(c => {
                const on = c.dataset.tipe === state.tipe;
                c.classList.toggle('bg-[#B8694C]', on);
                c.classList.toggle('text-white', on);
                c.classList.toggle('border-[#B8694C]', on);
                c.classList.toggle('bg-white', !on);
                c.classList.toggle('text-stone-700', !on);
                c.classList.toggle('border-stone-200', !on);
            });

            tampilkanDetail();

            if (opsi && opsi.fit) fitKeFilter();
            if (opsi && opsi.pilih && cariKos(opsi.pilih)) {
                const k = cariKos(opsi.pilih);
                if (isMobileUI()) setMobileView('detail', 'half');
                terbangKeTitik(k.lat, k.lng, Math.max(map.getZoom(), 15));
            }
        }

        // ---------- Rute Jalan Kaki ----------
        const ruteKos = {};
        let garisTerpilih = null;

        const labelPeta = teks => L.divIcon({
            className: '',
            html: '<span style="display:inline-block;transform:translate(-50%,-50%);background:#fff;color:#955844;font:700 11px/1 \'Plus Jakarta Sans\',sans-serif;padding:5px 8px;border-radius:8px;border:1px solid #ead9d2;box-shadow:0 1px 3px rgba(0,0,0,.15);white-space:nowrap">' + esc(teks) + '</span>',
            iconSize: [0, 0]
        });

        function infoRute(teks) {
            $('d-rute-info').textContent = teks || '';
            $('d-rute-info').classList.toggle('hidden', !teks);
            $('m-rute-info').textContent = teks || '';
            $('m-rute-info').classList.toggle('hidden', !teks);
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
            if (fit) {
                if (isMobileUI()) {
                    map.fitBounds(batas, {
                        paddingTopLeft: [28, 110],
                        paddingBottomRight: [28, tinggiSheetPx() + 28],
                        maxZoom: 17
                    });
                } else {
                    map.fitBounds(batas, { padding: [70, 70], maxZoom: 17 });
                }
            }
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
                setTimeout(() => { delete ruteKos[id]; }, 30000);
            }
            if (state.terpilih === id && state.rute) tampilkanGarisTerpilih(fit);
        }

        // ---------- Tampilkan Detail Kos (Desktop Panel Kanan + Mobile Sheet Detail) ----------
        function tampilkanDetail() {
            const k = cariKos(state.terpilih);
            $('detail').classList.toggle('hidden', !k);
            $('detail-kosong').classList.toggle('hidden', !!k);
            if (!k) return;

            if (galeri.kosId !== k.id) {
                galeri.kosId = k.id;
                galeri.idx = 0;
                if (window.kostinScrollToTop) {
                    window.kostinScrollToTop('panel-kanan-scroll', true);
                    window.kostinScrollToTop('m-detail-scroll', true);
                }
            }
            galeri.fotos = k.fotos;
            galeri.nama = k.nama;
            tampilFoto();

            const teksJarakLengkap = k.jarak_rute_m !== null && k.jarak_rute_m !== undefined
                ? jarakTeks(k.jarak_rute_m) + ' lewat jalan ke ' + KAMPUS.nama + ' (± ' + k.waktu_jalan_menit + ' menit jalan kaki)'
                : jarakTeks(k.jarak_m) + ' dari ' + KAMPUS.nama + ' (garis lurus)';

            const teksJarakMobile = k.jarak_rute_m !== null && k.jarak_rute_m !== undefined
                ? jarakTeks(k.jarak_rute_m) + ' · ± ' + k.waktu_jalan_menit + ' mnt jalan kaki'
                : jarakTeks(k.jarak_m) + ' dari SV IPB';

            const fasilitasHtml = k.fasilitas.map(f =>
                '<div class="flex flex-col items-center gap-1 text-center">' +
                '<span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B8694C] shadow-2xs">' + (IKON[f.ikon] || IKON['default']) + '</span>' +
                '<span class="text-[10px] leading-tight font-medium text-stone-600">' + esc(f.nama) + '</span></div>'
            ).join('') || '<p class="col-span-4 text-xs text-stone-400">Belum ada data fasilitas.</p>';

            // Isi Desktop
            $('d-nama').textContent = k.nama;
            $('d-tipe').textContent = 'Kos ' + k.tipe;
            $('d-tipe').className = 'shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ' + (BADGE[k.tipe] || '');
            $('d-harga').textContent = rupiah(k.harga);
            $('d-alamat').textContent = k.alamat || '';
            $('d-jarak').textContent = teksJarakLengkap;
            $('d-deskripsi').textContent = k.deskripsi || '';
            $('d-deskripsi').classList.toggle('hidden', !k.deskripsi);
            $('d-deskripsi-hr').classList.toggle('hidden', !k.deskripsi);
            $('d-fasilitas').innerHTML = fasilitasHtml;
            $('d-tipe-teks').textContent = 'Kos ' + k.tipe;
            $('d-periode').textContent = k.periode || '-';
            $('d-telepon').textContent = k.telepon || '-';

            // Isi Mobile
            $('m-d-nama').textContent = k.nama;
            $('m-d-tipe').textContent = 'Kos ' + k.tipe;
            $('m-d-tipe').className = 'shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-bold ' + (BADGE[k.tipe] || '');
            $('m-d-harga').textContent = rupiah(k.harga);
            $('m-d-jarak').textContent = teksJarakMobile;
            $('m-d-alamat').textContent = k.alamat || '';
            $('m-d-deskripsi').textContent = k.deskripsi || '';
            $('m-d-deskripsi').classList.toggle('hidden', !k.deskripsi);
            $('m-d-deskripsi-hr').classList.toggle('hidden', !k.deskripsi);
            $('m-d-fasilitas').innerHTML = fasilitasHtml;
            $('m-d-tipe-teks').textContent = 'Kos ' + k.tipe;
            $('m-d-periode').textContent = k.periode || '-';
            $('m-d-telepon').textContent = k.telepon || '-';

            updateTombolRute();
            catatDilihat(k.id);
            if (window.kostinResizeLenis) window.kostinResizeLenis();
        }

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
            if (k && geser) terbangKeTitik(k.lat, k.lng, Math.max(map.getZoom(), 15));
        }

        function fitKeFilter() {
            const padOpts = isMobileUI()
                ? { paddingTopLeft: [24, 108], paddingBottomRight: [24, tinggiSheetPx() + 24], maxZoom: 16 }
                : { padding: [70, 70], maxZoom: 16 };
            if (state.jarak < JARAK_MAKS || daftar.length === 0) {
                map.fitBounds(L.latLng(KAMPUS.lat, KAMPUS.lng).toBounds(state.jarak * 2000), padOpts);
                return;
            }
            const batas = L.latLngBounds([[KAMPUS.lat, KAMPUS.lng], ...daftar.map(k => [k.lat, k.lng])]);
            map.fitBounds(batas, padOpts);
        }

        // ---------- Galeri Foto + Lightbox (Desktop & Mobile) ----------
        const galeri = { kosId: null, idx: 0, fotos: [], nama: '' };
        const lb = $('lightbox');
        const lbBuka = () => !lb.classList.contains('hidden');

        function tampilFoto() {
            const n = galeri.fotos.length;
            $('g-hitung').classList.toggle('hidden', n === 0);
            $('m-g-hitung').classList.toggle('hidden', n === 0);
            if (!n) {
                $('d-foto-utama').removeAttribute('src');
                $('m-d-foto').removeAttribute('src');
                return;
            }
            const src = galeri.fotos[galeri.idx];
            const altText = galeri.nama + ' - foto ' + (galeri.idx + 1);
            const labelHitung = (galeri.idx + 1) + ' / ' + n;

            $('d-foto-utama').src = src;
            $('d-foto-utama').alt = altText;
            $('g-hitung').textContent = labelHitung;
            $('g-prev').classList.toggle('hidden', n < 2);
            $('g-next').classList.toggle('hidden', n < 2);

            $('m-d-foto').src = src;
            $('m-d-foto').alt = altText;
            $('m-g-hitung').textContent = labelHitung;
            $('m-g-prev').classList.toggle('hidden', n < 2);
            $('m-g-next').classList.toggle('hidden', n < 2);

            if (lbBuka()) {
                $('lb-foto').src = src;
                $('lb-foto').alt = altText;
                $('lb-hitung').textContent = 'Foto ' + (galeri.idx + 1) + ' dari ' + n;
                [...$('lb-dots').children].forEach((d, i) => {
                    d.className = 'h-2 rounded-full transition-all ' + (i === galeri.idx ? 'w-6 bg-[#B8694C]' : 'w-2 bg-white/40 hover:bg-white/70');
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
        $('m-g-prev').addEventListener('click', () => geserFoto(-1));
        $('m-g-next').addEventListener('click', () => geserFoto(1));
        $('m-g-zoom').addEventListener('click', bukaLightbox);
        $('m-d-foto').addEventListener('click', bukaLightbox);
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
        [$('d-foto-utama'), $('m-d-foto'), $('lb-area')].forEach(el => {
            let x0 = null;
            el.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
            el.addEventListener('touchend', e => {
                if (x0 === null) return;
                const dx = e.changedTouches[0].clientX - x0; x0 = null;
                if (Math.abs(dx) > 50) geserFoto(dx < 0 ? 1 : -1);
            });
        });

        // ---------- Tombol Rute (Desktop & Mobile) ----------
        function updateTombolRute() {
            const b = $('d-rute');
            if (b) {
                $('d-rute-teks').textContent = state.rute ? 'Sembunyikan Rute' : 'Lihat Rute';
                b.classList.toggle('bg-[#B8694C]', !state.rute);
                b.classList.toggle('text-white', !state.rute);
                b.classList.toggle('bg-white', state.rute);
                b.classList.toggle('text-[#B8694C]', state.rute);
                b.classList.toggle('hover:bg-[#955844]', !state.rute);
                b.classList.toggle('hover:bg-[#fbf5f3]', state.rute);
            }
            const mb = $('m-rute-cepat');
            if (mb) {
                $('m-rute-cepat-teks').textContent = state.rute ? 'Tutup Rute' : 'Lihat Rute';
                mb.classList.toggle('bg-[#B8694C]', !state.rute);
                mb.classList.toggle('text-white', !state.rute);
                mb.classList.toggle('bg-white', state.rute);
                mb.classList.toggle('text-[#B8694C]', state.rute);
            }
        }

        function toggleRuteKos() {
            const k = cariKos(state.terpilih);
            if (!k) return;
            state.rute = !state.rute;
            updateTombolRute();
            if (state.rute && isMobileUI() && sheetSnap === 'full') {
                setSheetSnap('half');
                setTimeout(() => tampilkanGarisTerpilih(true), 330);
            } else {
                tampilkanGarisTerpilih(true);
            }
        }

        $('d-rute').addEventListener('click', toggleRuteKos);
        $('m-rute-cepat').addEventListener('click', toggleRuteKos);

        // ---------- Dropdown Custom (Desktop & Mobile) ----------
        const semuaDropdown = [];
        function buatDropdown(id, onChange, onBeforeOpen) {
            const root = $(id);
            const btn = root.querySelector('.dd-btn');
            const menu = root.querySelector('.dd-menu');
            const chev = root.querySelector('.dd-chev');
            const opts = [...root.querySelectorAll('.dd-opt')];

            function buka(v) {
                if (v) {
                    semuaDropdown.forEach(fn => { if (fn !== buka) fn(false); });
                    if (onBeforeOpen) onBeforeOpen();
                }
                menu.classList.toggle('hidden', !v);
                chev.classList.toggle('rotate-180', v);
                btn.classList.toggle('border-[#B8694C]', v);
                btn.classList.toggle('border-stone-200', !v);
                btn.setAttribute('aria-expanded', String(v));
            }
            function set(value, diam) {
                const opt = opts.find(o => o.dataset.value === value) || opts[0];
                root.dataset.value = opt.dataset.value;
                root.querySelector('.dd-label').textContent = opt.dataset.label;
                opts.forEach(o => {
                    const aktif = o === opt;
                    o.querySelector('.dd-check').classList.toggle('hidden', !aktif);
                    o.classList.toggle('bg-[#fbf5f3]', aktif);
                    o.classList.toggle('font-bold', aktif);
                    o.classList.toggle('text-[#B8694C]', aktif);
                });
                if (!diam && onChange) onChange(opt.dataset.value);
            }

            btn.addEventListener('click', e => { e.stopPropagation(); buka(menu.classList.contains('hidden')); });
            opts.forEach(o => o.addEventListener('click', e => { e.stopPropagation(); set(o.dataset.value); buka(false); }));
            document.addEventListener('click', () => buka(false));
            semuaDropdown.push(buka);

            return { set, buka };
        }

        const ddTipe = buatDropdown('dd-tipe', val => {
            state.tipe = val;
            muat({ fit: true });
        });

        let mDdUrut;
        const ddUrut = buatDropdown('dd-urut', val => {
            state.urut = val;
            if (mDdUrut) mDdUrut.set(val, true);
            muat();
        });

        mDdUrut = buatDropdown('m-dd-urut', val => {
            state.urut = val;
            ddUrut.set(val, true);
            muat();
        }, () => {
            if (isMobileUI() && sheetSnap === 'peek') {
                setSheetSnap('half');
            }
        });

        ddTipe.set('Semua', true);
        ddUrut.set('rekomendasi', true);
        mDdUrut.set('rekomendasi', true);

        $('m-daftar-scroll').addEventListener('scroll', () => mDdUrut.buka(false), { passive: true });

        // ---------- Event Handler Filter (2-Way Sync Desktop & Mobile) ----------
        const inHarga = $('f-harga');
        const inJarak = $('f-jarak');
        const mHarga = $('m-harga');
        const mJarak = $('m-jarak');

        function keteranganJarak(v) {
            const teks = v >= JARAK_MAKS
                ? 'Semua jarak lewat jalan.'
                : 'Maksimal ' + kmTeks(v) + ' lewat jalan ke ' + KAMPUS.nama + ' (± ' + menitDari(v * 1000) + ' menit jalan kaki).';
            $('f-jarak-label').textContent = kmTeks(v);
            $('m-jarak-label').textContent = kmTeks(v);
            $('f-jarak-ket').textContent = teks;
            $('m-jarak-ket').textContent = teks;
        }

        inHarga.addEventListener('input', () => {
            mHarga.value = inHarga.value;
            $('f-harga-label').textContent = rupiah(inHarga.value);
            $('m-harga-label').textContent = rupiah(inHarga.value);
        });
        mHarga.addEventListener('input', () => {
            inHarga.value = mHarga.value;
            $('f-harga-label').textContent = rupiah(mHarga.value);
            $('m-harga-label').textContent = rupiah(mHarga.value);
        });

        inJarak.addEventListener('input', () => {
            mJarak.value = inJarak.value;
            keteranganJarak(+inJarak.value);
        });
        mJarak.addEventListener('input', () => {
            inJarak.value = mJarak.value;
            keteranganJarak(+mJarak.value);
        });

        // Sinkronisasi checkbox fasilitas Desktop <-> Mobile
        document.querySelectorAll('.f-fasilitas').forEach(cb => {
            cb.addEventListener('change', () => {
                const mCb = document.querySelector('.m-fasilitas[value="' + cb.value + '"]');
                if (mCb) mCb.checked = cb.checked;
            });
        });
        document.querySelectorAll('.m-fasilitas').forEach(cb => {
            cb.addEventListener('change', () => {
                const dCb = document.querySelector('.f-fasilitas[value="' + cb.value + '"]');
                if (dCb) dCb.checked = cb.checked;
            });
        });

        function terapkan() {
            state.harga = +inHarga.value;
            state.jarak = +inJarak.value;
            state.fasilitas = [...document.querySelectorAll('.f-fasilitas:checked')].map(c => +c.value);
            gambarBuffer();
            gambarArea();
            muat({ fit: true });
        }

        function resetSemuaFilter() {
            inHarga.value = HARGA_MAX; mHarga.value = HARGA_MAX;
            inJarak.value = JARAK_MAKS; mJarak.value = JARAK_MAKS;
            ddTipe.set('Semua', true);
            state.tipe = 'Semua';
            document.querySelectorAll('.f-fasilitas, .m-fasilitas').forEach(c => c.checked = false);
            $('f-cari').value = ''; $('m-cari').value = ''; state.cari = '';
            $('btn-search-clear').classList.add('hidden');
            $('m-search-clear').classList.add('hidden');
            $('f-harga-label').textContent = rupiah(HARGA_MAX);
            $('m-harga-label').textContent = rupiah(HARGA_MAX);
            keteranganJarak(JARAK_MAKS);
            terapkan();
        }

        $('btn-terapkan').addEventListener('click', terapkan);
        $('m-btn-terapkan').addEventListener('click', () => {
            terapkan();
            bukaModalFilter(false);
            setMobileView('daftar', 'half');
        });

        $('btn-reset').addEventListener('click', resetSemuaFilter);
        $('m-btn-reset').addEventListener('click', resetSemuaFilter);

        // Chip Tipe Desktop, Chip Tipe Mobile, & Opsi Tipe di Modal Filter Mobile
        function pilihTipeKos(t, dariModal) {
            ddTipe.set(t, true);
            state.tipe = t;
            if (dariModal) {
                // Cukup update tampilan tombol di modal sampai user klik Terapkan Filter, atau langsung muat
                render({ tanpaPilihOtomatis: true });
            } else {
                muat({ fit: true });
            }
        }

        document.querySelectorAll('#chip-tipe .chip, #m-chip-tipe .m-chip').forEach(btn => {
            btn.addEventListener('click', () => pilihTipeKos(btn.dataset.tipe, false));
        });
        document.querySelectorAll('#m-filter-tipe .m-tipe-opt').forEach(btn => {
            btn.addEventListener('click', () => {
                ddTipe.set(btn.dataset.tipe, true);
                state.tipe = btn.dataset.tipe;
                document.querySelectorAll('#m-filter-tipe .m-tipe-opt').forEach(c => {
                    const on = c.dataset.tipe === state.tipe;
                    c.classList.toggle('bg-[#B8694C]', on);
                    c.classList.toggle('text-white', on);
                    c.classList.toggle('border-[#B8694C]', on);
                    c.classList.toggle('bg-white', !on);
                    c.classList.toggle('text-stone-700', !on);
                    c.classList.toggle('border-stone-200', !on);
                });
            });
        });

        // Input Cari (2-Way Sync Desktop & Mobile)
        let jedaCari = null;
        function sinkronCari(nilai) {
            state.cari = nilai;
            if ($('f-cari').value !== nilai) $('f-cari').value = nilai;
            if ($('m-cari').value !== nilai) $('m-cari').value = nilai;
            $('btn-search-clear').classList.toggle('hidden', !nilai);
            $('m-search-clear').classList.toggle('hidden', !nilai);
            clearTimeout(jedaCari);
            jedaCari = setTimeout(() => muat(), 300);
        }

        $('f-cari').addEventListener('input', e => sinkronCari(e.target.value));
        $('m-cari').addEventListener('input', e => sinkronCari(e.target.value));

        $('btn-search-clear').addEventListener('click', () => sinkronCari(''));
        $('m-search-clear').addEventListener('click', () => sinkronCari(''));

        // Mulai memuat data
        keteranganJarak(JARAK_MAKS);
        gambarBuffer();
        gambarArea();
        muat({ fit: true, pilih: AWAL_PILIH });
    </script>
@endpush
