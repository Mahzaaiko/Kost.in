@extends('layouts.pengguna')

@section('title', 'Kost.in | Cari Kost Nyaman di Sekitar SV IPB University')
@section('body_class', 'bg-[#D5EEE0]')

@push('styles')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js"></script>
    <style>
        html { scroll-padding-top: 88px; }

        #hero-lottie-container svg,
        #leaves-lottie-container svg {
            width: 100% !important;
            height: 100% !important;
            display: block !important;
        }

        /* ================= 4 CARDS INTERACTIVE TILTED FAN ANIMATION (PERSIS GAMBAR 8 FIGMA) ================= */
        .fanned-deck {
            perspective: 1200px;
        }

        .fanned-card {
            transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), 
                        box-shadow 0.4s cubic-bezier(0.22, 1, 0.36, 1), 
                        border-color 0.3s ease, 
                        opacity 0.35s ease;
            transform-origin: center bottom;
            will-change: transform;
        }

        @media (min-width: 1024px) {
            /* Susunan persis referensi user (referensi_4_card.png): selang-seling naik-turun bertumpuk menyamping */
            .fanned-deck .card-1 {
                transform: rotate(-5deg) translateY(-18px);
                z-index: 1;
            }
            .fanned-deck .card-2 {
                transform: rotate(3.5deg) translateY(18px);
                z-index: 2;
            }
            .fanned-deck .card-3 {
                transform: rotate(-3deg) translateY(-16px);
                z-index: 3;
            }
            .fanned-deck .card-4 {
                transform: rotate(4deg) translateY(16px);
                z-index: 4;
            }

            /* Saat kartu di-hover: tegak lurus (rotate 0deg), terpisah naik, di depan */
            .fanned-deck .fanned-card:hover {
                transform: translateY(-34px) rotate(0deg) scale(1.06) !important;
                z-index: 50 !important;
                box-shadow: 0 25px 40px -12px rgba(20, 56, 33, 0.22), 0 10px 16px -6px rgba(20, 56, 33, 0.1) !important;
                border-color: #B8694C !important;
                opacity: 1 !important;
            }

            .fanned-deck:hover .fanned-card:not(:hover) {
                opacity: 0.88;
            }
        }

        @media (max-width: 1023px) {
            .fanned-card {
                transform: none !important;
            }
            .fanned-card:hover {
                transform: translateY(-6px) scale(1.02) !important;
                border-color: #B8694C !important;
                box-shadow: 0 16px 28px -8px rgba(20, 56, 33, 0.15) !important;
            }
        }

        /* ================= INTERAKTIVITAS MAP 3D POIN (HIGHLIGHT LEMBUT TANPA POPUP/GESER) ================= */
        .map-district {
            transition: filter 0.25s ease, opacity 0.25s ease;
            cursor: pointer;
        }
        .map-district:hover {
            filter: brightness(1.15) saturate(1.12);
            opacity: 0.96;
        }

        .map-pin {
            pointer-events: none !important;
            cursor: default !important;
        }
    </style>
@endpush

