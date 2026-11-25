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

        /* Sembunyikan scrollbar horizontal pada carousel mobile */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Masking halus batas atas ilustrasi Hero & animasi awan/peta/daun khusus Mobile */
        @media (max-width: 767px) {
            .hero-scenery-stage {
                -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 18%);
                mask-image: linear-gradient(to bottom, transparent 0%, black 18%);
            }
        }

        @keyframes mCloudDrift {
            0%   { transform: translate3d(-140px, 0, 0); }
            100% { transform: translate3d(calc(100vw + 140px), 0, 0); }
        }
        @keyframes mHeroMapFloat {
            0%, 100% { transform: translate3d(0, 0, 0) rotate(-0.6deg); }
            50%      { transform: translate3d(0, -7px, 0) rotate(0.6deg); }
        }
        @keyframes mHeroLeafSway {
            0%, 100% { transform: translate3d(0, 0, 0) rotate(-8deg); }
            50%      { transform: translate3d(2px, -4px, 0) rotate(10deg); }
        }
        @keyframes mLeafFallTop {
            0%   { transform: translate3d(0, -40px, 0); opacity: 0; }
            10%  { opacity: 0.92; }
            88%  { opacity: 0.92; }
            100% { transform: translate3d(0, 540px, 0); opacity: 0; }
        }
        @keyframes mLeafFallBottom {
            0%   { transform: translate3d(0, -40px, 0); opacity: 0; }
            10%  { opacity: 0.92; }
            90%  { opacity: 0.92; }
            100% { transform: translate3d(0, 860px, 0); opacity: 0; }
        }
        @keyframes mLeafSway {
            0%, 100% { transform: translateX(-12px) rotate(-18deg); }
            50%      { transform: translateX(14px) rotate(22deg); }
        }

        .m-cloud { will-change: transform; animation: mCloudDrift linear infinite; }
        .m-hero-map { will-change: transform; animation: mHeroMapFloat 4.6s ease-in-out infinite; }
        .m-hero-leaf { will-change: transform; animation: mHeroLeafSway 3.6s ease-in-out infinite; }
        .m-fall-top { will-change: transform, opacity; animation: mLeafFallTop linear infinite; }
        .m-fall-bottom { will-change: transform, opacity; animation: mLeafFallBottom linear infinite; }
        .m-sway { display: block; will-change: transform; animation: mLeafSway ease-in-out infinite; }

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
            /* Kemiringan tipis ala kartu deck saat digeser menyamping di mobile */
            .fanned-deck .card-1,
            .fanned-deck .card-3 {
                transform: rotate(-1.2deg);
            }
            .fanned-deck .card-2,
            .fanned-deck .card-4 {
                transform: rotate(1.2deg);
            }
            .fanned-card:hover,
            .fanned-card:active {
                transform: translateY(-4px) rotate(0deg) scale(1.01) !important;
                border-color: #B8694C !important;
                box-shadow: 0 14px 24px -8px rgba(20, 56, 33, 0.16) !important;
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

    {{-- ================= SECTION 1: HERO HEADER (SATU STRUKTUR WEB & MOBILE) ================= --}}
    <section class="relative w-full overflow-hidden bg-gradient-to-b from-[#C0EBD7] via-[#DDF1DF] to-[#F8F8DB] md:from-[#D5EEE0] md:via-[#DFF2E6] md:to-[#E6F5E8]">

        <div class="relative flex flex-col md:block w-full md:aspect-[1280/1080] overflow-hidden select-none">

            {{-- Layer Animasi Awan Langit Khusus Mobile (Meniru Awan Mengambang di Tampilan Web) --}}
            <div class="pointer-events-none absolute inset-0 z-[5] overflow-hidden md:hidden" aria-hidden="true">
                <svg class="m-cloud absolute top-[10%] left-0 w-24 h-auto opacity-90" style="animation-duration: 28s; animation-delay: -6s;" viewBox="0 0 165 90" fill="none">
                    <path d="M134 86H24C11.8 86 2 76.2 2 64C2 52.8 10.3 43.6 21.1 42.2C25.3 25.4 40.5 13 58.6 13C75.5 13 89.9 23.8 95.1 39C99.1 37.1 103.6 36 108.4 36C125.5 36 139.5 49.4 140.4 66.3C150.2 68.2 157.5 76.8 157.5 86H134Z" fill="url(#mCloudGrad1)"/>
                    <defs><linearGradient id="mCloudGrad1" x1="80" y1="13" x2="80" y2="86" gradientUnits="userSpaceOnUse"><stop stop-color="#FFFFFF"/><stop offset="1" stop-color="#E2F3EC"/></linearGradient></defs>
                </svg>
                <svg class="m-cloud absolute top-[27%] left-0 w-20 h-auto opacity-80" style="animation-duration: 35s; animation-delay: -21s;" viewBox="0 0 165 90" fill="none">
                    <path d="M134 86H24C11.8 86 2 76.2 2 64C2 52.8 10.3 43.6 21.1 42.2C25.3 25.4 40.5 13 58.6 13C75.5 13 89.9 23.8 95.1 39C99.1 37.1 103.6 36 108.4 36C125.5 36 139.5 49.4 140.4 66.3C150.2 68.2 157.5 76.8 157.5 86H134Z" fill="#FFFFFF"/>
                </svg>
                <svg class="m-cloud absolute top-[45%] left-0 w-28 h-auto opacity-90" style="animation-duration: 31s; animation-delay: -13s;" viewBox="0 0 165 90" fill="none">
                    <path d="M134 86H24C11.8 86 2 76.2 2 64C2 52.8 10.3 43.6 21.1 42.2C25.3 25.4 40.5 13 58.6 13C75.5 13 89.9 23.8 95.1 39C99.1 37.1 103.6 36 108.4 36C125.5 36 139.5 49.4 140.4 66.3C150.2 68.2 157.5 76.8 157.5 86H134Z" fill="url(#mCloudGrad2)"/>
                    <defs><linearGradient id="mCloudGrad2" x1="80" y1="13" x2="80" y2="86" gradientUnits="userSpaceOnUse"><stop stop-color="#FFFFFF"/><stop offset="1" stop-color="#E4F4EE"/></linearGradient></defs>
                </svg>
                <svg class="m-cloud absolute top-[62%] left-0 w-22 h-auto opacity-85" style="animation-duration: 38s; animation-delay: -29s;" viewBox="0 0 165 90" fill="none">
                    <path d="M134 86H24C11.8 86 2 76.2 2 64C2 52.8 10.3 43.6 21.1 42.2C25.3 25.4 40.5 13 58.6 13C75.5 13 89.9 23.8 95.1 39C99.1 37.1 103.6 36 108.4 36C125.5 36 139.5 49.4 140.4 66.3C150.2 68.2 157.5 76.8 157.5 86H134Z" fill="#FFFFFF"/>
                </svg>
            </div>

            {{-- Konten Teks & Search Hero (Urutan 1 di Mobile, Overlay Kiri di Tablet/Desktop) --}}
            <div class="order-1 relative md:absolute md:inset-0 z-10 mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8 pt-20 sm:pt-24 md:pt-28 lg:pt-32 xl:pt-36 md:pointer-events-none flex flex-col justify-start">
                <div class="max-w-xl md:max-w-[52%] lg:max-w-[46%] xl:max-w-[48%] pointer-events-auto">

                    {{-- Pill Tag Rekomendasi --}}
                    <div class="inline-flex items-center gap-1.5 md:gap-2 rounded-full border border-[#D4EAD2] bg-white/90 px-3 py-1 md:px-3.5 md:py-1.5 text-[10px] sm:text-xs font-bold text-[#163A27] shadow-sm backdrop-blur-sm">
                        <span class="h-1.5 w-1.5 md:h-2 md:w-2 rounded-full bg-[#0E7A4D] animate-pulse"></span>
                        <span class="tracking-wide">REKOMENDASI KOST SV IPB</span>
                    </div>

                    {{-- Headline Utama --}}
                    <h1 class="mt-3 sm:mt-5 text-[1.6rem] sm:text-5xl lg:text-[3.25rem] xl:text-[3.4rem] font-black tracking-tight leading-[1.16] sm:leading-[1.12] text-[#163A27]">
                        <span class="text-[#B8694C]">Cari kost nyaman</span> <span class="text-[#163A27]">di</span><br class="hidden md:inline">
                        <span class="text-[#163A27]">sekitar</span> <span class="text-[#0E7A4D]">Sekolah</span><br class="hidden md:inline">
                        <span class="text-[#0E7A4D]">Vokasi IPB</span><br class="hidden md:inline">
                        <span class="text-[#0E7A4D]">University!</span>
                    </h1>

                    {{-- Subheadline Deskripsi --}}
                    <p class="mt-2.5 sm:mt-5 max-w-lg text-xs sm:text-base leading-relaxed text-[#244B35] font-medium md:drop-shadow-sm">
                        Jelajahi kos, bandingkan harga dan fasilitas, lalu temukan yang paling pas buatmu.
                    </p>

                    {{-- Search Bar Pill Putih Terkoneksi Backend GET q --}}
                    <form action="{{ route('pengguna.jelajahKos') }}" method="GET"
                          class="mt-4 sm:mt-7 flex max-w-lg items-center overflow-hidden rounded-full border border-[#E6DEC8] bg-white p-1 sm:p-1.5 shadow-md sm:shadow-lg transition focus-within:border-[#B8694C] focus-within:ring-2 focus-within:ring-[#B8694C]/25 backdrop-blur-sm">
                        <div class="flex items-center pl-3 sm:pl-4 text-stone-400">
                            <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="q" placeholder="Cari area, kampus, atau stasiun"
                               class="min-w-0 flex-1 bg-transparent px-2.5 py-1.5 sm:px-3 sm:py-2.5 text-xs sm:text-sm text-stone-800 placeholder-stone-400 focus:outline-none">
                        <button type="submit" aria-label="Cari kost"
                                class="shrink-0 rounded-full bg-[#B8694C] px-4 py-2 sm:px-6 sm:py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm transition hover:bg-[#955844]">
                            Cari Kost
                        </button>
                    </form>

                    {{-- Counter Terverifikasi --}}
                    <div class="mt-2.5 sm:mt-4 flex items-center gap-1.5 text-[11px] sm:text-sm font-semibold text-[#244B35] md:drop-shadow-sm">
                        <span class="text-sm sm:text-lg font-black text-[#163A27]">{{ $jumlahKos }}+</span>
                        <span>kost terverifikasi</span>
                    </div>

                    {{-- Ilustrasi Peta Mengambang + Daun Berayun Khusus Mobile (Bawaan Gaya Web dalam Ukuran Proporsional) --}}
                    <div class="relative mx-auto mt-4 mb-1 w-[195px] sm:w-[230px] pointer-events-none md:hidden" aria-hidden="true">
                        <div class="m-hero-map relative">
                            <img src="{{ asset('images/map_3d_header.svg') }}" alt="" class="w-full h-auto drop-shadow-md">
                            <img src="{{ asset('images/daun_1.svg') }}" alt="" class="m-hero-leaf absolute -left-3 top-3 w-5 h-5" style="animation-delay: -0.4s;">
                            <img src="{{ asset('images/daun_2.svg') }}" alt="" class="m-hero-leaf absolute -right-2.5 -top-2 w-5 h-5" style="animation-delay: -1.7s;">
                            <img src="{{ asset('images/daun_1.svg') }}" alt="" class="m-hero-leaf absolute left-10 -bottom-2.5 w-4.5 h-4.5 rotate-45" style="animation-delay: -2.5s;">
                            <img src="{{ asset('images/daun_2.svg') }}" alt="" class="m-hero-leaf absolute -right-2 bottom-1 w-4.5 h-4.5 -rotate-12" style="animation-delay: -1.1s;">
                        </div>
                    </div>

                </div>
            </div>

            {{-- Panggung Ilustrasi Pemandangan (Urutan 2 di Mobile Memperlihatkan Panorama Kota & Bukit, Full Background di Web) --}}
            <div class="hero-scenery-stage order-2 relative -mt-4 h-[175px] sm:h-[225px] w-full overflow-hidden pointer-events-none md:mt-0 md:absolute md:inset-0 md:h-full">
                <div class="max-md:absolute max-md:bottom-0 max-md:left-1/2 max-md:-translate-x-1/2 max-md:w-[155%] max-md:aspect-[1280/1080] md:relative md:w-full md:h-full">
                    {{-- Container Bodymovin Lottie Hero --}}
                    <div id="hero-lottie-container" class="absolute inset-0 w-full h-full pointer-events-none"></div>

                    {{-- Fallback visual instan sebelum Lottie selesai memuat --}}
                    <div id="hero-fallback" class="absolute inset-0 w-full h-full pointer-events-none transition-opacity duration-700">
                        <img src="{{ asset('images/Main Content.svg') }}" alt="Ilustrasi Hero Kost.in" class="w-full h-full object-cover object-bottom md:object-top opacity-95">
                    </div>
                </div>
            </div>

            {{-- GELOMBANG TRANSISI 1: Menggunakan gelombang asli (gelombang_header_asli.png) --}}
            <div class="absolute bottom-0 left-0 right-0 w-full overflow-hidden leading-none pointer-events-none select-none z-20">
                <img src="{{ asset('images/gelombang_header_asli.png') }}" alt="" class="w-full h-auto block -mb-[2px]">
            </div>
        </div>

    </section>

    {{-- ================= WRAPPER DENGAN ANIMASI DAUN JATUH (landing 4.json) ================= --}}
    {{-- Posisi daun di atas kartu & teks (z-20) sehingga tidak hilang di belakang kartu --}}
    <div id="falling-leaves-wrapper" class="relative overflow-hidden bg-[#DDEDC4] -mt-1 sm:-mt-2">
        
        {{-- Animasi Lottie Daun Jatuh murni dari landing 4.json (Aktif di Tablet & Desktop) --}}
        <div id="leaves-lottie-container" class="hidden md:block pointer-events-none absolute inset-0 overflow-hidden z-20" aria-hidden="true"></div>

        {{-- Animasi Daun Jatuh Proporsional Khusus Mobile (Menggunakan aset asli daun_1.svg & daun_2.svg agar tidak terpotong rasio layar HP) --}}
        <div id="mobile-falling-leaves" class="md:hidden pointer-events-none absolute inset-0 overflow-hidden z-20" aria-hidden="true">
            {{-- Zona Atas: Seksi Tentang Kost.in --}}
            <div class="absolute inset-x-0 top-0 h-[540px] overflow-hidden">
                <div class="m-fall-top absolute left-[7%] top-0" style="animation-duration: 11s; animation-delay: -2s;">
                    <img src="{{ asset('images/daun_1.svg') }}" alt="" class="m-sway w-5 h-5" style="animation-duration: 3.4s;">
                </div>
                <div class="m-fall-top absolute left-[31%] top-0" style="animation-duration: 13.5s; animation-delay: -7.5s;">
                    <img src="{{ asset('images/daun_2.svg') }}" alt="" class="m-sway w-4.5 h-4.5" style="animation-duration: 4.1s; animation-delay: -1.2s;">
                </div>
                <div class="m-fall-top absolute left-[64%] top-0" style="animation-duration: 12s; animation-delay: -4.8s;">
                    <img src="{{ asset('images/daun_1.svg') }}" alt="" class="m-sway w-4 h-4" style="animation-duration: 3.8s; animation-delay: -2.1s;">
                </div>
                <div class="m-fall-top absolute left-[86%] top-0" style="animation-duration: 10.5s; animation-delay: -9s;">
                    <img src="{{ asset('images/daun_2.svg') }}" alt="" class="m-sway w-5 h-5" style="animation-duration: 3.2s; animation-delay: -0.6s;">
                </div>
            </div>

            {{-- Zona Bawah: Seksi Peta Timbul, Pepohonan, hingga Footer --}}
            <div class="absolute inset-x-0 bottom-0 h-[860px] overflow-hidden">
                <div class="m-fall-bottom absolute left-[8%] top-0" style="animation-duration: 13s; animation-delay: -3s;">
                    <img src="{{ asset('images/daun_2.svg') }}" alt="" class="m-sway w-5 h-5" style="animation-duration: 3.9s;">
                </div>
                <div class="m-fall-bottom absolute left-[28%] top-0" style="animation-duration: 15s; animation-delay: -10s;">
                    <img src="{{ asset('images/daun_1.svg') }}" alt="" class="m-sway w-4.5 h-4.5" style="animation-duration: 4.3s; animation-delay: -1.8s;">
                </div>
                <div class="m-fall-bottom absolute left-[58%] top-0" style="animation-duration: 12.5s; animation-delay: -6.2s;">
                    <img src="{{ asset('images/daun_2.svg') }}" alt="" class="m-sway w-4 h-4" style="animation-duration: 3.5s; animation-delay: -2.4s;">
                </div>
                <div class="m-fall-bottom absolute left-[84%] top-0" style="animation-duration: 14s; animation-delay: -1.5s;">
                    <img src="{{ asset('images/daun_1.svg') }}" alt="" class="m-sway w-5 h-5" style="animation-duration: 3.7s; animation-delay: -0.9s;">
                </div>
            </div>
        </div>

        {{-- ================= SECTION 2: TENTANG KOST.IN (#DDEDC4) ================= --}}
        <section id="tentang" class="relative bg-[#DDEDC4] pt-6 pb-0 sm:pt-12 lg:pt-16">
            <div class="relative z-10 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                
                {{-- Header Tentang --}}
                <div class="text-center">
                    <span class="inline-block text-[11px] sm:text-xs font-black uppercase tracking-widest text-[#7E6722]">
                        TENTANG KOST.IN
                    </span>
                    <h2 class="mt-1.5 sm:mt-2 text-xl sm:text-3xl lg:text-4xl xl:text-5xl font-black tracking-tight text-[#163A27]">
                        Kenapa memilih Kost.in?
                    </h2>
                    <p class="mx-auto mt-2.5 sm:mt-4 max-w-2xl text-xs sm:text-sm lg:text-base leading-relaxed text-[#244B35] font-medium">
                        Kost.in hadir untuk membantu kamu menemukan kost terbaik di sekitar Sekolah Vokasi IPB University, dengan informasi yang akurat dan mudah di akses.
                    </p>
                </div>

                {{-- 4 CARDS: HORIZONTAL SWIPE DECK DI MOBILE, TILTED OVERLAPPING FAN DECK DI WEB --}}
                <div class="mt-7 sm:mt-14 lg:mt-20">
                    <div id="deck-tentang" class="fanned-deck no-scrollbar flex flex-row flex-nowrap items-stretch lg:items-center justify-start lg:justify-center overflow-x-auto lg:overflow-visible snap-x snap-mandatory lg:snap-none gap-3.5 sm:gap-4 lg:gap-0 lg:-space-x-8 -mx-5 px-5 lg:mx-auto lg:px-0 max-w-none lg:max-w-5xl pt-3 pb-4 lg:pt-4 lg:pb-6">
                        
                        {{-- Card 01: Data Terverifikasi (Tilt Left -5deg, z-1) --}}
                        <div class="fanned-card card-1 w-[205px] sm:w-[235px] lg:w-[265px] shrink-0 snap-start rounded-2xl sm:rounded-3xl border border-[#EFE3CC] bg-[#FDF7EA] p-4 sm:p-5 lg:p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-7 w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full bg-[#FDE7D2] text-[11px] lg:text-xs font-black text-[#C2410C]">
                                    01
                                </span>
                                <div class="flex h-8 w-8 lg:h-10 lg:w-10 items-center justify-center rounded-xl lg:rounded-2xl bg-[#EAF6E8] text-[#0E7A4D]">
                                    <svg class="h-4 w-4 lg:h-5 lg:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-4 lg:mt-6 text-sm sm:text-base lg:text-lg font-extrabold text-[#163A27] leading-snug">
                                Data Terverifikasi
                            </h3>
                            <p class="mt-1.5 lg:mt-2 text-[11px] lg:text-xs leading-relaxed text-[#244B35] font-medium">
                                Informasi telah melalui pengecekan lokasi dan konfirmasi langsung dengan pemilik kost.
                            </p>
                        </div>

                        {{-- Card 02: Filter Lengkap (Tilt Right +3.5deg, Overlap, z-2) --}}
                        <div class="fanned-card card-2 w-[205px] sm:w-[235px] lg:w-[265px] shrink-0 snap-start rounded-2xl sm:rounded-3xl border border-[#D4EAD2] bg-[#EAF6E8] p-4 sm:p-5 lg:p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-7 w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full bg-[#CBE6C9] text-[11px] lg:text-xs font-black text-[#15803D]">
                                    02
                                </span>
                                <div class="flex h-8 w-8 lg:h-10 lg:w-10 items-center justify-center rounded-xl lg:rounded-2xl bg-[#CDE7C1] text-[#0E7A4D]">
                                    <svg class="h-4 w-4 lg:h-5 lg:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-4 lg:mt-6 text-sm sm:text-base lg:text-lg font-extrabold text-[#163A27] leading-snug">
                                Filter Lengkap
                            </h3>
                            <p class="mt-1.5 lg:mt-2 text-[11px] lg:text-xs leading-relaxed text-[#244B35] font-medium">
                                Sesuaikan pencarian kost berdasarkan harga, fasilitas, dan jarak ke kampus.
                            </p>
                        </div>

                        {{-- Card 03: Hubungi Langsung (Tilt Left -3deg, Overlap, z-3) --}}
                        <div class="fanned-card card-3 w-[205px] sm:w-[235px] lg:w-[265px] shrink-0 snap-start rounded-2xl sm:rounded-3xl border border-[#EFE3CC] bg-[#FDF7EA] p-4 sm:p-5 lg:p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-7 w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full bg-[#FDE7D2] text-[11px] lg:text-xs font-black text-[#C2410C]">
                                    03
                                </span>
                                <div class="flex h-8 w-8 lg:h-10 lg:w-10 items-center justify-center rounded-xl lg:rounded-2xl bg-[#FFEDD5] text-[#C2410C]">
                                    <svg class="h-4 w-4 lg:h-5 lg:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-4 lg:mt-6 text-sm sm:text-base lg:text-lg font-extrabold text-[#163A27] leading-snug">
                                Hubungi Langsung
                            </h3>
                            <p class="mt-1.5 lg:mt-2 text-[11px] lg:text-xs leading-relaxed text-[#244B35] font-medium">
                                Terhubung langsung ke nomor WhatsApp pemilik kost tanpa melalui perantara.
                            </p>
                        </div>

                        {{-- Card 04: 100% Gratis (Tilt Right +4deg, Overlap, z-4) --}}
                        <div class="fanned-card card-4 w-[205px] sm:w-[235px] lg:w-[265px] shrink-0 snap-start rounded-2xl sm:rounded-3xl border border-[#D4EAD2] bg-[#EAF6E8] p-4 sm:p-5 lg:p-6 shadow-md cursor-pointer">
                            <div class="flex items-center justify-between">
                                <span class="flex h-7 w-7 lg:h-8 lg:w-8 items-center justify-center rounded-full bg-[#CBE6C9] text-[11px] lg:text-xs font-black text-[#15803D]">
                                    04
                                </span>
                                <div class="flex h-8 w-8 lg:h-10 lg:w-10 items-center justify-center rounded-xl lg:rounded-2xl bg-[#CDE7C1] text-[#0E7A4D]">
                                    <svg class="h-4 w-4 lg:h-5 lg:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="mt-4 lg:mt-6 text-sm sm:text-base lg:text-lg font-extrabold text-[#163A27] leading-snug">
                                100% Gratis
                            </h3>
                            <p class="mt-1.5 lg:mt-2 text-[11px] lg:text-xs leading-relaxed text-[#244B35] font-medium">
                                Tanpa biaya admin maupun biaya tambahan lainnya bagi pencari kost.
                            </p>
                        </div>

                    </div>

                    {{-- Indikator Geser Kartu Mobile --}}
                    <div id="dots-tentang" class="mt-1 flex items-center justify-center gap-1.5 lg:hidden" aria-hidden="true">
                        <span class="h-1.5 w-5 rounded-full bg-[#B8694C] transition-all duration-300"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-[#163A27]/20 transition-all duration-300"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-[#163A27]/20 transition-all duration-300"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-[#163A27]/20 transition-all duration-300"></span>
                    </div>
                </div>
            </div>

            {{-- GELOMBANG TRANSISI 2: Menuju Section Kos Populer (#C1E6A7) menggunakan vektor gelombang baru (z-30 menutup daun tepat di gelombang) --}}
            <div class="relative w-full overflow-hidden leading-none pointer-events-none select-none mt-8 sm:mt-16 lg:mt-20 -mb-[2px] z-30">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 29" fill="none" aria-hidden="true" class="w-full h-auto block -mb-[1px]">
                    <path d="M 0 3.37 C 2.67 3.78, 10.67 5.10, 16.00 5.85 C 21.33 6.60, 26.67 7.28, 32.00 7.89 C 37.33 8.50, 42.67 9.10, 48.00 9.51 C 53.33 9.92, 58.67 10.14, 64.00 10.36 C 69.33 10.58, 74.67 10.73, 80.00 10.84 C 85.33 10.95, 90.67 11.02, 96.00 10.99 C 101.33 10.96, 106.67 10.80, 112.00 10.66 C 117.33 10.52, 122.67 10.34, 128.00 10.14 C 133.33 9.94, 138.67 9.77, 144.00 9.46 C 149.33 9.15, 154.67 8.69, 160.00 8.30 C 165.33 7.91, 170.67 7.52, 176.00 7.13 C 181.33 6.74, 186.67 6.35, 192.00 5.96 C 197.33 5.57, 202.67 5.19, 208.00 4.80 C 213.33 4.41, 218.67 4.02, 224.00 3.63 C 229.33 3.24, 234.67 2.83, 240.00 2.47 C 245.33 2.11, 250.67 1.76, 256.00 1.47 C 261.33 1.18, 266.67 0.93, 272.00 0.73 C 277.33 0.53, 282.67 0.36, 288.00 0.25 C 293.33 0.14, 298.67 0.08, 304.00 0.08 C 309.33 0.08, 314.67 0.15, 320.00 0.27 C 325.33 0.39, 330.67 0.49, 336.00 0.80 C 341.33 1.11, 346.67 1.62, 352.00 2.12 C 357.33 2.62, 362.67 3.19, 368.00 3.81 C 373.33 4.43, 378.67 5.12, 384.00 5.82 C 389.33 6.52, 394.67 7.33, 400.00 7.98 C 405.33 8.63, 410.67 9.21, 416.00 9.71 C 421.33 10.21, 426.67 10.68, 432.00 11.01 C 437.33 11.34, 442.67 11.52, 448.00 11.69 C 453.33 11.86, 458.67 11.95, 464.00 12.00 C 469.33 12.05, 474.67 12.07, 480.00 11.98 C 485.33 11.89, 490.67 11.67, 496.00 11.49 C 501.33 11.31, 506.67 11.11, 512.00 10.90 C 517.33 10.69, 522.67 10.52, 528.00 10.21 C 533.33 9.90, 538.67 9.44, 544.00 9.05 C 549.33 8.66, 554.67 8.27, 560.00 7.88 C 565.33 7.49, 570.67 7.10, 576.00 6.71 C 581.33 6.32, 586.67 5.94, 592.00 5.55 C 597.33 5.16, 602.67 4.77, 608.00 4.38 C 613.33 3.99, 618.67 3.56, 624.00 3.23 C 629.33 2.90, 634.67 2.64, 640.00 2.39 C 645.33 2.14, 650.67 1.92, 656.00 1.73 C 661.33 1.54, 666.67 1.36, 672.00 1.25 C 677.33 1.14, 682.67 1.09, 688.00 1.08 C 693.33 1.07, 698.67 1.10, 704.00 1.18 C 709.33 1.26, 714.67 1.31, 720.00 1.54 C 725.33 1.77, 730.67 2.12, 736.00 2.53 C 741.33 2.93, 746.67 3.42, 752.00 3.97 C 757.33 4.52, 762.67 5.17, 768.00 5.82 C 773.33 6.47, 778.67 7.27, 784.00 7.88 C 789.33 8.49, 794.67 9.01, 800.00 9.48 C 805.33 9.95, 810.67 10.32, 816.00 10.68 C 821.33 11.04, 826.67 11.44, 832.00 11.65 C 837.33 11.86, 842.67 11.89, 848.00 11.93 C 853.33 11.96, 858.67 11.95, 864.00 11.86 C 869.33 11.77, 874.67 11.64, 880.00 11.42 C 885.33 11.20, 890.67 10.88, 896.00 10.52 C 901.33 10.16, 906.67 9.72, 912.00 9.29 C 917.33 8.86, 922.67 8.43, 928.00 7.95 C 933.33 7.48, 938.67 6.93, 944.00 6.44 C 949.33 5.95, 954.67 5.47, 960.00 5.00 C 965.33 4.53, 970.67 4.00, 976.00 3.63 C 981.33 3.25, 986.67 2.85, 992.00 2.75 C 997.33 2.65, 1002.67 2.76, 1008.00 3.04 C 1013.33 3.32, 1021.33 4.18, 1024.00 4.41 L 1024 29 L 0 29 Z" fill="#C1E6A7"/>
                </svg>
            </div>
        </section>

        {{-- ================= SECTION 3: KOST POPULER (#C1E6A7) ================= --}}
        <section id="kos" class="relative z-30 bg-[#C1E6A7] pt-4 sm:pt-6 pb-0 text-[#163A27]">
            <div class="relative z-20 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
                
                {{-- Header Section --}}
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2.5 sm:gap-4">
                    <div>
                        <span class="inline-block text-[11px] sm:text-xs font-black uppercase tracking-widest text-[#5B280C]">
                            PALING DIMINATI
                        </span>
                        <h2 class="mt-1.5 sm:mt-2 text-xl sm:text-3xl lg:text-4xl font-black tracking-tight text-[#11331D]">
                            Kost yang paling banyak dilihat
                        </h2>
                        <p class="mt-1.5 sm:mt-2 text-xs sm:text-sm text-[#194529] max-w-xl font-medium">
                            Pilihan kost terpopuler dengan fasilitas lengkap, nyaman, dan harga bulanan yang terjangkau.
                        </p>
                    </div>
                    <a href="{{ route('pengguna.jelajahKos') }}" class="group inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-[#5B280C] hover:text-[#3A1806] transition shrink-0">
                        <span>Lihat semua kost</span>
                        <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>

                {{-- Carousel Horizontal di Mobile (~72% lebar layar), Grid 2/3 Kolom Utuh di Tablet & Web --}}
                <div id="carousel-kos" class="no-scrollbar mt-6 sm:mt-10 flex sm:grid flex-nowrap overflow-x-auto sm:overflow-visible snap-x snap-mandatory sm:snap-none gap-3.5 sm:gap-6 -mx-5 px-5 pb-3 sm:mx-0 sm:px-0 sm:pb-0 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($kosKartu as $kos)
                        <article class="group flex w-[72vw] max-w-[252px] shrink-0 snap-start sm:w-auto sm:max-w-none flex-col overflow-hidden rounded-2xl sm:rounded-3xl bg-[#FDF8EE] border border-[#EFE5D1] p-2.5 sm:p-3.5 shadow-md transition duration-300 hover:-translate-y-1.5 hover:shadow-2xl">
                            
                            {{-- Foto Kos (Polos tanpa badge di atasnya sesuai gambar 2) --}}
                            <div class="relative aspect-[16/10] overflow-hidden rounded-xl sm:rounded-2xl bg-stone-100">
                                @if ($kos['foto'])
                                    <img src="{{ $kos['foto'] }}" alt="{{ $kos['nama'] }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-emerald-50 text-emerald-700">
                                        <svg class="h-8 w-8 sm:h-10 sm:w-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Info Kos --}}
                            <div class="flex flex-1 flex-col p-1.5 pt-3 sm:p-2.5 sm:pt-4">
                                {{-- Baris 1: Nama Kos di Kiri, Badge Kategori di Kanan Sesuai Gambar 2 --}}
                                <div class="flex items-center justify-between gap-1.5 sm:gap-2">
                                    <h3 class="font-extrabold text-stone-900 text-sm sm:text-base leading-snug line-clamp-1 group-hover:text-[#B8694C] transition-colors">
                                        {{ $kos['nama'] }}
                                    </h3>
                                    <span class="shrink-0 rounded-full border px-2 py-0.5 sm:px-3 text-[10px] sm:text-xs font-bold tracking-wide shadow-xs {{ $warnaBadge[$kos['tipe']] ?? 'bg-stone-100 text-stone-700 border-stone-200' }}">
                                        {{ $labelBadge[$kos['tipe']] ?? ('Kost ' . $kos['tipe']) }}
                                    </span>
                                </div>

                                {{-- Baris 2: Jarak Kampus --}}
                                <p class="mt-1.5 sm:mt-2 flex items-center gap-1 sm:gap-1.5 text-[11px] sm:text-xs text-stone-500 font-medium">
                                    <svg class="h-3 w-3 sm:h-3.5 sm:w-3.5 text-[#B8694C] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>{{ $kos['jarak'] }} dari kampus</span>
                                </p>

                                {{-- Baris 3: Fasilitas Chips --}}
                                <div class="mt-2.5 sm:mt-3.5 flex flex-wrap gap-1 sm:gap-1.5">
                                    @foreach ($kos['fasilitas'] as $fas)
                                        <span class="rounded-md border border-[#E6DEC8] bg-[#F5EFE0] px-2 py-0.5 sm:px-2.5 text-[10px] sm:text-[11px] font-semibold text-stone-700">
                                             {{ $fas }}
                                        </span>
                                    @endforeach
                                </div>

                                {{-- Baris 4: Harga & Tombol Panah --}}
                                <div class="mt-3.5 pt-2.5 sm:mt-5 sm:pt-3.5 flex items-center justify-between border-t border-[#EFE5D1]">
                                    <div>
                                        <span class="text-sm sm:text-base font-black text-[#B8694C]">{{ $kos['harga_fmt'] }}</span>
                                        <span class="text-[11px] sm:text-xs text-stone-500 font-normal">/bulan</span>
                                    </div>
                                    <a href="{{ route('pengguna.jelajahKos', ['kos' => $kos['id']]) }}" aria-label="Lihat detail {{ $kos['nama'] }}" class="flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-[#B8694C] text-white shadow-sm transition hover:bg-[#955844] hover:scale-105">
                                        <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>

                        </article>
                    @empty
                        <p class="col-span-full w-full rounded-2xl border border-dashed border-[#7CB75A] bg-white/50 p-8 text-center text-sm font-semibold text-[#163A27]">
                            Belum ada data kos yang ditampilkan.
                        </p>
                    @endforelse
                </div>

                @if (count($kosKartu) > 1)
                    <div id="dots-kos" class="mt-1 flex items-center justify-center gap-1.5 sm:hidden" aria-hidden="true">
                        @foreach ($kosKartu as $idx => $kos)
                            <span class="h-1.5 rounded-full transition-all duration-300 {{ $idx === 0 ? 'w-5 bg-[#5B280C]' : 'w-1.5 bg-[#11331D]/20' }}"></span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- GELOMBANG TRANSISI 3: Menuju Section Peta (#DDEDC4) menggunakan gelombang asli gelombang_peta.png --}}
            <div class="relative w-full overflow-hidden leading-none pointer-events-none select-none mt-8 sm:mt-16 lg:mt-20 -mb-[2px] z-30">
                <img src="{{ asset('images/gelombang_peta.png') }}" alt="" class="w-full h-auto block -mb-[1px]">
            </div>
        </section>

        {{-- ================= SECTION 4: ANTI TELAT NGAMPUS (PETA TIMBUL) (#DDEDC4) ================= --}}
        {{-- Menggunakan partial map_poin.blade.php dengan interaktivitas highlight saat kursor diarahkan ke wilayah map/pin --}}
        <section id="peta" class="relative bg-[#DDEDC4] pt-4 sm:pt-6 pb-0">
            <div class="relative z-10 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 pb-8 sm:pb-14 lg:pb-16">
                <div class="grid items-center gap-6 sm:gap-10 lg:grid-cols-12 lg:gap-14">
                    
                    {{-- Kiri: Ilustrasi map_3d_poin interaktif (responsif penuh, tidak terpotong di mobile) --}}
                    <div class="lg:col-span-6 flex justify-center items-center">
                        <div class="w-full max-w-[285px] sm:max-w-[440px] drop-shadow-xl select-none mx-auto overflow-visible">
                            @include('pengguna.partials.map_poin')
                        </div>
                    </div>

                    {{-- Kanan: Penjelasan & Aksi --}}
                    <div class="lg:col-span-6">
                        <span class="inline-block text-[11px] sm:text-xs font-black uppercase tracking-widest text-[#B8694C]">
                            ANTI TELAT NGAMPUS
                        </span>

                        <h2 class="mt-2 sm:mt-3 text-xl sm:text-4xl lg:text-5xl font-black tracking-tight text-[#163A27] leading-tight">
                            Biar ke kampus makin sat-set, pilih lokasi terdekat
                        </h2>

                        <p class="mt-2.5 sm:mt-5 text-xs sm:text-base leading-relaxed text-[#244B35] font-medium">
                            Pantau sebaran kost dari area SV IPB, lalu pilih sendiri hunian yang paling nyaman dan pas buatmu.
                        </p>

                        {{-- Legenda Kategori --}}
                        <div class="mt-4 sm:mt-7 flex flex-wrap items-center gap-4 sm:gap-6 text-xs sm:text-sm font-bold text-[#163A27]">
                            <span class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-[#EC4899] shadow-sm"></span>
                                <span>Putri</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-[#3B82F6] shadow-sm"></span>
                                <span>Putra</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-[#A855F7] shadow-sm"></span>
                                <span>Campur</span>
                            </span>
                        </div>

                        {{-- Tombol Buka Peta Kost --}}
                        <div class="mt-5 sm:mt-9">
                            <a href="{{ route('pengguna.jelajahKos') }}" class="group inline-flex items-center gap-2 text-sm sm:text-base font-extrabold text-[#163A27] transition hover:text-[#B8694C]">
                                <span class="underline decoration-2 underline-offset-4">Buka peta kost</span>
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            {{-- SILUET PEPOHONAN DI ATAS FOOTER (Daun jatuh mengalir melewati depan pepohonan ini) --}}
            <div class="relative w-full overflow-hidden leading-none select-none pointer-events-none -mt-1 -mb-[1px] z-10">
                <img src="{{ asset('images/footer_pepohonan.svg') }}" alt="Siluet Pepohonan" class="w-full h-auto block">
            </div>
        </section>

        {{-- ================= SECTION 5: FOOTER (#285F40) ================= --}}
        {{-- Di dalam falling-leaves-wrapper agar animasi daun tetap mengalir sampai footer akhir --}}
        <footer class="relative z-10 bg-[#285F40] text-stone-200 -mt-[1px]">
            <div class="relative z-30 mx-auto max-w-7xl px-5 sm:px-6 lg:px-8 py-12 lg:py-16">
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

    </div> {{-- ================= END WRAPPER DENGAN ANIMASI DAUN JATUH (landing 4.json) ================= --}}
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

            // Pause animasi Hero (3.5MB SVG) saat di luar layar agar scroll tetap ringan & 60fps
            if ('IntersectionObserver' in window) {
                const heroObs = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) heroAnim.play();
                        else heroAnim.pause();
                    });
                }, { rootMargin: '150px 0px' });
                heroObs.observe(heroContainer);
            }
        }

        // 2. Inisialisasi Bodymovin Lottie Daun Jatuh (landing 4.json untuk Tablet & Desktop)
        const leavesContainer = document.getElementById('leaves-lottie-container');
        if (leavesContainer && typeof lottie !== 'undefined') {
            lottie.loadAnimation({
                container: leavesContainer,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                path: "{{ asset('images/landing 4.json') }}",
                rendererSettings: {
                    preserveAspectRatio: 'xMidYMin slice'
                }
            });
        }

        // 3. Sinkronisasi Indikator Dots Carousel Mobile (4 Card Tentang & Kos Populer)
        function bindCarouselDots(trackId, dotsId, activeClass, inactiveClass) {
            const track = document.getElementById(trackId);
            const dotsWrap = document.getElementById(dotsId);
            if (!track || !dotsWrap) return;
            const dots = [...dotsWrap.children];
            if (dots.length < 2) return;

            track.addEventListener('scroll', function () {
                const maxScroll = track.scrollWidth - track.clientWidth;
                if (maxScroll <= 0) return;
                const progress = Math.max(0, Math.min(1, track.scrollLeft / maxScroll));
                const activeIdx = Math.round(progress * (dots.length - 1));
                dots.forEach(function (dot, i) {
                    dot.className = 'h-1.5 rounded-full transition-all duration-300 ' + (i === activeIdx ? activeClass : inactiveClass);
                });
            }, { passive: true });
        }

        bindCarouselDots('deck-tentang', 'dots-tentang', 'w-5 bg-[#B8694C]', 'w-1.5 bg-[#163A27]/20');
        bindCarouselDots('carousel-kos', 'dots-kos', 'w-5 bg-[#5B280C]', 'w-1.5 bg-[#11331D]/20');
    });
    </script>
@endpush
