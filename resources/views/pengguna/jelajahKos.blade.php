<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jelajah Kos | Kost.in</title>

    {{-- Font: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; background: #ece8e3; }
        .leaflet-popup-content-wrapper { border-radius: 10px; }
        .scroll-thin::-webkit-scrollbar { width: 6px; }
        .scroll-thin::-webkit-scrollbar-thumb { background: #d6d3d1; border-radius: 6px; }
    </style>
</head>
<body class="bg-[#f7f5f2] font-sans text-stone-800 antialiased selection:bg-[#B46C54] selection:text-white">

@php
    // Titik pusat: Kampus SV IPB (koordinat perkiraan, sesuaikan dengan titik yang tepat)
    $kampus = ['nama' => 'SV IPB', 'lat' => -6.5886, 'lng' => 106.8032];

    $hitungJarak = function ($lat1, $lng1, $lat2, $lng2) {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(sqrt($a));
    };

    $foto = [
        'photo-1554995207-c18c203602cb',
        'photo-1505691938895-1758d7feb511',
        'photo-1598928506311-c55ded91a20c',
        'photo-1522708323590-d24dbb6b0267',
    ];

    // DATA DUMMY. Nanti ganti dengan data dari database lewat controller.
    $kosList = collect([
        ['id' => 1, 'nama' => 'Kos Putri Melati',    'tipe' => 'Putri',  'harga' => 700000, 'periode' => '1 Bulan', 'telepon' => '0812-3456-7890', 'alamat' => 'Jl. Kemuning No. 12, Babakan, Bogor Tengah', 'fasilitas' => ['Wi-Fi', 'AC', 'Kamar Mandi Dalam'],            'lat' => -6.5990, 'lng' => 106.8085],
        ['id' => 2, 'nama' => 'Kos Putra Mandiri',   'tipe' => 'Putra',  'harga' => 550000, 'periode' => '1 Bulan', 'telepon' => '0813-1111-2222', 'alamat' => 'Jl. Kumbang No. 5, Babakan, Bogor Tengah',    'fasilitas' => ['Wi-Fi', 'Parkir'],                          'lat' => -6.5850, 'lng' => 106.8000],
        ['id' => 3, 'nama' => 'Kos Griya Sejahtera', 'tipe' => 'Campur', 'harga' => 950000, 'periode' => '3 Bulan', 'telepon' => '0856-7777-8888', 'alamat' => 'Jl. Pajajaran No. 20, Baranangsiang, Bogor',  'fasilitas' => ['Wi-Fi', 'AC', 'Parkir', 'Dapur', 'Keamanan 24 Jam'],           'lat' => -6.5800, 'lng' => 106.8150],
        ['id' => 4, 'nama' => 'Kos Putri Anggrek',   'tipe' => 'Putri',  'harga' => 800000, 'periode' => '1 Bulan', 'telepon' => '0821-2222-3333', 'alamat' => 'Jl. Anggrek No. 3, Bogor Selatan',             'fasilitas' => ['Wi-Fi', 'AC', 'Kamar Mandi Dalam', 'Laundry'],            'lat' => -6.6050, 'lng' => 106.7950],
        ['id' => 5, 'nama' => 'Kos Putra Harapan',   'tipe' => 'Putra',  'harga' => 600000, 'periode' => '6 Bulan', 'telepon' => '0857-4444-5555', 'alamat' => 'Jl. Harapan No. 8, Tanah Sareal',            'fasilitas' => ['Wi-Fi', 'Parkir', 'Dapur'],                  'lat' => -6.5700, 'lng' => 106.8030],
        ['id' => 6, 'nama' => 'Kos Campur Bersama',  'tipe' => 'Campur', 'harga' => 650000, 'periode' => 'Tahunan', 'telepon' => '0812-9999-0000', 'alamat' => 'Jl. Bersama No. 1, Bogor Selatan',             'fasilitas' => ['Wi-Fi', 'Dapur'],                            'lat' => -6.6150, 'lng' => 106.8100],
    ])->map(function ($k, $i) use ($kampus, $hitungJarak, $foto) {
        $k['jarak_m'] = (int) round($hitungJarak($kampus['lat'], $kampus['lng'], $k['lat'], $k['lng']));
        // 4 foto per kos (dummy: foto digeser agar tiap kos berbeda)
        $k['fotos'] = collect(range(0, 3))->map(fn ($n) => 'https://images.unsplash.com/' . $foto[($i + $n) % 4] . '?auto=format&fit=crop&w=900&q=80')->all();
        return $k;
    })->values();

    $semuaFasilitas = ['Wi-Fi', 'AC', 'Kamar Mandi Dalam', 'Parkir', 'Dapur', 'Laundry', 'Keamanan 24 Jam'];
    // Ikon fasilitas (sama dengan halaman Data Fasilitas admin)
    $ikonFasilitas = [
        'Wi-Fi' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/></svg>',
        'AC' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M800-480H160q-33 0-56.5-23.5T80-560v-240q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v240q0 33-23.5 56.5T800-480ZM160-200v-80q50 0 85-35t35-85h80q0 83-58.5 141.5T160-200Zm640 0q-83 0-141.5-58.5T600-400h80q0 50 35 85t85 35v80Zm-360 40v-240h80v240h-80Zm360-400H160h640Zm-560 0v-80q0-33 23.5-56.5T320-720h320q33 0 56.5 23.5T720-640v80h-80v-80H320v80h-80Zm-80 0h640v-240H160v240Z"/></svg>',
        'Kamar Mandi Dalam' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M320-240q-17 0-28.5-11.5T280-280q0-17 11.5-28.5T320-320q17 0 28.5 11.5T360-280q0 17-11.5 28.5T320-240Zm160 0q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm160 0q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240ZM200-400v-80q0-106 68-184t172-92v-84h80v84q104 14 172 92t68 184v80H200Zm80-80h400q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480Zm40 360q-17 0-28.5-11.5T280-160q0-17 11.5-28.5T320-200q17 0 28.5 11.5T360-160q0 17-11.5 28.5T320-120Zm160 0q-17 0-28.5-11.5T440-160q0-17 11.5-28.5T480-200q17 0 28.5 11.5T520-160q0 17-11.5 28.5T480-120Zm160 0q-17 0-28.5-11.5T600-160q0-17 11.5-28.5T640-200q17 0 28.5 11.5T680-160q0 17-11.5 28.5T640-120ZM480-480Z"/></svg>',
        'Parkir' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 8h2.5a2.5 2.5 0 010 5H10V8zm0 8v-3m11-1a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'Dapur' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M320-640v-120h80v120h-80Zm0 360v-200h80v200h-80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h480q33 0 56.5 23.5T800-800v640q0 33-23.5 56.5T720-80H240Zm0-80h480v-360H240v360Zm0-440h480v-200H240v200Z"/></svg>',
        'Laundry' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h480q33 0 56.5 23.5T800-800v640q0 33-23.5 56.5T720-80H240Zm0-80h480v-640H240v640Zm381.5-98.5Q680-317 680-400t-58.5-141.5Q563-600 480-600t-141.5 58.5Q280-483 280-400t58.5 141.5Q397-200 480-200t141.5-58.5ZM480-268q-26 0-50.5-9.5T386-306l188-188q19 19 28.5 43.5T612-400q0 55-38.5 93.5T480-268ZM320-680q17 0 28.5-11.5T360-720q0-17-11.5-28.5T320-760q-17 0-28.5 11.5T280-720q0 17 11.5 28.5T320-680Zm148.5-11.5Q480-703 480-720t-11.5-28.5Q457-760 440-760t-28.5 11.5Q400-737 400-720t11.5 28.5Q423-680 440-680t28.5-11.5ZM240-160v-640 640Z"/></svg>',
        'Keamanan 24 Jam' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    ];

    $hargaMin = 500000;
    $hargaMax = 15000000;
@endphp

    {{-- ================= NAVBAR ================= --}}
    @php
        $menu = [
            ['label' => 'Beranda',     'url' => route('pengguna.beranda'),                'aktif' => request()->routeIs('pengguna.beranda')],
            ['label' => 'Peta Kos',    'url' => route('pengguna.beranda') . '#peta',      'aktif' => false],
            ['label' => 'Tentang',     'url' => route('pengguna.beranda') . '#tentang',   'aktif' => false],
            ['label' => 'Jelajah Kos', 'url' => route('pengguna.jelajahKos'),             'aktif' => request()->routeIs('pengguna.jelajahKos')],
        ]; 
    @endphp

    <header class="sticky top-0 z-[2000] border-b border-stone-200 bg-white/95 backdrop-blur">
        <nav class="mx-auto grid max-w-[1600px] grid-cols-1 items-center gap-y-3 px-6 py-4 md:grid-cols-[1fr_auto_1fr]">
            {{-- Logo --}}
            <a href="{{ route('pengguna.beranda') }}" class="flex items-center justify-center gap-2.5 md:justify-start">
                <img src="{{ asset('images/logo kost.in.png') }}" alt="Kost.in" class="h-7 w-auto object-contain">
                <span class="text-lg font-extrabold tracking-tight text-stone-900">Kost<span class="text-[#B46C54]">.in</span></span>
            </a>

            {{-- Menu tengah --}}
            <ul class="flex items-center justify-center gap-1 text-sm">
                @foreach ($menu as $item)
                    <li>
                        <a href="{{ $item['url'] }}"
                           @if ($item['aktif']) aria-current="page" @endif
                           class="relative block px-4 py-2 font-medium transition-colors {{ $item['aktif'] ? 'text-[#B46C54] font-bold' : 'text-stone-600 hover:text-stone-900' }}">
                            {{ $item['label'] }}
                            <span class="absolute inset-x-4 -bottom-[17px] h-0.5 rounded-full bg-[#B46C54] transition-opacity {{ $item['aktif'] ? 'opacity-100' : 'opacity-0' }}"></span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Spacer agar menu tetap di tengah --}}
            <div class="hidden md:block"></div>
        </nav>
    </header>

    {{-- ================= KONTEN ================= --}}
    <main class="mx-auto grid max-w-[1600px] gap-5 p-5 lg:h-[calc(100vh-69px)] lg:grid-cols-[300px_minmax(0,1fr)_330px]">

        {{-- ========== KOLOM KIRI: FILTER + DAFTAR ========== --}}
        <aside class="scroll-thin flex min-h-0 flex-col gap-5 lg:overflow-y-auto lg:pr-1">

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
                        <span id="f-harga-label" class="rounded-md bg-[#fbf5f3] px-2 py-1 font-semibold text-[#B46C54]">Rp 15.000.000</span>
                    </div>
                    <input id="f-harga" type="range" min="{{ $hargaMin }}" max="{{ $hargaMax }}" step="50000" value="{{ $hargaMax }}" class="mt-3 w-full accent-[#B46C54]">
                    <div class="flex justify-between text-[11px] text-stone-500"><span>Rp 500rb</span><span>Rp 15jt</span></div>
                </div>

                {{-- Jarak --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs">
                        <label for="f-jarak" class="font-semibold text-stone-800">Jarak ke Kampus</label>
                        <span id="f-jarak-label" class="rounded-md bg-[#fbf5f3] px-2 py-1 font-semibold text-[#B46C54]">10 km</span>
                    </div>
                    <input id="f-jarak" type="range" min="0.5" max="10" step="0.5" value="10" class="mt-3 w-full accent-[#B46C54]">
                    <div class="flex justify-between text-[11px] text-stone-500"><span>0 km</span><span>10 km</span></div>
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

                {{-- Fasilitas --}}
                <div class="mt-4">
                    <p class="text-xs font-semibold text-stone-800">Fasilitas</p>
                    <div class="mt-2 grid grid-cols-2 gap-x-3 gap-y-2">
                        @foreach ($semuaFasilitas as $fas)
                            <label class="flex cursor-pointer items-start gap-2 text-xs text-stone-600">
                                <input type="checkbox" value="{{ $fas }}" class="f-fasilitas mt-0.5 h-4 w-4 shrink-0 rounded border-stone-300 accent-[#B46C54]">
                                <span>{{ $fas }}</span>
                            </label>
                        @endforeach
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
                    <div id="dd-urut" class="dd relative" data-value="jarak">
                        <button type="button" class="dd-btn flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-700 transition hover:border-stone-300 focus:border-[#B46C54] focus:outline-none" aria-haspopup="listbox">
                            <span class="dd-label">Terdekat</span>
                            <svg class="dd-chev h-3.5 w-3.5 text-stone-400 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <ul class="dd-menu absolute right-0 z-30 mt-1.5 hidden w-40 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lg" role="listbox">
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="jarak" data-label="Terdekat" role="option"><span>Terdekat</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="murah" data-label="Termurah" role="option"><span>Termurah</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                            <li class="dd-opt flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition-colors" data-value="mahal" data-label="Termahal" role="option"><span>Termahal</span><svg class="dd-check hidden h-3.5 w-3.5 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></li>
                        </ul>
                    </div>
                </div>
                <ul id="daftar-kos" class="mt-3 space-y-2.5"></ul>
                <p id="kosong" class="hidden py-6 text-center text-sm text-stone-500">Tidak ada kos yang cocok dengan filter.</p>
            </section>
        </aside>

        {{-- ========== TENGAH: PETA ========== --}}
        <section class="relative isolate h-[520px] overflow-hidden rounded-2xl border border-stone-200 lg:h-full">
            <div id="peta" class="absolute inset-0 z-0"></div>

            {{-- Pencarian + chip tipe --}}
            <div id="overlay-atas" class="absolute left-4 top-4 z-[1000] w-[calc(100%-6.5rem)] max-w-sm">
                <div class="relative">
                    <input id="f-cari" type="text" placeholder="Cari lokasi atau nama kos..." class="block w-full rounded-xl border border-stone-200 bg-white py-3 pl-4 pr-10 text-sm shadow-sm placeholder:text-stone-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">
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
            <div id="overlay-legenda" class="absolute bottom-4 left-4 z-[1000] rounded-xl border border-stone-200 bg-white p-3 text-xs shadow-sm">
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
        <aside class="scroll-thin min-h-0 overflow-hidden rounded-2xl border border-stone-200 bg-white lg:overflow-y-auto">
            <div id="detail-kosong" class="flex h-full min-h-[240px] items-center justify-center p-8 text-center text-sm text-stone-500">
                Pilih kos di daftar atau di peta untuk melihat detailnya.
            </div>

            <div id="detail" class="hidden">
                <img id="d-foto-utama" src="" alt="" class="aspect-[16/10] w-full object-cover">

                <div id="d-thumbs" class="grid grid-cols-4 gap-2 px-4 pt-3"></div>

                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <h3 id="d-nama" class="text-lg font-bold leading-tight text-stone-900"></h3>
                        <span id="d-tipe" class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold"></span>
                    </div>
                    <p class="mt-3 text-xl font-extrabold text-[#B46C54]"><span id="d-harga"></span> <span class="text-xs font-medium text-stone-500">/ bulan</span></p>
                    <p id="d-alamat" class="mt-3 text-xs leading-relaxed text-stone-500"></p>
                    <p id="d-jarak" class="mt-1 text-xs text-stone-500"></p>

                    <hr class="my-4 border-stone-200">

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
                </div>
            </div>
        </aside>
    </main>

    {{-- ================= SCRIPT ================= --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const KAMPUS = @json($kampus);
        const IKON = @json($ikonFasilitas);
        const IKON_DEFAULT = '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        const SEMUA_KOS = @json($kosList);
        const HARGA_MAX = {{ $hargaMax }};
        const WARNA = { 'Putri': '#f472b6', 'Putra': '#38bdf8', 'Campur': '#a78bfa' };
        const BADGE = {
            'Putri':  'border-pink-300 text-pink-600',
            'Putra':  'border-sky-300 text-sky-600',
            'Campur': 'border-purple-300 text-purple-600'
        };
        const COKLAT = '#B46C54';
        const RING_KM = [1, 3, 5, 10];

        const rupiah = n => 'Rp ' + n.toLocaleString('id-ID');
        const jarakTeks = m => m < 1000 ? (Math.round(m / 10) * 10) + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km';
        const $ = id => document.getElementById(id);

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
        const ring = {};
        RING_KM.forEach(km => {
            ring[km] = L.circle([KAMPUS.lat, KAMPUS.lng], {
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
                      '<span style="background:#1c1917;color:#fff;font:700 11px/1 \'Plus Jakarta Sans\',sans-serif;padding:5px 8px;border-radius:6px;white-space:nowrap">' + KAMPUS.nama + '</span></div>',
                iconSize: [0, 0]
            }),
            zIndexOffset: 1000, keyboard: false
        }).addTo(map);

        // ---------- State ----------
        const state = {
            harga: HARGA_MAX, jarak: 10, tipe: 'Semua', fasilitas: [], cari: '', urut: 'jarak', terpilih: null, rute: false
        };
        const markers = {};
        const layerKos = L.layerGroup().addTo(map);

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

        function terfilter() {
            const q = state.cari.trim().toLowerCase();
            let hasil = SEMUA_KOS.filter(k =>
                k.harga <= state.harga &&
                k.jarak_m <= state.jarak * 1000 &&
                (state.tipe === 'Semua' || k.tipe === state.tipe) &&
                state.fasilitas.every(f => k.fasilitas.includes(f)) &&
                (!q || k.nama.toLowerCase().includes(q) || k.alamat.toLowerCase().includes(q))
            );
            if (state.urut === 'jarak') hasil.sort((a, b) => a.jarak_m - b.jarak_m);
            if (state.urut === 'murah') hasil.sort((a, b) => a.harga - b.harga);
            if (state.urut === 'mahal') hasil.sort((a, b) => b.harga - a.harga);
            return hasil;
        }

        // ---------- Render ----------
        function render(opsi) {
            const hasil = terfilter();
            $('jumlah-kos').textContent = hasil.length;
            $('kosong').classList.toggle('hidden', hasil.length > 0);

            // Jika kos terpilih tersaring, batalkan pilihan
            if (state.terpilih && !hasil.some(k => k.id === state.terpilih)) { state.terpilih = null; state.rute = false; }

            // Daftar
            const ul = $('daftar-kos');
            ul.innerHTML = '';
            hasil.forEach(k => {
                const li = document.createElement('li');
                const aktif = k.id === state.terpilih;
                li.innerHTML =
                    '<button type="button" class="flex w-full gap-3 rounded-xl border p-2 text-left transition ' +
                    (aktif ? 'border-[#B46C54] bg-[#fbf5f3]' : 'border-stone-200 bg-white hover:border-stone-300') + '">' +
                    '<img src="' + k.fotos[0] + '" alt="" class="h-[72px] w-[72px] shrink-0 rounded-lg object-cover">' +
                    '<span class="min-w-0 flex-1">' +
                    '<span class="block truncate text-sm font-bold text-stone-900">' + k.nama + '</span>' +
                    '<span class="mt-0.5 block text-xs font-bold text-[#B46C54]">' + rupiah(k.harga) + ' <span class="font-medium text-stone-500">/ bulan</span></span>' +
                    '<span class="mt-0.5 block text-xs text-stone-500">' + jarakTeks(k.jarak_m) + ' dari kampus</span>' +
                    '<span class="mt-1 block truncate text-[10px] text-stone-500">' + k.fasilitas.slice(0, 3).join(' · ') + '</span>' +
                    '</span></button>';
                li.firstChild.addEventListener('click', () => pilih(k.id, true));
                ul.appendChild(li);
            });

            // Marker
            layerKos.clearLayers();
            Object.keys(markers).forEach(id => delete markers[id]);
            hasil.forEach(k => {
                const m = L.marker([k.lat, k.lng], { icon: ikonKos(k, k.id === state.terpilih), zIndexOffset: k.id === state.terpilih ? 900 : 0 });
                m.on('click', () => pilih(k.id, false));
                m.bindTooltip(k.nama + ' · ' + jarakTeks(k.jarak_m), { direction: 'top', offset: [0, -18] });
                markers[k.id] = m;
                layerKos.addLayer(m);
            });

            // Label "Kos dipilih" + garis jarak
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

            // Detail
            tampilkanDetail();

            if (opsi && opsi.fit) fitKeFilter(hasil);
        }

        let garisTerpilih = null;
        function tampilkanGarisTerpilih() {
            if (garisTerpilih) { map.removeLayer(garisTerpilih); garisTerpilih = null; }
            const k = SEMUA_KOS.find(x => x.id === state.terpilih);
            if (!k || !state.rute) return;
            const garis = L.polyline([[KAMPUS.lat, KAMPUS.lng], [k.lat, k.lng]], { color: COKLAT, weight: 2.5, dashArray: '2 7', lineCap: 'round' });
            const label = L.marker([(KAMPUS.lat + k.lat) / 2, (KAMPUS.lng + k.lng) / 2], {
                icon: L.divIcon({
                    className: '',
                    html: '<span style="display:inline-block;transform:translate(-50%,-50%);background:#fff;color:#955844;font:700 11px/1 \'Plus Jakarta Sans\',sans-serif;padding:5px 8px;border-radius:8px;border:1px solid #ead9d2;box-shadow:0 1px 3px rgba(0,0,0,.15);white-space:nowrap">' + jarakTeks(k.jarak_m) + '</span>',
                    iconSize: [0, 0]
                }),
                interactive: false, keyboard: false
            });
            garisTerpilih = L.layerGroup([garis, label]).addTo(map);
        }

        function tampilkanDetail() {
            const k = SEMUA_KOS.find(x => x.id === state.terpilih);
            $('detail').classList.toggle('hidden', !k);
            $('detail-kosong').classList.toggle('hidden', !!k);
            if (!k) return;

            $('d-foto-utama').src = k.fotos[0];
            $('d-foto-utama').alt = k.nama;
            $('d-thumbs').innerHTML = '';
            k.fotos.forEach((f, i) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'overflow-hidden rounded-lg border-2 ' + (i === 0 ? 'border-[#B46C54]' : 'border-transparent');
                b.innerHTML = '<img src="' + f + '" alt="" class="aspect-square w-full object-cover">';
                b.addEventListener('click', () => {
                    $('d-foto-utama').src = f;
                    [...$('d-thumbs').children].forEach(c => { c.classList.remove('border-[#B46C54]'); c.classList.add('border-transparent'); });
                    b.classList.add('border-[#B46C54]'); b.classList.remove('border-transparent');
                });
                $('d-thumbs').appendChild(b);
            });

            $('d-nama').textContent = k.nama;
            $('d-tipe').textContent = 'Kos ' + k.tipe;
            $('d-tipe').className = 'shrink-0 rounded-full border px-3 py-1 text-xs font-semibold ' + (BADGE[k.tipe] || '');
            $('d-harga').textContent = rupiah(k.harga);
            $('d-alamat').textContent = k.alamat;
            $('d-jarak').textContent = jarakTeks(k.jarak_m) + ' dari Kampus ' + KAMPUS.nama + ' (garis lurus)';
            $('d-fasilitas').innerHTML = k.fasilitas.map(f =>
                '<div class="flex flex-col items-center gap-1.5 text-center">' +
                '<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B46C54]">' + (IKON[f] || IKON_DEFAULT) + '</span>' +
                '<span class="text-[11px] leading-tight text-stone-500">' + f + '</span></div>'
            ).join('');
            $('d-tipe-teks').textContent = 'Kos ' + k.tipe;
            $('d-periode').textContent = k.periode;
            $('d-telepon').textContent = k.telepon;
            updateTombolRute();
        }

        function pilih(id, geser) {
            if (id !== state.terpilih) state.rute = false;
            state.terpilih = id;
            render();
            const k = SEMUA_KOS.find(x => x.id === id);
            if (k && geser) map.flyTo([k.lat, k.lng], Math.max(map.getZoom(), 15), { duration: 0.6 });
        }

        function fitKeFilter(hasil) {
            map.fitBounds(L.circle([KAMPUS.lat, KAMPUS.lng], { radius: state.jarak * 1000 }).getBounds(), { padding: [30, 30] });
        }

        // ---------- Tombol rute (toggle garis kampus - kos) ----------
        function updateTombolRute() {
            const b = $('d-rute');
            if (!b) return;
            $('d-rute-teks').textContent = state.rute ? 'Sembunyikan Rute' : 'Lihat Rute';
            b.classList.toggle('bg-[#B46C54]', !state.rute);
            b.classList.toggle('text-white', !state.rute);
            b.classList.toggle('bg-white', state.rute);
            b.classList.toggle('text-[#B46C54]', state.rute);
        }
        $('d-rute').addEventListener('click', () => {
            const k = SEMUA_KOS.find(x => x.id === state.terpilih);
            if (!k) return;
            state.rute = !state.rute;
            tampilkanGarisTerpilih();
            updateTombolRute();
            if (state.rute) {
                map.fitBounds([[KAMPUS.lat, KAMPUS.lng], [k.lat, k.lng]], { padding: [90, 90], maxZoom: 16 });
            }
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
        const ddUrut = buatDropdown('dd-urut', v => { state.urut = v; render(); });

        inHarga.addEventListener('input', () => { $('f-harga-label').textContent = rupiah(+inHarga.value); });
        inJarak.addEventListener('input', () => { $('f-jarak-label').textContent = (+inJarak.value).toString().replace('.', ',') + ' km'; });

        function terapkan() {
            state.harga = +inHarga.value;
            state.jarak = +inJarak.value;
            state.tipe = ddTipe.get();
            state.fasilitas = [...document.querySelectorAll('.f-fasilitas:checked')].map(c => c.value);
            render({ fit: true });
        }
        $('btn-terapkan').addEventListener('click', terapkan);

        $('btn-reset').addEventListener('click', () => {
            inHarga.value = HARGA_MAX; inJarak.value = 10; ddTipe.set('Semua', true);
            document.querySelectorAll('.f-fasilitas').forEach(c => c.checked = false);
            $('f-cari').value = ''; state.cari = '';
            $('f-harga-label').textContent = rupiah(HARGA_MAX);
            $('f-jarak-label').textContent = '10 km';
            terapkan();
        });

        document.querySelectorAll('.chip').forEach(c => c.addEventListener('click', () => {
            ddTipe.set(c.dataset.tipe, true);
            state.tipe = c.dataset.tipe;
            render();
        }));

        $('f-cari').addEventListener('input', e => { state.cari = e.target.value; render(); });
        
        // ---------- Awal ----------
        const awal = terfilter();
        if (awal.length) state.terpilih = awal[0].id;
        render({ fit: true });
        setTimeout(() => map.invalidateSize(), 200);
    });
    </script>
</body>
</html>