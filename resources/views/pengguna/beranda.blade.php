@extends('layouts.pengguna')

@section('title', 'Kost.in | Cari Kost Nyaman di Sekitar SV IPB University')
@section('body_class', 'bg-[#D6EFE1]')

@push('styles')
    <style>
        html { scroll-padding-top: 88px; }

        /* ================= ANIMASI MUNCUL TEKS HERO DI AWAL ================= */
        @keyframes heroFadeUp {
            0% {
                opacity: 0;
                transform: translateY(22px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .hero-animate {
            opacity: 0;
            animation: heroFadeUp 0.75s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.22s; }
        .delay-300 { animation-delay: 0.35s; }
        .delay-400 { animation-delay: 0.48s; }
        .delay-500 { animation-delay: 0.6s; }

        /* ================= ANIMASI PETA 3D, AWAN & DAUN (NIMPA DI ATAS MAIN CONTENT.SVG) ================= */
        @keyframes floatMap {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-10px) rotate(1deg);
            }
        }
        .animate-float-map {
            animation: floatMap 5.5s ease-in-out infinite;
            filter: drop-shadow(0 20px 28px rgba(22, 58, 39, 0.14));
            will-change: transform;
        }

        @keyframes driftCloudA {
            0%, 100% { transform: translateX(0px); }
            50% { transform: translateX(14px); }
        }
        .animate-cloud-a {
            animation: driftCloudA 7.5s ease-in-out infinite;
            will-change: transform;
        }

        @keyframes driftCloudB {
            0%, 100% { transform: translateX(0px); }
            50% { transform: translateX(-14px); }
        }
        .animate-cloud-b {
            animation: driftCloudB 8.5s ease-in-out infinite;
            will-change: transform;
        }

        @keyframes swayLeafA {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(9deg); }
        }
        .animate-leaf-a {
            animation: swayLeafA 4.5s ease-in-out infinite;
            will-change: transform;
        }

        @keyframes swayLeafB {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(8px) rotate(-10deg); }
        }
        .animate-leaf-b {
            animation: swayLeafB 5.2s ease-in-out infinite;
            will-change: transform;
        }

        /* Haluskan hanya 15% tepi paling atas Main Content.svg agar tidak ada garis sambung di langit */
        .main-content-blend {
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 15%);
            mask-image: linear-gradient(to bottom, transparent 0%, black 15%);
        }

        /* ================= 4 CARDS INTERACTIVE FAN & HOVER ANIMATION ================= */
        .fanned-deck {
            perspective: 1200px;
        }

        .fanned-card {
            transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1),
                        box-shadow 0.45s cubic-bezier(0.22, 1, 0.36, 1),
                        border-color 0.3s ease,
                        opacity 0.35s ease;
            transform-origin: center bottom;
            will-change: transform;
        }

        @media (min-width: 1024px) {
            .fanned-deck:not(:hover) .card-1 {
                transform: rotate(-6deg) translateY(12px) translateX(14px);
                z-index: 1;
            }
            .fanned-deck:not(:hover) .card-2 {
                transform: rotate(-2deg) translateY(-4px) translateX(5px);
                z-index: 2;
            }
            .fanned-deck:not(:hover) .card-3 {
                transform: rotate(2deg) translateY(-2px) translateX(-5px);
                z-index: 3;
            }
            .fanned-deck:not(:hover) .card-4 {
                transform: rotate(6deg) translateY(14px) translateX(-14px);
                z-index: 4;
            }

            .fanned-deck:hover .card-1 {
                transform: rotate(-3deg) translateX(-28px) translateY(6px);
                z-index: 1;
            }
            .fanned-deck:hover .card-2 {
                transform: rotate(-1deg) translateX(-10px) translateY(0px);
                z-index: 2;
            }
            .fanned-deck:hover .card-3 {
                transform: rotate(1deg) translateX(10px) translateY(0px);
                z-index: 3;
            }
            .fanned-deck:hover .card-4 {
                transform: rotate(3deg) translateX(28px) translateY(6px);
                z-index: 4;
            }

            .fanned-deck .fanned-card:hover {
                transform: rotate(0deg) translateY(-28px) scale(1.08) !important;
                z-index: 60 !important;
                box-shadow: 0 30px 45px -12px rgba(20, 56, 33, 0.18), 0 12px 20px -6px rgba(20, 56, 33, 0.08) !important;
                border-color: #B8694C !important;
                opacity: 1 !important;
            }

            .fanned-deck:hover .fanned-card:not(:hover) {
                opacity: 0.88;
            }
        }

        /* ================= DEDAUNAN JATUH (FALLING LEAVES) ANIMATION ================= */
        .falling-leaf {
            position: absolute;
            pointer-events: none;
            will-change: transform, top;
            animation-name: leafFallAndSway;
            animation-timing-function: linear;
            animation-iteration-count: infinite;
            z-index: 25;
        }

        @keyframes leafFallAndSway {
            0% {
                top: -60px;
                transform: translate3d(0, 0, 0) rotate(0deg) rotateY(0deg);
                opacity: 0;
            }
            5% {
                opacity: 0.9;
            }
            25% {
                transform: translate3d(32px, 0, 0) rotate(75deg) rotateY(50deg);
            }
            50% {
                transform: translate3d(-35px, 0, 0) rotate(180deg) rotateY(100deg);
            }
            75% {
                transform: translate3d(38px, 0, 0) rotate(280deg) rotateY(150deg);
            }
            95% {
                opacity: 0.85;
            }
            100% {
                top: 101%;
                transform: translate3d(-18px, 0, 0) rotate(360deg) rotateY(180deg);
                opacity: 0;
            }
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
    @endphp

    {{-- ================= SECTION 1: HERO HEADER (Main Content.svg + LAYER NIMPA) ================= --}}
    <section class="relative -mt-24 pt-24 overflow-hidden bg-gradient-to-b from-[#D5EEE0] via-[#DFF2E6] to-[#E6F5E8]">

        {{-- LAYER 1 (PALING BAWAH): Main Content.svg utuh selebar layar --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10 w-full select-none" aria-hidden="true">
            <img src="{{ asset('images/Main Content.svg') }}"
                 alt=""
                 class="main-content-blend w-full h-auto block">
        </div>

        {{-- LAYER 2 (TENGAH): Awan-awan putih mengambang di belakang konten --}}
        <div class="pointer-events-none absolute inset-0 z-15 overflow-hidden select-none" aria-hidden="true">
            {{-- Awan Kiri Atas (di belakang pill badge) --}}
            <svg class="animate-cloud-a absolute -left-10 top-28 w-44 sm:w-56 text-white/65" viewBox="0 0 240 90" fill="currentColor">
                <path d="M25,80 H205 C224,80 238,67 238,50 C238,34 225,21 209,20 C203,8 190,0 175,0 C158,0 144,10 139,25 C132,21 124,19 115,19 C94,19 77,34 75,54 C69,50 61,48 53,48 C34,48 18,62 18,80 Z"/>
            </svg>

            {{-- Awan Tengah (di belakang tulisan Sekolah Vokasi) --}}
            <svg class="animate-cloud-b hidden md:block absolute left-[34%] top-44 w-44 lg:w-52 text-white/75" viewBox="0 0 240 90" fill="currentColor">
                <path d="M25,80 H205 C224,80 238,67 238,50 C238,34 225,21 209,20 C203,8 190,0 175,0 C158,0 144,10 139,25 C132,21 124,19 115,19 C94,19 77,34 75,54 C69,50 61,48 53,48 C34,48 18,62 18,80 Z"/>
            </svg>

            {{-- Awan Bawah Kiri (di belakang search bar) --}}
            <svg class="animate-cloud-a hidden sm:block absolute left-[38%] top-[330px] lg:top-[360px] w-48 lg:w-60 text-white/80" viewBox="0 0 240 90" fill="currentColor">
                <path d="M25,80 H205 C224,80 238,67 238,50 C238,34 225,21 209,20 C203,8 190,0 175,0 C158,0 144,10 139,25 C132,21 124,19 115,19 C94,19 77,34 75,54 C69,50 61,48 53,48 C34,48 18,62 18,80 Z"/>
            </svg>
        </div>

        {{-- LAYER 3 (ATAS): Grid Teks Kiri + Peta 3D & Daun Kanan --}}
        <div class="relative z-20 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pt-8 sm:pt-12 lg:pt-14 pb-56 sm:pb-72 md:pb-80 lg:pb-[380px] xl:pb-[440px]">
            <div class="grid items-center gap-10 lg:grid-cols-12 lg:gap-6">

                {{-- KOLOM KIRI: Teks & Pencarian --}}
                <div class="lg:col-span-6">
                    {{-- Tag Pill --}}
                    <div class="hero-animate delay-100 inline-flex items-center gap-2 rounded-full border border-emerald-200/90 bg-white/95 px-3.5 py-1.5 text-xs font-bold text-[#1F513F] shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="tracking-wide">REKOMENDASI KOST SV IPB</span>
                    </div>

                    {{-- Headline Utama --}}
                    <h1 class="hero-animate delay-200 mt-5 text-4xl font-black tracking-tight leading-[1.12] text-stone-900 sm:text-5xl lg:text-[3.4rem]">
                        <span class="text-[#B8694C]">Cari kost nyaman</span> <span class="text-[#163A27]">di</span><br>
                        <span class="text-[#163A27]">sekitar</span> <span class="text-[#0E7A4D]">Sekolah</span><br>
                        <span class="text-[#0E7A4D]">Vokasi IPB</span><br>
                        <span class="text-[#0E7A4D]">University!</span>
                    </h1>

                    {{-- Deskripsi Subtitle --}}
                    <p class="hero-animate delay-300 mt-5 max-w-md text-sm leading-relaxed text-stone-700 sm:text-base font-medium">
                        Jelajahi kost, bandingkan harga dan fasilitas, lalu temukan yang paling pas buatmu.
                    </p>

                    {{-- Form Pencarian Cepat --}}
                    <form action="{{ route('pengguna.jelajahKos') }}" method="GET"
                          class="hero-animate delay-400 mt-7 flex max-w-[460px] items-center overflow-hidden rounded-full border border-stone-200/90 bg-white p-1.5 shadow-lg transition focus-within:border-[#B8694C] focus-within:ring-2 focus-within:ring-[#B8694C]/25">
                        <div class="flex items-center pl-4 text-stone-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="q" placeholder="Cari area, kampus, atau stasiun"
                               class="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none">
                        <button type="submit" aria-label="Cari kost"
                                class="shrink-0 rounded-full bg-[#B8694C] px-6 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-[#955844] sm:text-sm">
                            Cari Kost
                        </button>
                    </form>

                    {{-- Counter Terverifikasi --}}
                    <div class="hero-animate delay-500 mt-4 flex items-center gap-1.5 text-xs font-semibold text-stone-800 sm:text-sm">
                        <span class="text-base font-black text-[#1F513F]">{{ $jumlahKos }}+</span>
                        <span>kost terverifikasi</span>
                    </div>
                </div>

                {{-- KOLOM KANAN: Peta 3D (map_3d_header.svg) + Awan & Daun Saling Nimpa --}}
                <div class="hero-animate delay-300 lg:col-span-6 flex justify-center items-center">
                    <div class="relative w-full max-w-[520px] py-2">

                        {{-- Awan Kanan Tengah (di belakang sisi kanan peta) --}}
                        <svg class="animate-cloud-b pointer-events-none absolute -right-10 top-1/2 -translate-y-1/2 w-36 sm:w-44 text-white/75 z-10" viewBox="0 0 240 90" fill="currentColor" aria-hidden="true">
                            <path d="M25,80 H205 C224,80 238,67 238,50 C238,34 225,21 209,20 C203,8 190,0 175,0 C158,0 144,10 139,25 C132,21 124,19 115,19 C94,19 77,34 75,54 C69,50 61,48 53,48 C34,48 18,62 18,80 Z"/>
                        </svg>

                        {{-- Awan Besar Kanan Bawah (menimpa sudut kanan bawah peta seperti di Figma) --}}
                        <svg class="animate-cloud-a pointer-events-none absolute right-2 -bottom-8 w-52 sm:w-64 text-white/90 z-30 drop-shadow-sm" viewBox="0 0 240 90" fill="currentColor" aria-hidden="true">
                            <path d="M25,80 H205 C224,80 238,67 238,50 C238,34 225,21 209,20 C203,8 190,0 175,0 C158,0 144,10 139,25 C132,21 124,19 115,19 C94,19 77,34 75,54 C69,50 61,48 53,48 C34,48 18,62 18,80 Z"/>
                        </svg>

                        {{-- Daun 1: Kiri Atas Peta --}}
                        <img src="{{ asset('images/daun_1.svg') }}" alt="" aria-hidden="true"
                             class="animate-leaf-a pointer-events-none absolute left-4 top-2 w-7 sm:w-8 h-auto z-30 drop-shadow-sm">

                        {{-- Daun 2: Kiri Tengah Peta --}}
                        <img src="{{ asset('images/daun_2.svg') }}" alt="" aria-hidden="true"
                             class="animate-leaf-b pointer-events-none absolute -left-4 top-[42%] w-8 sm:w-9 h-auto z-30 drop-shadow-sm">

                        {{-- Daun 3: Bawah Peta --}}
                        <img src="{{ asset('images/daun_1.svg') }}" alt="" aria-hidden="true"
                             class="animate-leaf-a pointer-events-none absolute left-[30%] -bottom-2 w-8 sm:w-9 h-auto z-30 drop-shadow-sm"
                             style="animation-delay: 1.1s;">

                        {{-- Daun 4: Kanan Atas Peta --}}
                        <img src="{{ asset('images/daun_2.svg') }}" alt="" aria-hidden="true"
                             class="animate-leaf-b pointer-events-none absolute -right-2 top-3 w-8 sm:w-9 h-auto z-30 drop-shadow-sm"
                             style="animation-delay: 1.7s;">

                        {{-- Peta 3D Utama (map_3d_header.svg) --}}
                        <img src="{{ asset('images/map_3d_header.svg') }}"
                             alt="Peta Rekomendasi Kost SV IPB"
                             class="animate-float-map relative z-20 w-full h-auto object-contain select-none">
                    </div>
                </div>

            </div>
        </div>

        {{-- GELOMBANG 1: Menimpa tepi bawah rumput Main Content.svg menuju Section Tentang (#CDE7C1) --}}
        <div class="relative z-20 -mt-12 sm:-mt-16 lg:-mt-20 -mb-[2px] overflow-hidden leading-none pointer-events-none select-none">
            <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto block" preserveAspectRatio="none">
                <path d="M0,18 C480,78 960,-12 1440,46 L1440,90 L0,90 Z" fill="#CDE7C1"/>
            </svg>
        </div>
    </section>

    {{-- ================= WRAPPER DENGAN ANIMASI DAUN JATUH (DARI TENTANG SAMPAI FOOTER) ================= --}}
    <div id="falling-leaves-wrapper" class="relative overflow-hidden">

        {{-- Layer Partikel Daun Jatuh (daun_1.svg & daun_2.svg) --}}
        <div id="leaves-container" class="pointer-events-none absolute inset-0 overflow-hidden z-25" aria-hidden="true"></div>

        {{-- ================= SECTION 2: TENTANG KOST.IN ================= --}}
        <section id="tentang" class="relative overflow-hidden bg-[#CDE7C1] pt-10 pb-20 sm:pt-14 sm:pb-24 lg:pt-16 lg:pb-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

                {{-- Header Tentang --}}
                <div class="text-center">
                    <span class="inline-block text-xs font-extrabold uppercase tracking-widest text-[#7E6722]">
                        TENTANG KOST.IN
                    </span>
                    <h2 class="mt-2 text-3xl font-black tracking-tight text-[#163A27] sm:text-4xl lg:text-5xl">
                        Kenapa memilih Kost.in?
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-[#244B35] sm:text-base font-medium">
                        Kost.in hadir untuk membantu kamu menemukan kost terbaik di sekitar Sekolah Vokasi IPB University, dengan informasi yang akurat dan mudah diakses.
                    </p>
                </div>

                {{-- 4 CARDS DENGAN INTERACTIVE FAN & HOVER ANIMATION --}}
                <div class="mt-14 lg:mt-20">
                    <div id="deck-tentang" class="fanned-deck mx-auto flex flex-col sm:flex-row flex-wrap lg:flex-nowrap justify-center items-center gap-5 lg:gap-0 lg:-space-x-3 max-w-5xl">

                        {{-- Card 01: Data Terverifikasi (Cream) --}}
                        <div class="fanned-card card-1 w-full max-w-[270px] sm:w-[260px] lg:w-[250px] shrink-0 rounded-3xl border border-[#EFE3CC] bg-[#FDF7EA] p-6 shadow-md transition-all">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#FDE7D2] text-xs font-black text-[#C2410C]">
                                    01
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#F5EAD6] text-[#163A27]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-stone-900 leading-snug">
                                Data Terverifikasi
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-stone-600 font-medium">
                                Informasi telah melalui pengecekan lokasi dan konfirmasi langsung dengan pemilik kost.
                            </p>
                        </div>

                        {{-- Card 02: Filter Lengkap (Soft Green) --}}
                        <div class="fanned-card card-2 w-full max-w-[270px] sm:w-[260px] lg:w-[250px] shrink-0 rounded-3xl border border-[#D4EAD2] bg-[#EAF6E8] p-6 shadow-md transition-all">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#CBE6C9] text-xs font-black text-[#15803D]">
                                    02
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#D8EED6] text-[#163A27]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-stone-900 leading-snug">
                                Filter Lengkap
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-stone-600 font-medium">
                                Sesuaikan pencarian kost berdasarkan harga, fasilitas, dan jarak ke kampus.
                            </p>
                        </div>

                        {{-- Card 03: Hubungi Langsung (Cream) --}}
                        <div class="fanned-card card-3 w-full max-w-[270px] sm:w-[260px] lg:w-[250px] shrink-0 rounded-3xl border border-[#EFE3CC] bg-[#FDF7EA] p-6 shadow-md transition-all">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#FDE7D2] text-xs font-black text-[#C2410C]">
                                    03
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#F5EAD6] text-[#163A27]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-stone-900 leading-snug">
                                Hubungi Langsung
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-stone-600 font-medium">
                                Terhubung langsung ke nomor WhatsApp pemilik kost tanpa melalui perantara.
                            </p>
                        </div>

                        {{-- Card 04: 100% Gratis (Soft Green) --}}
                        <div class="fanned-card card-4 w-full max-w-[270px] sm:w-[260px] lg:w-[250px] shrink-0 rounded-3xl border border-[#D4EAD2] bg-[#EAF6E8] p-6 shadow-md transition-all">
                            <div class="flex items-center justify-between">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#CBE6C9] text-xs font-black text-[#15803D]">
                                    04
                                </span>
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#D8EED6] text-[#163A27]">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-6 text-lg font-extrabold text-stone-900 leading-snug">
                                100% Gratis
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-stone-600 font-medium">
                                Tanpa biaya admin maupun biaya tambahan lainnya bagi pencari kost.
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- GELOMBANG 2: S-curve landai ke Section Kost Populer (#71A857) --}}
            <div class="absolute bottom-0 left-0 right-0 -mb-[2px] overflow-hidden leading-none pointer-events-none select-none">
                <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto block" preserveAspectRatio="none">
                    <path d="M0,48 C480,-10 960,78 1440,18 L1440,90 L0,90 Z" fill="#71A857"/>
                </svg>
            </div>
        </section>

        {{-- ================= SECTION 3: KOST POPULER (PALING BANYAK DILIHAT) ================= --}}
        <section id="kos" class="relative overflow-hidden bg-[#71A857] pt-14 pb-20 sm:pt-18 sm:pb-24 lg:pt-20 lg:pb-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">

                {{-- Header Section --}}
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <span class="inline-block text-xs font-black uppercase tracking-widest text-[#143B22]">
                            PALING DIMINATI
                        </span>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-[#11331D] sm:text-3xl lg:text-4xl">
                            Kost yang paling banyak dilihat
                        </h2>
                        <p class="mt-2 text-sm text-[#194529] max-w-xl font-medium">
                            Hunian favorit pengunjung dengan informasi lokasi, fasilitas, dan harga bulanan yang lengkap.
                        </p>
                    </div>
                    <a href="{{ route('pengguna.jelajahKos') }}" class="group inline-flex items-center gap-1.5 text-sm font-bold text-[#11331D] hover:text-white transition shrink-0">
                        <span>Lihat semua kost</span>
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>

                {{-- Grid Kartu Kos (Cream Hangat sesuai Figma) --}}
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($kosKartu as $kos)
                        <article class="group flex flex-col overflow-hidden rounded-3xl border border-[#EFE5D1] bg-[#FDF8EE] p-3.5 shadow-md transition duration-300 hover:-translate-y-1.5 hover:shadow-2xl">

                            {{-- Foto Kos --}}
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

                                {{-- Badge Tipe Kost di Atas Foto --}}
                                <span class="absolute left-3 top-3 rounded-full border bg-white/95 px-2.5 py-0.5 text-[10px] font-bold tracking-wide shadow-sm {{ $warnaBadge[$kos['tipe']] ?? 'text-stone-700 border-stone-200' }}">
                                    Kost {{ $kos['tipe'] }}
                                </span>
                            </div>

                            {{-- Info Kos --}}
                            <div class="flex flex-1 flex-col p-2.5 pt-4">
                                <h3 class="font-extrabold text-stone-900 text-base leading-snug line-clamp-1 group-hover:text-[#B8694C] transition-colors">
                                    {{ $kos['nama'] }}
                                </h3>

                                <p class="mt-1.5 flex items-center gap-1 text-xs text-stone-600 font-medium">
                                    <svg class="h-3.5 w-3.5 text-[#B8694C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>{{ $kos['jarak'] }} dari kampus</span>
                                </p>

                                {{-- Fasilitas Chips --}}
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach ($kos['fasilitas'] as $fas)
                                        <span class="rounded-md border border-[#E6DEC8] bg-[#F5EFE0] px-2 py-0.5 text-[11px] font-semibold text-stone-700">
                                            {{ $fas }}
                                        </span>
                                    @endforeach
                                </div>

                                {{-- Harga & Tombol Detail --}}
                                <div class="mt-5 flex items-center justify-between border-t border-[#ECE3D0] pt-3.5">
                                    <div>
                                        <span class="text-base font-black text-[#B8694C]">{{ $kos['harga_fmt'] }}</span>
                                        <span class="text-xs text-stone-500 font-medium">/bulan</span>
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
                        <p class="col-span-full rounded-2xl border border-dashed border-white/40 bg-white/10 p-8 text-center text-sm text-white">
                            Belum ada data kost yang ditampilkan.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- GELOMBANG 3: S-curve landai ke Section Peta (#DFF0C8) --}}
            <div class="absolute bottom-0 left-0 right-0 -mb-[2px] overflow-hidden leading-none pointer-events-none select-none">
                <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto block" preserveAspectRatio="none">
                    <path d="M0,18 C480,78 960,-12 1440,46 L1440,90 L0,90 Z" fill="#DFF0C8"/>
                </svg>
            </div>
        </section>

        {{-- ================= SECTION 4: ANTI TELAT NGAMPUS (PETA SVG) ================= --}}
        <section id="peta" class="relative overflow-hidden bg-[#DFF0C8] pt-12 pb-10 sm:pt-16 sm:pb-14 lg:pt-20 lg:pb-16">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">

                    {{-- Kiri: Ilustrasi Peta SVG Teritorial --}}
                    <div class="lg:col-span-6 flex justify-center items-center">
                        <div class="w-full max-w-[440px] drop-shadow-lg transition duration-300 hover:scale-[1.02]">
                            <img src="{{ asset('images/peta.svg') }}" alt="Peta Sebaran Kost SV IPB" class="w-full h-auto object-contain">
                        </div>
                    </div>

                    {{-- Kanan: Penjelasan & Aksi --}}
                    <div class="lg:col-span-6">
                        <span class="inline-block text-xs font-black uppercase tracking-widest text-[#B8694C]">
                            ANTI TELAT NGAMPUS
                        </span>

                        <h2 class="mt-3 text-3xl font-black tracking-tight text-[#163A27] sm:text-4xl lg:text-5xl leading-tight">
                            Biar ke kampus makin sat-set, cek kost di sekitar kampus.
                        </h2>

                        <p class="mt-5 text-sm sm:text-base leading-relaxed text-[#244B35] font-medium">
                            Pantau sebaran kost dari area SV IPB, lalu pilih sendiri hunian yang paling nyaman dan pas buatmu.
                        </p>

                        {{-- Legend Kategori --}}
                        <div class="mt-7 flex flex-wrap items-center gap-6 text-xs sm:text-sm font-bold text-[#163A27]">
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
                        <div class="mt-9">
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
        </section>

        {{-- ================= SILUET PEPOHONAN TRANSISI KE FOOTER ================= --}}
        <div class="w-full bg-[#DFF0C8] -mt-[2px] -mb-[2px] overflow-hidden leading-none select-none pointer-events-none">
            <img src="{{ asset('images/footer_pepohonan.svg') }}" alt="Siluet Pepohonan Bogor" class="w-full h-auto block min-h-[60px]">
        </div>

        {{-- ================= SECTION 5: FOOTER ================= --}}
        <footer class="relative bg-[#24593C] text-stone-200">
            <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-12 lg:py-16">
                <div class="grid gap-10 sm:grid-cols-2 md:grid-cols-12 items-start">

                    {{-- Brand & Deskripsi --}}
                    <div class="sm:col-span-2 md:col-span-6">
                        <a href="{{ route('pengguna.beranda') }}" class="inline-flex items-center gap-2.5">
                            <img src="{{ asset('images/logo kost.in.svg') }}" alt="Kost.in" class="h-8 w-auto object-contain">
                            <span class="text-xl font-black tracking-tight text-white">Kost<span class="text-[#E08A6B]">.in</span></span>
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
                    <p>© 2026 Kost.in. Dibuat khusus buat anak kost SV IPB.</p>
                    <div class="flex gap-6">
                        <a href="#" class="transition hover:text-white">Privasi</a>
                        <a href="#" class="transition hover:text-white">Syarat penggunaan</a>
                    </div>
                </div>
            </div>
        </footer>

    </div>
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Generator Dedaunan Jatuh (Falling Leaves dari Tentang sampai Footer)
        const leavesContainer = document.getElementById('leaves-container');
        if (leavesContainer) {
            const leafSources = [
                "{{ asset('images/daun_1.svg') }}",
                "{{ asset('images/daun_2.svg') }}"
            ];
            const LEAF_COUNT = 16;

            for (let i = 0; i < LEAF_COUNT; i++) {
                const leaf = document.createElement('img');
                leaf.src = leafSources[i % leafSources.length];
                leaf.className = 'falling-leaf';
                leaf.alt = '';

                const size = 20 + Math.random() * 20;
                leaf.style.width = size + 'px';
                leaf.style.height = 'auto';
                leaf.style.left = (3 + Math.random() * 92) + '%';

                const duration = 14 + Math.random() * 12;
                leaf.style.animationDuration = duration + 's';

                const delay = -(Math.random() * duration);
                leaf.style.animationDelay = delay + 's';

                leavesContainer.appendChild(leaf);
            }
        }
    });
    </script>
@endpush