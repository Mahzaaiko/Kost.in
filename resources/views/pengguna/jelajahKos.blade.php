@extends('layouts.pengguna')

@section('title', 'Jelajah Kos | Kost.in')
@section('body_class', 'bg-[#f7f5f2] overflow-hidden')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
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

        /* Sembunyikan kontrol zoom bawaan Leaflet karena dipindahkan ke sisi kiri peta */
        .leaflet-control-zoom { display: none !important; }
    </style>
@endpush

@section('content')
@php
    // Data berasal dari JelajahController; daftar kos diambil lewat API /api/kos.
    $fmtRingkas = fn ($n) => $n >= 1_000_000 ? 'Rp '.rtrim(rtrim(number_format($n / 1_000_000, 1, ',', '.'), '0'), ',').'jt' : 'Rp '.number_format($n / 1000, 0, ',', '.').'rb';
@endphp

    {{-- ========== PETA FULL SCREEN (BACKGROUND PENUH 100vw x 100vh) ========== --}}
    <div id="peta" class="fixed inset-0 h-screen w-screen z-0"></div>

    {{-- ========== OVERLAY SEARCH BAR TENGAH ATAS ========== --}}
    <div id="overlay-search" class="fixed top-20 left-1/2 -translate-x-1/2 z-[400] w-[calc(100%-2rem)] max-w-md pointer-events-auto">
        <div class="relative flex items-center rounded-full border border-stone-200/90 bg-white/95 px-4 py-2.5 shadow-lg backdrop-blur-md transition-all focus-within:border-[#B8694C] focus-within:ring-2 focus-within:ring-[#B8694C]/20">
            <svg class="h-4 w-4 shrink-0 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m0 0A7.5 7.5 0 105.2 5.2a7.5 7.5 0 0010.6 10.6z"/></svg>
            <input id="f-cari" type="text" value="{{ $awal['q'] }}" maxlength="100" placeholder="Cari nama kos atau alamat..." class="w-full bg-transparent pl-3 pr-2 text-xs sm:text-sm font-medium text-stone-800 placeholder:text-stone-400 focus:outline-none">
            <button type="button" id="btn-search-clear" class="hidden text-stone-400 hover:text-stone-700" title="Hapus pencarian">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- ========== KONTROL ZOOM & LOCATE (PINDAH KE SISI KIRI PETA) ========== --}}
    <div id="kontrol-zoom" class="fixed top-24 left-4 sm:left-[355px] z-[400] flex flex-col gap-2 pointer-events-auto">
        {{-- Capsule Tombol Zoom + / - --}}
        <div class="flex flex-col items-center rounded-2xl border border-stone-200/90 bg-white/95 shadow-md backdrop-blur-md overflow-hidden">
            <button type="button" id="btn-zoom-in" aria-label="Perbesar peta" class="flex h-9 w-9 items-center justify-center text-sm font-bold text-stone-800 transition hover:bg-[#fbf5f3] hover:text-[#B8694C]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>
            <div class="h-px w-6 bg-stone-200"></div>
            <button type="button" id="btn-zoom-out" aria-label="Perkecil peta" class="flex h-9 w-9 items-center justify-center text-sm font-bold text-stone-800 transition hover:bg-[#fbf5f3] hover:text-[#B8694C]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
            </button>
        </div>

        {{-- Tombol GPS Recenter ke Kampus SV IPB --}}
        <button type="button" id="btn-recenter" aria-label="Pusatkan ke Kampus SV IPB" class="flex h-9 w-9 items-center justify-center rounded-2xl border border-stone-200/90 bg-white/95 text-stone-800 shadow-md backdrop-blur-md transition hover:scale-105 hover:bg-[#fbf5f3] hover:text-[#B8694C]" title="Pusatkan ke Kampus SV IPB">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 2v2m0 16v2m-10-10h2m16 0h2"/></svg>
        </button>
    </div>

    {{-- ========== KONTROL BAWAH: LAYER PETA DI ATAS INFO PUTRA PUTRI CAMPUR ========== --}}
    <div id="kontrol-bawah" class="fixed bottom-5 left-4 sm:left-[355px] z-[400] pointer-events-auto flex flex-col items-start gap-2">
        {{-- 1. Opsi Layer Peta (Di Atas) --}}
        <div id="panel-layer" class="relative">
            <button id="tombol-layer" type="button" aria-expanded="false" aria-controls="isi-layer"
                    class="flex items-center gap-2 rounded-full border border-stone-200/90 bg-white/95 px-4 py-2 text-xs font-bold text-stone-800 shadow-lg backdrop-blur-md transition hover:border-[#B8694C] hover:text-[#B8694C]">
                <svg class="h-4 w-4 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4l-8 4l8 4l8 -4l-8 -4M4 12l8 4l8 -4M4 16l8 4l8 -4"/></svg>
                <span>Layer peta</span>
                <svg id="panah-layer" class="h-3.5 w-3.5 transition" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 15l6-6 6 6"/></svg>
            </button>

            {{-- Popup Isi Layer --}}
            <div id="isi-layer" class="scroll-thin absolute bottom-full left-0 mb-2 hidden max-h-[min(55vh,400px)] w-64 overflow-y-auto rounded-2xl border border-stone-200/90 bg-white/95 p-3.5 text-xs text-stone-600 shadow-xl backdrop-blur-md">
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

        {{-- 2. Info / Chips Tipe Kos (Di Bawah Layer Peta) --}}
        <div id="chip-tipe" class="flex items-center gap-1.5 rounded-full border border-stone-200/90 bg-white/95 p-1 shadow-lg backdrop-blur-md">
            <button type="button" data-tipe="Semua" class="chip flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold shadow-2xs transition"><span class="h-2 w-2 rounded-full bg-white chip-dot"></span>Semua</button>
            <button type="button" data-tipe="Putri" class="chip flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold shadow-2xs transition"><span class="h-2.5 w-2.5 rounded-full bg-pink-400"></span>Kos Putri</button>
            <button type="button" data-tipe="Putra" class="chip flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold shadow-2xs transition"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Kos Putra</button>
            <button type="button" data-tipe="Campur" class="chip flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold shadow-2xs transition"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>Campur</button>
        </div>
    </div>

    {{-- ========== PANEL KIRI MELAYANG: FILTER PENCARIAN & DAFTAR KOS (ROUNDED 3XL UTUH) ========== --}}
    <aside id="panel-kiri" class="fixed top-20 bottom-4 left-4 z-[400] w-[320px] sm:w-[335px] max-w-[calc(100vw-2rem)] flex flex-col rounded-3xl border border-stone-200/90 bg-white/95 shadow-xl backdrop-blur-md overflow-hidden pointer-events-auto">
        {{-- Header Panel Kiri --}}
        <div class="flex shrink-0 items-center justify-between border-b border-stone-100 bg-white/90 px-4 py-3 backdrop-blur-sm">
            <div class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#fbf5f3] text-[#B8694C]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4z"/></svg>
                </span>
                <h2 class="text-xs sm:text-sm font-bold text-stone-900">Filter Pencarian</h2>
            </div>
            <button type="button" id="btn-reset" class="text-[11px] font-bold text-[#B8694C] hover:underline" title="Reset filter">Reset</button>
        </div>

        {{-- Konten Scrollable di dalam Card (Bawah & Atas Selalu Mengikuti Rounded Card) --}}
        <div class="scroll-thin flex-1 overflow-y-auto p-4 space-y-4">
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
            <div>
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
    </aside>

    {{-- ========== TOMBOL MUNCULKAN PANEL KANAN (KETIKA DI-HIDE) ========== --}}
    <button type="button" id="btn-tampilkan-kanan" class="fixed top-20 right-4 z-[400] hidden items-center gap-1.5 rounded-full border border-stone-200 bg-white/95 px-3.5 py-2 text-xs font-bold text-stone-800 shadow-md backdrop-blur-md transition hover:border-[#B8694C] hover:text-[#B8694C]">
        <span>Detail Kos</span>
        <svg class="h-3.5 w-3.5 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </button>

    {{-- ========== PANEL KANAN MELAYANG: DETAIL KOS ========== --}}
    <aside id="panel-kanan" class="fixed top-20 bottom-4 right-4 z-[400] w-[320px] sm:w-[350px] xl:w-[370px] max-w-[calc(100vw-2rem)] flex flex-col rounded-3xl border border-stone-200/90 bg-white/95 shadow-xl backdrop-blur-md pointer-events-auto transition-transform duration-300 overflow-hidden">
        {{-- Header Panel Kanan: Detail Kos & Tombol Sembunyikan --}}
        <div class="flex shrink-0 items-center justify-between border-b border-stone-100 bg-white/80 px-4 py-2.5">
            <span class="flex items-center gap-1.5 text-xs font-bold text-stone-900">
                <svg class="h-4 w-4 text-[#B8694C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Detail Kos</span>
            </span>
            <button type="button" id="btn-sembunyikan-kanan" class="flex items-center gap-1 rounded-lg border border-stone-200 bg-white px-2 py-1 text-[11px] font-semibold text-stone-600 transition hover:border-[#B8694C] hover:text-[#B8694C]" title="Sembunyikan detail kos">
                <span>Sembunyikan</span>
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Konten Scrollable Panel Kanan --}}
        <div class="scroll-thin flex-1 overflow-y-auto">
            <div id="detail-kosong" class="flex h-full min-h-[260px] items-center justify-center p-8 text-center text-xs text-stone-500">
                Pilih kos di daftar atau di peta untuk melihat detailnya.
            </div>

            <div id="detail" class="hidden">
                {{-- Foto Kos: Diberi Frame Border & Margin Agar Rapi, Tidak Nempel Tepi, Tanpa Love --}}
                <div class="p-3.5 pb-0">
                    <div id="galeri" class="group relative aspect-[16/10] w-full overflow-hidden rounded-2xl border border-stone-200/90 bg-stone-100 shadow-sm">
                        <img id="d-foto-utama" src="" alt="" class="h-full w-full cursor-zoom-in select-none object-cover">

                        {{-- Tombol Panah Prev / Next --}}
                        <button id="g-prev" type="button" aria-label="Foto sebelumnya" class="absolute left-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-md transition hover:bg-white hover:text-[#B8694C]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button id="g-next" type="button" aria-label="Foto berikutnya" class="absolute right-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-md transition hover:bg-white hover:text-[#B8694C]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        {{-- Tombol Perbesar / Zoom (Kiri Atas) --}}
                        <button id="g-zoom" type="button" aria-label="Perbesar foto" class="absolute left-2.5 top-2.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-stone-700 shadow-sm transition hover:bg-white hover:text-[#B8694C]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/></svg>
                        </button>

                        {{-- Counter Foto (Kanan Atas, persis seperti mockup) --}}
                        <span id="g-hitung" class="absolute right-2.5 top-2.5 rounded-full bg-black/60 px-2.5 py-0.5 text-[11px] font-bold text-white backdrop-blur-sm shadow-sm"></span>
                    </div>
                </div>

                {{-- Deskripsi & Fasilitas --}}
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
    </aside>

    {{-- ========== LIGHTBOX FOTO PENUH ========== --}}
    <div id="lightbox" class="fixed inset-0 z-[3000] hidden items-center justify-center bg-black/90 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
        <button id="lb-tutup" type="button" aria-label="Tutup" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <button id="lb-prev" type="button" aria-label="Foto sebelumnya" class="absolute left-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button id="lb-next" type="button" aria-label="Foto berikutnya" class="absolute right-4 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
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
        const HARGA_MIN = {{ $hargaMin }};
        const HARGA_MAX = {{ $hargaMax }};
        const JARAK_MAKS = {{ $jarakMaks }};
        const KAMPUS = @json($kampus);
        const AWAL = @json($awal);
        const M_PER_MENIT = {{ (float) config('kostin.routing.kecepatan_jalan_kmh') * 1000 / 60 }};
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
        
        // Keterangan jarak kos
        const jarakKos = k => k.jarak_rute_m !== null && k.jarak_rute_m !== undefined
            ? jarakTeks(k.jarak_rute_m) + ' lewat jalan · ± ' + k.waktu_jalan_menit + ' menit jalan kaki'
            : jarakTeks(k.jarak_m) + ' dari kampus (garis lurus)';
        const skorTeks = s => s === null || s === undefined ? '' : Number(s).toFixed(2).replace('.', ',');
        const $ = id => document.getElementById(id);
        const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        // ---------- Inisialisasi Peta ----------
        const map = L.map('peta', { zoomControl: false }).setView([KAMPUS.lat, KAMPUS.lng], 14);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        ['overlay-search', 'kontrol-zoom', 'kontrol-bawah', 'panel-kiri', 'panel-kanan'].forEach(id => {
            const el = $(id);
            if (el) {
                L.DomEvent.disableClickPropagation(el);
                L.DomEvent.disableScrollPropagation(el);
            }
        });

        // Kontrol Zoom Kustom di Kiri Peta
        $('btn-zoom-in').addEventListener('click', () => map.zoomIn());
        $('btn-zoom-out').addEventListener('click', () => map.zoomOut());
        $('btn-recenter').addEventListener('click', () => map.flyTo([KAMPUS.lat, KAMPUS.lng], 15, { duration: 0.6 }));

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

        // ---------- Kontrol Buka/Tutup Panel Kanan ----------
        function bukaPanelKanan() {
            $('panel-kanan').classList.remove('translate-x-[calc(100%+2rem)]', 'pointer-events-none');
            $('btn-tampilkan-kanan').classList.add('hidden');
            $('btn-tampilkan-kanan').classList.remove('flex');
        }

        function tutupPanelKanan() {
            $('panel-kanan').classList.add('translate-x-[calc(100%+2rem)]', 'pointer-events-none');
            $('btn-tampilkan-kanan').classList.remove('hidden');
            $('btn-tampilkan-kanan').classList.add('flex');
        }

        $('btn-sembunyikan-kanan').addEventListener('click', tutupPanelKanan);
        $('btn-tampilkan-kanan').addEventListener('click', bukaPanelKanan);

        // ---------- Layer Peta Setup ----------
        const LAYER = {
            kos: { layer: layerKos },
            area: { layer: layerArea },
            buffer: { layer: layerBuffer },
            jangkauan: { layer: layerJangkauan, muat: () => muatLayer(layerJangkauan, URL_JANGKAUAN, gambarJangkauan) },
            jalan: { layer: layerJalan, muat: () => muatLayer(layerJalan, URL_JALAN, gambarJalan) }
        };
        document.querySelectorAll('#isi-layer [data-layer]').forEach(cb => {
            cb.addEventListener('change', () => {
                const l = LAYER[cb.dataset.layer];
                if (cb.checked) { l.layer.addTo(map); l.muat && l.muat(); } else { map.removeLayer(l.layer); }
                document.querySelector('[data-legenda="' + cb.dataset.layer + '"]').classList.toggle('hidden', !cb.checked);
            });
        });
        $('tombol-layer').addEventListener('click', () => {
            const buka = $('isi-layer').classList.toggle('hidden') === false;
            $('tombol-layer').setAttribute('aria-expanded', String(buka));
            $('panah-layer').classList.toggle('rotate-180', buka);
        });

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
            try {
                const res = await fetch(URL_API + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (nomor !== permintaan) return;
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

        // ---------- Render Seluruh Tampilan ----------
        function render(opsi) {
            $('jumlah-kos').textContent = daftar.length;
            $('kosong').classList.toggle('hidden', daftar.length > 0);
            $('ket-skor').classList.toggle('hidden', state.urut !== 'rekomendasi' || daftar.length === 0);

            if (opsi && opsi.pilih && cariKos(opsi.pilih)) state.terpilih = opsi.pilih;
            if (state.terpilih && !cariKos(state.terpilih)) { state.terpilih = null; state.rute = false; }
            if (!state.terpilih && daftar.length && !(opsi && opsi.tanpaPilihOtomatis)) state.terpilih = daftar[0].id;

            // Render Daftar Kos
            const ul = $('daftar-kos');
            ul.innerHTML = '';
            daftar.forEach((k, i) => {
                const li = document.createElement('li');
                const aktif = k.id === state.terpilih;
                const skor = state.urut === 'rekomendasi'
                    ? '<span class="ml-auto shrink-0 rounded-md bg-[#fbf5f3] px-1.5 py-0.5 text-[10px] font-bold text-[#B8694C]" title="Skor rekomendasi">#' + (i + 1) + ' · ' + skorTeks(k.skor) + '</span>'
                    : '';
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
            });

            // Render Marker di Peta dengan Popup Interaktif
            layerKos.clearLayers();
            Object.keys(markers).forEach(id => delete markers[id]);
            daftar.forEach(k => {
                const m = L.marker([k.lat, k.lng], { icon: ikonKos(k, k.id === state.terpilih), zIndexOffset: k.id === state.terpilih ? 900 : 0 });
                
                // Popup card interaktif yang rapi di atas pin
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
                    pilih(k.id, false);
                    bukaPanelKanan();
                });

                markers[k.id] = m;
                layerKos.addLayer(m);
            });

            // Delegasi klik pada konten popup peta
            map.on('popupopen', e => {
                const el = e.popup.getElement();
                if (el) {
                    const row = el.querySelector('[data-kos-id]');
                    if (row) {
                        row.addEventListener('click', () => {
                            const id = +row.dataset.kosId;
                            pilih(id, false);
                            bukaPanelKanan();
                        });
                    }
                }
            });

            tampilkanGarisTerpilih();

            // Status Chip Tipe
            document.querySelectorAll('#chip-tipe .chip').forEach(c => {
                const on = c.dataset.tipe === state.tipe;
                c.classList.toggle('bg-[#B8694C]', on);
                c.classList.toggle('text-white', on);
                c.classList.toggle('border-[#B8694C]', on);
                c.classList.toggle('bg-white/95', !on);
                c.classList.toggle('text-stone-700', !on);
                c.classList.toggle('border-stone-200/90', !on);
            });

            tampilkanDetail();

            if (opsi && opsi.fit) fitKeFilter();
            if (opsi && opsi.pilih && cariKos(opsi.pilih)) {
                const k = cariKos(opsi.pilih);
                map.flyTo([k.lat, k.lng], Math.max(map.getZoom(), 15), { duration: 0.6 });
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
                setTimeout(() => { delete ruteKos[id]; }, 30000);
            }
            if (state.terpilih === id && state.rute) tampilkanGarisTerpilih(fit);
        }

        // ---------- Tampilkan Detail Kos di Panel Kanan ----------
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
            $('d-tipe').className = 'shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold ' + (BADGE[k.tipe] || '');
            $('d-harga').textContent = rupiah(k.harga);
            $('d-alamat').textContent = k.alamat || '';
            $('d-jarak').textContent = k.jarak_rute_m !== null && k.jarak_rute_m !== undefined
                ? jarakTeks(k.jarak_rute_m) + ' lewat jalan ke ' + KAMPUS.nama + ' (± ' + k.waktu_jalan_menit + ' menit jalan kaki)'
                : jarakTeks(k.jarak_m) + ' dari ' + KAMPUS.nama + ' (garis lurus)';
            $('d-deskripsi').textContent = k.deskripsi || '';
            $('d-deskripsi').classList.toggle('hidden', !k.deskripsi);
            $('d-deskripsi-hr').classList.toggle('hidden', !k.deskripsi);
            $('d-fasilitas').innerHTML = k.fasilitas.map(f =>
                '<div class="flex flex-col items-center gap-1 text-center">' +
                '<span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B8694C] shadow-2xs">' + (IKON[f.ikon] || IKON['default']) + '</span>' +
                '<span class="text-[10px] leading-tight font-medium text-stone-600">' + esc(f.nama) + '</span></div>'
            ).join('') || '<p class="col-span-4 text-xs text-stone-400">Belum ada data fasilitas.</p>';
            $('d-tipe-teks').textContent = 'Kos ' + k.tipe;
            $('d-periode').textContent = k.periode || '-';
            $('d-telepon').textContent = k.telepon || '-';
            updateTombolRute();
            catatDilihat(k.id);
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
            if (k && geser) map.flyTo([k.lat, k.lng], Math.max(map.getZoom(), 15), { duration: 0.6 });
        }

        function fitKeFilter() {
            if (state.jarak < JARAK_MAKS || daftar.length === 0) {
                map.fitBounds(L.latLng(KAMPUS.lat, KAMPUS.lng).toBounds(state.jarak * 2000), { padding: [50, 50] });
                return;
            }
            const batas = L.latLngBounds([[KAMPUS.lat, KAMPUS.lng], ...daftar.map(k => [k.lat, k.lng])]);
            map.fitBounds(batas, { padding: [70, 70], maxZoom: 16 });
        }

        // ---------- Galeri Foto + Lightbox ----------
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

        // ---------- Tombol Rute ----------
        function updateTombolRute() {
            const b = $('d-rute');
            if (!b) return;
            $('d-rute-teks').textContent = state.rute ? 'Sembunyikan Rute' : 'Lihat Rute';
            b.classList.toggle('bg-[#B8694C]', !state.rute);
            b.classList.toggle('text-white', !state.rute);
            b.classList.toggle('bg-white', state.rute);
            b.classList.toggle('text-[#B8694C]', state.rute);
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

        // ---------- Dropdown Custom (Tipe & Urut) ----------
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

            return { set };
        }

        const ddTipe = buatDropdown('dd-tipe', val => {
            state.tipe = val;
            muat({ fit: true });
        });

        const ddUrut = buatDropdown('dd-urut', val => {
            state.urut = val;
            muat();
        });

        // ---------- Event Handler Filter ----------
        const inHarga = $('f-harga');
        const inJarak = $('f-jarak');

        function keteranganJarak() {
            const v = +inJarak.value;
            $('f-jarak-label').textContent = kmTeks(v);
            $('f-jarak-ket').textContent = v >= JARAK_MAKS
                ? 'Semua jarak lewat jalan.'
                : 'Maksimal ' + kmTeks(v) + ' lewat jalan ke ' + KAMPUS.nama + ' (± ' + menitDari(v * 1000) + ' menit jalan kaki).';
        }

        inHarga.addEventListener('input', () => { $('f-harga-label').textContent = rupiah(inHarga.value); });
        inJarak.addEventListener('input', keteranganJarak);

        function terapkan() {
            state.harga = +inHarga.value;
            state.jarak = +inJarak.value;
            state.fasilitas = [...document.querySelectorAll('.f-fasilitas:checked')].map(c => +c.value);
            gambarBuffer();
            gambarArea();
            muat({ fit: true });
        }

        $('btn-terapkan').addEventListener('click', terapkan);

        $('btn-reset').addEventListener('click', () => {
            inHarga.value = HARGA_MAX; inJarak.value = JARAK_MAKS; ddTipe.set('Semua', true);
            document.querySelectorAll('.f-fasilitas').forEach(c => c.checked = false);
            $('f-cari').value = ''; state.cari = '';
            $('f-harga-label').textContent = rupiah(HARGA_MAX);
            keteranganJarak();
            terapkan();
        });

        // Chip Tipe
        document.querySelectorAll('#chip-tipe .chip').forEach(btn => {
            btn.addEventListener('click', () => {
                const t = btn.dataset.tipe;
                ddTipe.set(t, true);
                state.tipe = t;
                muat({ fit: true });
            });
        });

        // Input Cari
        let jedaCari = null;
        $('f-cari').addEventListener('input', e => {
            state.cari = e.target.value;
            $('btn-search-clear').classList.toggle('hidden', !state.cari);
            clearTimeout(jedaCari);
            jedaCari = setTimeout(() => muat(), 300);
        });

        $('btn-search-clear').addEventListener('click', () => {
            $('f-cari').value = '';
            state.cari = '';
            $('btn-search-clear').classList.add('hidden');
            muat();
        });

        // Mulai memuat data
        keteranganJarak();
        gambarBuffer();
        gambarArea();
        muat({ fit: true, pilih: AWAL.pilih ? +AWAL.pilih : null });
    </script>
@endpush