@section('content')
    @php
        $warnaBadge = [
            'Putri'  => 'bg-pink-50 text-pink-700 border-pink-200',
            'Putra'  => 'bg-blue-50 text-blue-700 border-blue-200',
            'Campur' => 'bg-purple-50 text-purple-700 border-purple-200',
        ];

        $labelBadge = [
            'Putri'  => 'Kost Putri',
            'Putra'  => 'Kost Putra',
            'Campur' => 'Kost Campur',
        ];
    @endphp

    {{-- ================= SECTION 1: HERO HEADER ================= --}}
    <section class="relative w-full overflow-hidden bg-gradient-to-b from-[#D5EEE0] via-[#DFF2E6] to-[#E6F5E8]">

        {{-- 1. WRAPPER TABLET & DESKTOP: Mengunci aspek rasio asli 1280/1080 kanvas JSON tanpa ruang kosong --}}
        <div class="hidden md:block relative w-full aspect-[1280/1080] overflow-hidden select-none">
            {{-- Container Bodymovin Lottie Hero --}}
            <div id="hero-lottie-container" class="absolute inset-0 w-full h-full pointer-events-none"></div>

            {{-- Fallback visual instan sebelum Lottie selesai memuat --}}
            <div id="hero-fallback" class="absolute inset-0 w-full h-full pointer-events-none transition-opacity duration-700">
                <img src="{{ asset('images/Main Content.svg') }}" alt="Ilustrasi Hero Kost.in" class="w-full h-full object-cover object-top opacity-90">
            </div>

            {{-- Overlay Konten Teks Hero di Kiri --}}
            <div class="absolute inset-0 z-10 mx-auto w-full max-w-7xl px-6 lg:px-8 pt-24 md:pt-28 lg:pt-32 xl:pt-36 pointer-events-none flex flex-col justify-start">
                <div class="max-w-xl lg:max-w-[46%] xl:max-w-[48%] pointer-events-auto">

                    {{-- Pill Tag Rekomendasi --}}
                    <div class="inline-flex items-center gap-2 rounded-full border border-[#D4EAD2] bg-white/90 px-3.5 py-1.5 text-xs font-bold text-[#163A27] shadow-sm backdrop-blur-sm">
                        <span class="h-2 w-2 rounded-full bg-[#0E7A4D] animate-pulse"></span>
                        <span class="tracking-wide">REKOMENDASI KOST SV IPB</span>
                    </div>

                    {{-- Headline Utama 4 Baris --}}
                    <h1 class="mt-4 sm:mt-5 text-3xl sm:text-5xl lg:text-[3.25rem] xl:text-[3.4rem] font-black tracking-tight leading-[1.12] text-[#163A27]">
                        <span class="text-[#B8694C]">Cari kost nyaman</span> <span class="text-[#163A27]">di</span><br>
                        <span class="text-[#163A27]">sekitar</span> <span class="text-[#0E7A4D]">Sekolah</span><br>
                        <span class="text-[#0E7A4D]">Vokasi IPB</span><br>
                        <span class="text-[#0E7A4D]">University!</span>
                    </h1>

                    {{-- Subheadline Deskripsi --}}
                    <p class="mt-4 sm:mt-5 max-w-lg text-sm sm:text-base leading-relaxed text-[#244B35] font-medium drop-shadow-sm">
                        Jelajahi kos, bandingkan harga dan fasilitas, lalu temukan yang paling pas buatmu.
                    </p>

                    {{-- Search Bar Pill Putih Terkoneksi Backend GET q --}}
                    <form action="{{ route('pengguna.jelajahKos') }}" method="GET"
                          class="mt-6 sm:mt-7 flex max-w-lg items-center overflow-hidden rounded-full border border-[#E6DEC8] bg-white p-1.5 shadow-lg transition focus-within:border-[#B8694C] focus-within:ring-2 focus-within:ring-[#B8694C]/25 backdrop-blur-sm">
                        <div class="flex items-center pl-4 text-stone-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="q" placeholder="Cari area, kampus, atau stasiun"
                               class="min-w-0 flex-1 bg-transparent px-3 py-2 sm:py-2.5 text-xs sm:text-sm text-stone-800 placeholder-stone-400 focus:outline-none">
                        <button type="submit" aria-label="Cari kost"
                                class="shrink-0 rounded-full bg-[#B8694C] px-5 sm:px-6 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm transition hover:bg-[#955844]">
                            Cari Kost
                        </button>
                    </form>

                    {{-- Counter Terverifikasi --}}
                    <div class="mt-4 flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-[#244B35] drop-shadow-sm">
                        <span class="text-base sm:text-lg font-black text-[#163A27]">{{ $jumlahKos }}+</span>
                        <span>kost terverifikasi</span>
                    </div>

                </div>
            </div>

            {{-- GELOMBANG TRANSISI 1: Menggunakan gelombang asli yang diberikan pengguna (gelombang_header_asli.png) --}}
            <div class="absolute bottom-0 left-0 right-0 w-full overflow-hidden leading-none pointer-events-none select-none z-20">
                <img src="{{ asset('images/gelombang_header_asli.png') }}" alt="" class="w-full h-auto block -mb-[2px]">
            </div>
        </div>

        {{-- 2. WRAPPER MOBILE (block md:hidden): Responsif bersih & proporsional untuk smartphone --}}
        <div class="block md:hidden relative w-full overflow-hidden pt-24 pb-0 bg-gradient-to-b from-[#D5EEE0] via-[#DFF2E6] to-[#E6F5E8]">
            <div class="relative z-10 px-5">
                {{-- Pill Tag Rekomendasi --}}
                <div class="inline-flex items-center gap-2 rounded-full border border-[#D4EAD2] bg-white/90 px-3.5 py-1 text-xs font-bold text-[#163A27] shadow-sm backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-[#0E7A4D] animate-pulse"></span>
                    <span class="tracking-wide">REKOMENDASI KOST SV IPB</span>
                </div>

                {{-- Headline Utama --}}
                <h1 class="mt-4 text-2xl sm:text-3xl font-black tracking-tight leading-tight text-[#163A27]">
                    <span class="text-[#B8694C]">Cari kost nyaman</span> <span class="text-[#163A27]">di sekitar</span>
                    <span class="text-[#0E7A4D]">Sekolah Vokasi IPB University!</span>
                </h1>

                {{-- Subheadline --}}
                <p class="mt-3 text-xs sm:text-sm leading-relaxed text-[#244B35] font-medium">
                    Jelajahi kos, bandingkan harga dan fasilitas, lalu temukan yang paling pas buatmu.
                </p>

                {{-- Search Bar Pill Putih Terkoneksi Backend GET q --}}
                <form action="{{ route('pengguna.jelajahKos') }}" method="GET"
                      class="mt-5 flex w-full items-center overflow-hidden rounded-full border border-[#E6DEC8] bg-white p-1.5 shadow-md">
                    <div class="flex items-center pl-3 text-stone-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="q" placeholder="Cari area, kampus, atau stasiun"
                           class="min-w-0 flex-1 bg-transparent px-2.5 py-2 text-xs text-stone-800 placeholder-stone-400 focus:outline-none">
                    <button type="submit" aria-label="Cari kost"
                            class="shrink-0 rounded-full bg-[#B8694C] px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-[#955844]">
                        Cari Kost
                    </button>
                </form>

                {{-- Counter Terverifikasi --}}
                <div class="mt-3 flex items-center gap-1.5 text-xs font-semibold text-[#244B35]">
                    <span class="text-base font-black text-[#163A27]">{{ $jumlahKos }}+</span>
                    <span>kost terverifikasi</span>
                </div>

                {{-- Ilustrasi Peta Header di Mobile (Tunggal & Rapi) --}}
                <div class="mt-6 flex justify-center">
                    <img src="{{ asset('images/map_3d_header.svg') }}" alt="Ilustrasi SV IPB" class="w-full max-w-[280px] sm:max-w-[320px] h-auto drop-shadow-md">
                </div>
            </div>

            {{-- GELOMBANG TRANSISI 1 di Mobile Menuju Section Tentang (#DDEDC4) --}}
            <div class="relative w-full overflow-hidden leading-none mt-6 pointer-events-none select-none z-10">
                <img src="{{ asset('images/gelombang_header_asli.png') }}" alt="" class="w-full h-auto block -mb-[2px]">
            </div>
        </div>

    </section>

    {{-- ================= WRAPPER DENGAN ANIMASI DAUN JATUH (landing 4.json) ================= --}}
    {{-- Posisi daun di atas background warna section (z-10), tetapi di belakang kartu & teks (z-20) --}}
    <div id="falling-leaves-wrapper" class="relative overflow-hidden bg-[#DDEDC4] -mt-1 sm:-mt-2">
        
        {{-- Animasi Lottie Daun Jatuh murni dari landing 4.json menggunakan Bodymovin Lottie --}}
        <div id="leaves-lottie-container" class="pointer-events-none absolute inset-0 overflow-hidden z-10" aria-hidden="true"></div>

        {{-- ================= SECTION 2: TENTANG KOST.IN (#DDEDC4) ================= --}}
        <section id="tentang" class="relative bg-[#DDEDC4] pt-8 pb-0 sm:pt-12 lg:pt-16">
            <div class="relative z-20 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                
                {{-- Header Tentang --}}
                <div class="text-center">
                    <span class="inline-block text-xs font-black uppercase tracking-widest text-[#7E6722]">
                        TENTANG KOST.IN
                    </span>
                    <h2 class="mt-2 text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-black tracking-tight text-[#163A27]">
                        Kenapa memilih Kost.in?
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-xs sm:text-sm lg:text-base leading-relaxed text-[#244B35] font-medium">
                        Kost.in hadir untuk membantu kamu menemukan kost terbaik di sekitar Sekolah Vokasi IPB University, dengan informasi yang akurat dan mudah di akses.
                    </p>
                </div>

                {{-- 4 CARDS: SUSUNAN PERSIS GAMBAR 8 FIGMA (TILTED OVERLAPPING FAN DECK) --}}
                <div class="mt-12 sm:mt-16 lg:mt-20">
                    <div id="deck-tentang" class="fanned-deck mx-auto flex flex-col sm:flex-row flex-wrap lg:flex-nowrap justify-center items-center gap-6 lg:gap-0 lg:-space-x-8 max-w-5xl pt-4 pb-6">
                        
                        {{-- Card 01: Data Terverifikasi (Tilt Left -5deg, z-1) --}}
                        <div class="fanned-card card-1 w-full max-w-[270px] sm:w-[260px] lg:w-[265px] shrink-0 rounded-3xl border border-[#EFE3CC] bg-[#FDF7EA] p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#FDE7D2] text-xs font-black text-[#C2410C]">
                                    01
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#EAF6E8] text-[#0E7A4D]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-[#163A27] leading-snug">
                                Data Terverifikasi
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-[#244B35] font-medium">
                                Informasi telah melalui pengecekan lokasi dan konfirmasi langsung dengan pemilik kost.
                            </p>
                        </div>

                        {{-- Card 02: Filter Lengkap (Tilt Right +3.5deg, Overlap, z-2) --}}
                        <div class="fanned-card card-2 w-full max-w-[270px] sm:w-[260px] lg:w-[265px] shrink-0 rounded-3xl border border-[#D4EAD2] bg-[#EAF6E8] p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#CBE6C9] text-xs font-black text-[#15803D]">
                                    02
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#CDE7C1] text-[#0E7A4D]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-[#163A27] leading-snug">
                                Filter Lengkap
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-[#244B35] font-medium">
                                Sesuaikan pencarian kost berdasarkan harga, fasilitas, dan jarak ke kampus.
                            </p>
                        </div>

                        {{-- Card 03: Hubungi Langsung (Tilt Left -3deg, Overlap, z-3) --}}
                        <div class="fanned-card card-3 w-full max-w-[270px] sm:w-[260px] lg:w-[265px] shrink-0 rounded-3xl border border-[#EFE3CC] bg-[#FDF7EA] p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#FDE7D2] text-xs font-black text-[#C2410C]">
                                    03
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#FFEDD5] text-[#C2410C]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-[#163A27] leading-snug">
                                Hubungi Langsung
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-[#244B35] font-medium">
                                Terhubung langsung ke nomor WhatsApp pemilik kost tanpa melalui perantara.
                            </p>
                        </div>

                        {{-- Card 04: 100% Gratis (Tilt Right +4deg, Overlap, z-4) --}}
                        <div class="fanned-card card-4 w-full max-w-[270px] sm:w-[260px] lg:w-[265px] shrink-0 rounded-3xl border border-[#D4EAD2] bg-[#EAF6E8] p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#CBE6C9] text-xs font-black text-[#15803D]">
                                    04
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#CDE7C1] text-[#0E7A4D]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-[#163A27] leading-snug">
                                100% Gratis
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-[#244B35] font-medium">
                                Tanpa biaya admin maupun biaya tambahan lainnya bagi pencari kost.
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- GELOMBANG TRANSISI 2: Menuju Section Kos Populer (#7CB75A) menggunakan gelombang asli gelombang_kos.png --}}
            <div class="relative w-full overflow-hidden leading-none pointer-events-none select-none mt-12 sm:mt-16 lg:mt-20 -mb-[1px] z-20">
                <img src="{{ asset('images/gelombang_kos.png') }}" alt="" class="w-full h-auto block">
            </div>
        </section>

        {{-- ================= SECTION 3: KOST POPULER (#7CB75A) ================= --}}
        <section id="kos" class="relative z-10 bg-[#7CB75A] pt-4 sm:pt-6 pb-0 text-white">
            <div class="relative z-20 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                
                {{-- Header Section --}}
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <span class="inline-block text-xs font-black uppercase tracking-widest text-[#143B22]">
                            PALING DIMINATI
                        </span>
                        <h2 class="mt-2 text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-[#11331D]">
                            Kost yang paling banyak dilihat
                        </h2>
                        <p class="mt-2 text-sm text-[#194529] max-w-xl font-medium">
                            Pilihan kost terpopuler dengan fasilitas lengkap, nyaman, dan harga bulanan yang terjangkau.
                        </p>
                    </div>
                    <a href="{{ route('pengguna.jelajahKos') }}" class="group inline-flex items-center gap-1.5 text-sm font-bold text-white hover:text-emerald-100 transition shrink-0">
                        <span>Lihat semua kost</span>
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>

                {{-- Grid Kartu Kos Sesuai Referensi Gambar 2 --}}
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($kosKartu as $kos)
                        <article class="group flex flex-col overflow-hidden rounded-3xl bg-[#FDF8EE] border border-[#EFE5D1] p-3.5 shadow-md transition duration-300 hover:-translate-y-1.5 hover:shadow-2xl">
                            
                            {{-- Foto Kos (Polos tanpa badge di atasnya sesuai gambar 2) --}}
                            <div class="relative aspect-[16/10] overflow-hidden rounded-2xl bg-stone-100">
                                @if ($kos['foto'])
                                    <img src="{{ $kos['foto'] }}" alt="{{ $kos['nama'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-emerald-50 text-emerald-700">
                                        <svg class="h-10 w-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Info Kos --}}
                            <div class="flex flex-1 flex-col p-2.5 pt-4">
                                {{-- Baris 1: Nama Kos di Kiri, Badge Kategori di Kanan Sesuai Gambar 2 --}}
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="font-extrabold text-stone-900 text-base leading-snug line-clamp-1 group-hover:text-[#B8694C] transition-colors">
                                        {{ $kos['nama'] }}
                                    </h3>
                                    <span class="shrink-0 rounded-full border px-3 py-0.5 text-xs font-bold tracking-wide shadow-xs {{ $warnaBadge[$kos['tipe']] ?? 'bg-stone-100 text-stone-700 border-stone-200' }}">
                                        {{ $labelBadge[$kos['tipe']] ?? ('Kost ' . $kos['tipe']) }}
                                    </span>
                                </div>

                                {{-- Baris 2: Jarak Kampus --}}
                                <p class="mt-2 flex items-center gap-1.5 text-xs text-stone-500 font-medium">
                                    <svg class="h-3.5 w-3.5 text-[#B8694C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>{{ $kos['jarak'] }} dari kampus</span>
                                </p>

                                {{-- Baris 3: Fasilitas Chips --}}
                                <div class="mt-3.5 flex flex-wrap gap-1.5">
                                    @foreach ($kos['fasilitas'] as $fas)
                                        <span class="rounded-md border border-[#E6DEC8] bg-[#F5EFE0] px-2.5 py-0.5 text-[11px] font-semibold text-stone-700">
                                             {{ $fas }}
                                        </span>
                                    @endforeach
                                </div>

                                {{-- Baris 4: Harga & Tombol Panah --}}
                                <div class="mt-5 flex items-center justify-between border-t border-[#EFE5D1] pt-3.5">
                                    <div>
                                        <span class="text-base font-black text-[#B8694C]">{{ $kos['harga_fmt'] }}</span>
                                        <span class="text-xs text-stone-500 font-normal">/bulan</span>
                                    </div>
                                    <a href="{{ route('pengguna.jelajahKos', ['kos' => $kos['id']]) }}" aria-label="Lihat detail {{ $kos['nama'] }}" class="flex h-8 w-8 items-center justify-center rounded-full bg-[#B8694C] text-white shadow-sm transition hover:bg-[#955844] hover:scale-105">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>

                        </article>
                    @empty
                        <p class="col-span-full rounded-2xl border border-dashed border-emerald-300 bg-white/10 p-8 text-center text-sm text-emerald-100">
                            Belum ada data kos yang ditampilkan.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- GELOMBANG TRANSISI 3: Menuju Section Peta (#DDEDC4) menggunakan gelombang asli gelombang_peta.png --}}
            <div class="relative w-full overflow-hidden leading-none pointer-events-none select-none mt-12 sm:mt-16 lg:mt-20 -mb-[1px] z-20">
                <img src="{{ asset('images/gelombang_peta.png') }}" alt="" class="w-full h-auto block">
            </div>
        </section>

        {{-- ================= SECTION 4: ANTI TELAT NGAMPUS (PETA TIMBUL) (#DDEDC4) ================= --}}
        {{-- Menggunakan partial map_poin.blade.php dengan interaktivitas highlight saat kursor diarahkan ke wilayah map/pin --}}
        <section id="peta" class="relative bg-[#DDEDC4] pt-4 sm:pt-6 pb-0">
            <div class="relative z-20 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pb-10 sm:pb-14 lg:pb-16">
                <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-14">
                    
                    {{-- Kiri: Ilustrasi map_3d_poin interaktif (responsif penuh, tidak terpotong di mobile) --}}
                    <div class="lg:col-span-6 flex justify-center items-center">
                        <div class="w-full max-w-[440px] drop-shadow-xl select-none mx-auto overflow-visible">
                            @include('pengguna.partials.map_poin')
                        </div>
                    </div>

                    {{-- Kanan: Penjelasan & Aksi --}}
                    <div class="lg:col-span-6">
                        <span class="inline-block text-xs font-black uppercase tracking-widest text-[#B8694C]">
                            ANTI TELAT NGAMPUS
                        </span>

                        <h2 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-[#163A27] leading-tight">
                            Biar ke kampus makin sat-set, pilih lokasi terdekat
                        </h2>

                        <p class="mt-4 sm:mt-5 text-sm sm:text-base leading-relaxed text-[#244B35] font-medium">
                            Pantau sebaran kost dari area SV IPB, lalu pilih sendiri hunian yang paling nyaman dan pas buatmu.
                        </p>

                        {{-- Legenda Kategori --}}
                        <div class="mt-6 sm:mt-7 flex flex-wrap items-center gap-6 text-xs sm:text-sm font-bold text-[#163A27]">
                            <span class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-[#EC4899] shadow-sm"></span>
                                <span>Putri</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-[#3B82F6] shadow-sm"></span>
                                <span>Putra</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-[#A855F7] shadow-sm"></span>
                                <span>Campur</span>
                            </span>
                        </div>

                        {{-- Tombol Buka Peta Kost --}}
                        <div class="mt-8 sm:mt-9">
                            <a href="{{ route('pengguna.jelajahKos') }}" class="group inline-flex items-center gap-2 text-base font-extrabold text-[#163A27] transition hover:text-[#B8694C]">
                                <span class="underline decoration-2 underline-offset-4">Buka peta kost</span>
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- SILUET PEPOHONAN DI ATAS FOOTER (Daun jatuh mengalir melewati atas pepohonan ini) --}}
            <div class="relative w-full overflow-hidden leading-none select-none pointer-events-none -mt-1 -mb-[1px] z-10">
                <img src="{{ asset('images/footer_pepohonan.svg') }}" alt="Siluet Pepohonan" class="w-full h-auto block">
            </div>
        </section>

    </div> {{-- ================= END WRAPPER DENGAN ANIMASI DAUN JATUH (landing 4.json) ================= --}}

    {{-- ================= SECTION 5: FOOTER (#285F40) ================= --}}
    {{-- Terpisah di luar falling-leaves-wrapper sehingga daun TIDAK keluar dari bawah pepohonan --}}
    <footer class="relative z-20 bg-[#285F40] text-stone-200 -mt-[1px]">
        <div class="relative mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="grid gap-10 sm:grid-cols-2 md:grid-cols-12 items-start">
                
                {{-- Brand & Deskripsi --}}
                <div class="sm:col-span-2 md:col-span-6">
                    <a href="{{ route('pengguna.beranda') }}" class="inline-flex items-center gap-2.5">
                        <img src="{{ asset('images/logo kost.in.svg') }}" alt="Kost.in" class="h-8 w-auto object-contain">
                        <span class="text-xl font-black tracking-tight text-white">Kost<span class="text-[#B8694C]">.in</span></span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-emerald-100/85 font-medium">
                        Bantu kamu nemuin kost yang nyaman dan aman di sekitar kampus SV IPB, Bogor.
                    </p>
                </div>

                {{-- Menu Navigasi --}}
                <div class="md:col-span-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-emerald-200">Menu</h3>
                    <ul class="mt-4 space-y-2.5 text-sm font-semibold text-emerald-100/85">
                        <li><a href="{{ route('pengguna.beranda') }}" class="transition hover:text-white">Beranda</a></li>
                        <li><a href="#tentang" class="transition hover:text-white">Tentang</a></li>
                        <li><a href="#peta" class="transition hover:text-white">Peta</a></li>
                        <li><a href="{{ route('pengguna.jelajahKos') }}" class="transition hover:text-white">Jelajah Kost</a></li>
                    </ul>
                </div>

                {{-- Info Kampus --}}
                <div class="md:col-span-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-emerald-200">Kampus</h3>
                    <ul class="mt-4 space-y-2.5 text-sm font-medium text-emerald-100/85">
                        <li>Sekolah Vokasi IPB University</li>
                        <li>Kota Bogor, Jawa Barat</li>
                        <li class="pt-1 text-xs text-emerald-300">100% Gratis Tanpa Biaya Admin</li>
                    </ul>
                </div>

            </div>

            {{-- Baris Hak Cipta & Kebijakan --}}
            <div class="mt-12 flex flex-col gap-3 border-t border-emerald-900/60 pt-6 text-xs text-emerald-200/75 sm:flex-row sm:items-center sm:justify-between font-medium">
                <p>&copy; 2026 Kost.in. Dibuat khusus buat anak kost SV IPB.</p>
                <div class="flex gap-6">
                    <a href="#" class="transition hover:text-white">Privasi</a>
                    <a href="#" class="transition hover:text-white">Syarat penggunaan</a>
                </div>
            </div>
        </div>
    </footer>
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Inisialisasi Bodymovin Lottie Hero Desktop (1280 x 1080)
        const heroContainer = document.getElementById('hero-lottie-container');
        const heroFallback = document.getElementById('hero-fallback');

        if (heroContainer && typeof lottie !== 'undefined') {
            const heroAnim = lottie.loadAnimation({
                container: heroContainer,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: "{{ asset('images/Main Content.json') }}",
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMid slice'
                }
            });

            heroAnim.addEventListener('DOMLoaded', function () {
                if (heroFallback) {
                    heroFallback.classList.add('opacity-0');
                    setTimeout(function () { heroFallback.remove(); }, 700);
                }
            });

            // Fallback timeout jika DOMLoaded sudah lewat
            setTimeout(function () {
                if (heroFallback) heroFallback.classList.add('opacity-0');
            }, 1200);
        }

        // 2. Inisialisasi Bodymovin Lottie Daun Jatuh (landing 4.json)
        const leavesContainer = document.getElementById('leaves-lottie-container');
        if (leavesContainer && typeof lottie !== 'undefined') {
            lottie.loadAnimation({
                container: leavesContainer,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: "{{ asset('images/landing 4.json') }}",
                rendererSettings: {
                    preserveAspectRatio: 'xMidYTop slice'
                }
            });
        }
    });
    </script>
@endpush
