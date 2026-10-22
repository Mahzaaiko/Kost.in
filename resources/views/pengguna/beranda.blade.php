<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kost.in | Temukan Kos Terbaik di Sekitar SV IPB</title>

    {{-- Font: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Leaflet --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-stone-800 antialiased selection:bg-[#B46C54] selection:text-white">

    {{-- ================= NAVBAR ================= --}}
    @php
        // Tambah atau ubah menu di sini. 'aktif' = kondisi saat menu dianggap aktif.
        $menu = [
            ['label' => 'Beranda',     'url' => route('pengguna.beranda'), 'aktif' => request()->routeIs('pengguna.beranda')],
            ['label' => 'Peta Kos',    'url' => '#peta',                   'aktif' => false, 'section' => 'peta'],
            ['label' => 'Tentang',     'url' => '#tentang',                'aktif' => false, 'section' => 'tentang'],
            ['label' => 'Jelajah Kos', 'url' => route('pengguna.jelajahKos'), 'aktif' => request()->routeIs('pengguna.jelajahKos')],
        ];
    @endphp

    <header class="sticky top-0 z-50 border-b border-stone-200 bg-white/95 backdrop-blur">
        <nav class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-y-3 px-6 py-4 md:grid-cols-[1fr_auto_1fr]">
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
                        @isset($item['section']) data-section="{{ $item['section'] }}" @endisset
                        @if ($item['aktif']) aria-current="page" @endif
                        class="nav-link relative block px-4 py-2 font-medium transition-colors
                                {{ $item['aktif'] ? 'is-active text-[#B46C54] font-bold' : 'text-stone-600 hover:text-stone-900' }}">
                            {{ $item['label'] }}
                            {{-- Garis bawah penanda aktif --}}
                            <span class="nav-underline absolute inset-x-4 -bottom-[17px] h-0.5 rounded-full bg-[#B46C54] transition-opacity
                                        {{ $item['aktif'] ? 'opacity-100' : 'opacity-0' }}"></span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Spacer agar menu tetap di tengah --}}
            <div class="hidden md:block"></div>
        </nav>
    </header>

    {{-- Penanda aktif otomatis untuk menu yang menuju section (#peta, #tentang) saat di-scroll atau diklik --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const links = document.querySelectorAll('.nav-link');
        const beranda = document.querySelector('.nav-link[aria-current="page"]');
        const sectionLinks = [...links].filter(l => l.dataset.section);

        function setAktif(target) {
            links.forEach(l => {
                const on = l === target;
                l.classList.toggle('text-[#B46C54]', on);
                l.classList.toggle('font-bold', on);
                l.classList.toggle('text-stone-600', !on);
                l.querySelector('.nav-underline').classList.toggle('opacity-100', on);
                l.querySelector('.nav-underline').classList.toggle('opacity-0', !on);
            });
        }

        // Saat scroll: tandai menu sesuai section yang sedang terlihat
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const link = sectionLinks.find(l => l.dataset.section === entry.target.id);
                if (link) setAktif(link);
            });
        }, { rootMargin: '-45% 0px -50% 0px' });

        sectionLinks.forEach(l => {
            const sec = document.getElementById(l.dataset.section);
            if (sec) observer.observe(sec);
        });

        // Kembali ke Beranda saat scroll ke paling atas
        window.addEventListener('scroll', () => {
            if (window.scrollY < 200 && beranda) setAktif(beranda);
        }, { passive: true });
    });
    </script>

    {{-- ================= HERO ================= --}}
    <section class="mx-auto max-w-6xl px-6 py-16 lg:py-24">
        <div class="grid items-center gap-12 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-stone-900 sm:text-5xl">
                    Temukan kos terbaik di sekitar Sekolah Vokasi IPB
                </h1>
                <p class="mt-5 max-w-md text-base leading-relaxed text-stone-600">
                    Cari hunian yang dekat kampus, sesuai budget, dan punya fasilitas yang kamu butuhkan.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="#peta" class="rounded-lg bg-[#B46C54] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#955844]">
                        Jelajahi peta kos
                    </a>
                    <a href="#kos" class="rounded-lg border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-50">
                        Lihat daftar kos
                    </a>
                </div>
            </div>

            <div class="lg:col-span-6">
                <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1000&q=80" alt="Kamar kos" class="aspect-[4/3] w-full rounded-xl object-cover">
            </div>
        </div>
    </section>

    {{-- ================= KEUNGGULAN ================= --}}
    <section class="bg-[#FAFAFA] py-16 lg:py-24 border-y border-stone-100">
        <div class="mx-auto max-w-6xl px-6">
            <div class="text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-[#B46C54]">LEBIH MUDAH, LEBIH PASTI</p>
                <h2 class="mt-2 text-3xl font-bold text-stone-900 sm:text-4xl">Kenapa menggunakan Kost.in?</h2>
                <p class="mx-auto mt-4 max-w-2xl text-stone-500">Semua informasi penting untuk memilih kos tersedia dalam satu tempat, tanpa proses yang rumit.</p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Card 1 --}}
                <div class="rounded-[24px] bg-white border border-stone-100 p-8 shadow-sm transition hover:border-[#B46C54]/30 hover:shadow-md">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F8EFEC] text-[#B46C54]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <h3 class="mt-6 text-lg font-bold text-stone-900">Data Terverifikasi</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-500">Informasi akurat langsung dari pemilik, meminimalisir penipuan.</p>
                </div>
                {{-- Card 2 --}}
                <div class="rounded-[24px] bg-white border border-stone-100 p-8 shadow-sm transition hover:border-[#B46C54]/30 hover:shadow-md">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F8EFEC] text-[#B46C54]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </span>
                    <h3 class="mt-6 text-lg font-bold text-stone-900">Filter Lengkap</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-500">Temukan kos sesuai harga, fasilitas, dan jarak idamanmu.</p>
                </div>
                {{-- Card 3 --}}
                <div class="rounded-[24px] bg-white border border-stone-100 p-8 shadow-sm transition hover:border-[#B46C54]/30 hover:shadow-md">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F8EFEC] text-[#B46C54]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </span>
                    <h3 class="mt-6 text-lg font-bold text-stone-900">Hubungi Langsung</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-500">Dapatkan nomor WhatsApp pemilik tanpa harus melalui perantara.</p>
                </div>
                {{-- Card 4 --}}
                <div class="rounded-[24px] bg-white border border-stone-100 p-8 shadow-sm transition hover:border-[#B46C54]/30 hover:shadow-md">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F8EFEC] text-[#B46C54]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <h3 class="mt-6 text-lg font-bold text-stone-900">100% Gratis</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-500">Tidak ada biaya tersembunyi atau biaya admin bagi pencari kos.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= DAFTAR KOS ================= --}}
    <section id="kos" class="mx-auto max-w-6xl px-6 py-16 lg:py-24">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">Kos yang sering dilihat</h2>
                <p class="mt-3 text-stone-600">Pilihan kos dekat kampus dengan harga terjangkau.</p>
            </div>
            {{-- Tombol Lihat semua kos (Desktop) --}}
            <a href="#" class="hidden shrink-0 items-center gap-1 text-sm font-semibold text-[#B46C54] hover:underline sm:flex">
                Lihat semua kos 
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#B46C54"><path d="m560-240-56-58 142-142H160v-80h486L504-662l56-58 240 240-240 240Z"/></svg>
            </a>
        </div>

        @php
            // Titik pusat: Kampus SV IPB
            $kampus = ['nama' => 'Kampus SV IPB', 'lat' => -6.5886, 'lng' => 106.8032];

            // Jarak garis lurus (meter) dengan rumus haversine
            $hitungJarak = function ($lat1, $lng1, $lat2, $lng2) {
                $r = 6371000;
                $dLat = deg2rad($lat2 - $lat1);
                $dLng = deg2rad($lng2 - $lng1);
                $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
                return 2 * $r * asin(sqrt($a));
            };
            $formatJarak = fn ($m) => $m < 1000 ? round($m / 10) * 10 . ' m' : number_format($m / 1000, 1, ',', '.') . ' km';

            $kosList = collect([
                ['nama' => 'Kos Putri Melati',    'tipe' => 'Putri',  'harga' => 700000, 'fasilitas' => ['Wi-Fi', 'AC', 'Kamar mandi dalam'], 'foto' => 'photo-1554995207-c18c203602cb', 'lat' => -6.5990, 'lng' => 106.8085],
                ['nama' => 'Kos Putra Mandiri',   'tipe' => 'Putra',  'harga' => 550000, 'fasilitas' => ['Wi-Fi', 'Kamar mandi luar'],       'foto' => 'photo-1505691938895-1758d7feb511', 'lat' => -6.5850, 'lng' => 106.8000],
                ['nama' => 'Kos Griya Sejahtera', 'tipe' => 'Campur', 'harga' => 950000, 'fasilitas' => ['Wi-Fi', 'AC', 'Parkir motor'],    'foto' => 'photo-1598928506311-c55ded91a20c', 'lat' => -6.5800, 'lng' => 106.8150],
                ['nama' => 'Kos Putri Anggrek',   'tipe' => 'Putri',  'harga' => 800000, 'fasilitas' => ['Wi-Fi', 'Laundry'],                'foto' => null, 'lat' => -6.6050, 'lng' => 106.7950],
                ['nama' => 'Kos Putra Harapan',   'tipe' => 'Putra',  'harga' => 600000, 'fasilitas' => ['Wi-Fi', 'Parkir'],                 'foto' => null, 'lat' => -6.5700, 'lng' => 106.8030],
                ['nama' => 'Kos Campur Bersama',  'tipe' => 'Campur', 'harga' => 650000, 'fasilitas' => ['Wi-Fi', 'Dapur'],                  'foto' => null, 'lat' => -6.6150, 'lng' => 106.8100],
            ])->map(function ($k) use ($kampus, $hitungJarak, $formatJarak) {
                $k['jarak_m'] = (int) round($hitungJarak($kampus['lat'], $kampus['lng'], $k['lat'], $k['lng']));
                $k['jarak'] = $formatJarak($k['jarak_m']);
                $k['harga_fmt'] = 'Rp' . number_format($k['harga'], 0, ',', '.');
                return $k;
            });
            $kosKartu = $kosList->filter(fn ($k) => $k['foto'])->take(3);
        @endphp

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($kosKartu as $kos)
                <article class="group flex flex-col overflow-hidden rounded-xl border border-stone-200 bg-white transition hover:border-stone-300">
                    <div class="aspect-[4/3] overflow-hidden bg-stone-100">
                        <img src="https://images.unsplash.com/{{ $kos['foto'] }}?auto=format&fit=crop&w=800&q=80" alt="{{ $kos['nama'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-bold text-stone-900">{{ $kos['nama'] }}</h3>
                            <span class="shrink-0 rounded-md bg-stone-100 px-2 py-0.5 text-xs font-semibold text-stone-600">{{ $kos['tipe'] }}</span>
                        </div>
                        <p class="mt-1 text-sm text-stone-500">{{ $kos['jarak'] }} dari kampus</p>
                        <p class="mt-3 text-xs text-stone-500">{{ implode(' · ', $kos['fasilitas']) }}</p>

                        <div class="mt-5 flex items-center justify-between border-t border-stone-100 pt-4">
                            <p class="font-bold text-stone-900">{{ $kos['harga_fmt'] }} <span class="text-xs font-normal text-stone-500">/ bulan</span></p>
                            <a href="#" class="text-sm font-semibold text-[#B46C54] hover:underline">Detail</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Tombol Lihat semua kos (Mobile) --}}
        <div class="mt-8 flex sm:hidden">
            <a href="#" class="flex items-center gap-1 text-sm font-semibold text-[#B46C54] hover:underline">
                Lihat semua kos 
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#B46C54"><path d="m560-240-56-58 142-142H160v-80h486L504-662l56-58 240 240-240 240Z"/></svg>
            </a>
        </div>
    </section>

    {{-- ================= PETA ================= --}}
    <section id="peta" class="border-y border-stone-200 bg-stone-50">
        <div class="mx-auto max-w-6xl px-6 py-16 lg:py-24">
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-12">
                <div class="lg:col-span-4">
                    <h2 class="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">Seberapa dekat kos pilihanmu?</h2>
                    <p class="mt-3 text-stone-600">Lihat persebaran kos berdasarkan radius jarak dari Sekolah Vokasi IPB.</p>

                    <dl class="mt-8 divide-y divide-stone-200 border-y border-stone-200">
                        <div class="flex items-baseline justify-between py-4">
                            <dt class="text-sm text-stone-600">Kos terdata</dt>
                            <dd class="text-xl font-bold text-stone-900">128+</dd>
                        </div>
                        <div class="flex items-baseline justify-between py-4">
                            <dt class="text-sm text-stone-600">Pilihan radius</dt>
                            <dd class="text-xl font-bold text-stone-900">4</dd>
                        </div>
                        <div class="flex items-baseline justify-between py-4">
                            <dt class="text-sm text-stone-600">Data tervalidasi</dt>
                            <dd class="text-xl font-bold text-stone-900">100%</dd>
                        </div>
                    </dl>

                    <a href="{{ route('pengguna.jelajahKos') }}" class="mt-8 inline-block rounded-lg bg-[#B46C54] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#955844]">
                        Buka peta interaktif
                    </a>
                </div>

                {{-- Peta interaktif (Leaflet + OpenStreetMap) --}}
                <div class="lg:col-span-8">
                    <div class="mb-3 flex flex-wrap items-center gap-2" id="radius-filter">
                        <span class="mr-1 text-sm font-medium text-stone-600">Radius:</span>
                        <button type="button" data-radius="500" class="radius-btn rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700 transition">500 m</button>
                        <button type="button" data-radius="1000" class="radius-btn rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700 transition">1 km</button>
                        <button type="button" data-radius="2000" class="radius-btn rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700 transition">2 km</button>
                        <button type="button" data-radius="3000" class="radius-btn rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700 transition">3 km</button>
                        <span id="radius-info" class="ml-auto text-sm text-stone-500"></span>
                    </div>

                    <div id="peta-beranda" class="relative isolate z-0 h-[380px] overflow-hidden rounded-xl border border-stone-200 lg:h-[480px]"></div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-stone-500">
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-pink-500"></span>Putri</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>Putra</span>
                        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-purple-500"></span>Campur</span>
                        <span class="sm:ml-auto">Jarak dihitung garis lurus dari kampus, bukan jarak tempuh.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TENTANG (Desain Baru) ================= --}}
    <section id="tentang" class="bg-[#FDFCF9] py-16 lg:py-24">
        <div class="mx-auto max-w-6xl px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                {{-- Bagian Kiri: Gambar --}}
                <div class="relative w-full overflow-hidden rounded-[24px] shadow-sm">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1000&q=80" alt="Tentang Kost.in" class="aspect-[4/3] w-full object-cover">
                </div>

                {{-- Bagian Kanan: Teks --}}
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-[#B46C54]">TENTANG KOST.IN</p>
                    
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-stone-900 sm:text-4xl leading-tight">
                        Membuat pencarian kos jadi lebih jelas dan manusiawi.
                    </h2>
                    
                    <p class="mt-5 text-sm leading-relaxed text-stone-500">
                        Kost.in menyatukan informasi lokasi, biaya, fasilitas, dan akses menuju kampus dalam satu pengalaman yang sederhana. Kamu bisa mengeksplorasi pilihan berdasarkan radius tanpa perlu membuat akun.
                    </p>
                    
                    {{-- Fitur Poin --}}
                    <div class="mt-8 grid grid-cols-2 gap-6">
                        <div class="border-l-2 border-[#B46C54] pl-4">
                            <h4 class="font-bold text-stone-900">Data ringkas</h4>
                            <p class="mt-1 text-xs text-stone-500">Informasi penting tanpa distraksi</p>
                        </div>
                        <div class="border-l-2 border-[#B46C54] pl-4">
                            <h4 class="font-bold text-stone-900">Peta intuitif</h4>
                            <p class="mt-1 text-xs text-stone-500">Lokasi, jarak, dan rute yang jelas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FOOTER ================= --}}
    <footer class="bg-[#38302E] text-stone-300">
        <div class="mx-auto max-w-6xl px-6 py-12">
            <div class="grid gap-10 md:grid-cols-12">
                <div class="md:col-span-6">
                    <a href="{{ route('pengguna.beranda') }}" class="flex items-center gap-2.5">
                        <div class="bg-white px-2 py-1 rounded-md">
                            <img src="{{ asset('images/logo kost.in.png') }}" alt="Kost.in" class="h-6 w-auto object-contain">
                        </div>
                        <span class="text-lg font-extrabold tracking-tight text-white">Kost<span class="text-[#E8A88F]">.in</span></span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-stone-400">
                        Platform pencarian dan pemetaan informasi kos untuk mahasiswa Sekolah Vokasi IPB University.
                    </p>
                </div>

                <div class="md:col-span-3">
                    <h3 class="text-sm font-bold text-white">Menu</h3>
                    <ul class="mt-4 space-y-2 text-sm text-stone-400">
                        <li><a href="{{ route('pengguna.beranda') }}" class="transition hover:text-white">Beranda</a></li>
                        <li><a href="#peta" class="transition hover:text-white">Peta Kos</a></li>
                        <li><a href="#tentang" class="transition hover:text-white">Tentang</a></li>
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <h3 class="text-sm font-bold text-white">Kontak</h3>
                    <ul class="mt-4 space-y-2 text-sm text-stone-400">
                        <li>halo@kostin.id</li>
                        <li>Kampus SV IPB, Bogor</li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-stone-400 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} Kost.in. Hak cipta dilindungi.</p>
                <div class="flex gap-5">
                    <a href="#" class="transition hover:text-white">Syarat &amp; Ketentuan</a>
                    <a href="#" class="transition hover:text-white">Kebijakan Privasi</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- ================= LEAFLET: PETA BERANDA ================= --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const kampus = @json($kampus);
        const daftarKos = @json($kosList->values());
        const warnaTipe = { 'Putri': '#ec4899', 'Putra': '#3b82f6', 'Campur': '#a855f7' };
        const WARNA_UTAMA = '#B46C54';
        const radiusBuffer = [500, 1000, 2000, 3000]; // meter

        const map = L.map('peta-beranda', { scrollWheelZoom: false }).setView([kampus.lat, kampus.lng], 14);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Scroll wheel zoom aktif hanya setelah peta diklik (agar tidak mengganggu scroll halaman)
        map.on('focus', () => map.scrollWheelZoom.enable());
        map.on('blur', () => map.scrollWheelZoom.disable());

        // Titik kampus
        L.marker([kampus.lat, kampus.lng], {
            icon: L.divIcon({
                className: '',
                html: '<div style="background:#1c1917;color:#fff;font:600 11px/1 \'Plus Jakarta Sans\',sans-serif;padding:6px 10px;border-radius:6px;white-space:nowrap;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35);transform:translate(-50%,-50%)">' + kampus.nama + '</div>',
                iconSize: [0, 0]
            }),
            zIndexOffset: 1000
        }).addTo(map);

        // Buffer radius (lingkaran) + label jarak di tepi utara tiap lingkaran
        const lingkaran = {};
        radiusBuffer.forEach(r => {
            lingkaran[r] = L.circle([kampus.lat, kampus.lng], {
                radius: r, color: WARNA_UTAMA, weight: 1.5, dashArray: '6 6',
                fillColor: WARNA_UTAMA, fillOpacity: 0.04
            }).addTo(map);

            const latTepi = kampus.lat + (r / 111320);
            L.marker([latTepi, kampus.lng], {
                icon: L.divIcon({
                    className: '',
                    html: '<span style="background:#fff;color:#955844;font:600 10px/1 \'Plus Jakarta Sans\',sans-serif;padding:3px 6px;border-radius:4px;border:1px solid #e7d3cb;white-space:nowrap;transform:translate(-50%,-50%);display:inline-block">' + (r >= 1000 ? (r / 1000) + ' km' : r + ' m') + '</span>',
                    iconSize: [0, 0]
                }),
                interactive: false
            }).addTo(map);
        });

        // Marker kos + garis jarak ke kampus
        const formatJarak = m => m < 1000 ? (Math.round(m / 10) * 10) + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km';
        const items = daftarKos.map(k => {
            const warna = warnaTipe[k.tipe] || WARNA_UTAMA;
            const marker = L.marker([k.lat, k.lng], {
                icon: L.divIcon({
                    className: '',
                    html: '<span style="display:block;width:16px;height:16px;border-radius:50%;background:' + warna + ';border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);transform:translate(-50%,-50%)"></span>',
                    iconSize: [0, 0]
                })
            });
            marker.bindPopup(
                '<div style="font-family:\'Plus Jakarta Sans\',sans-serif;min-width:150px">' +
                '<strong style="font-size:13px">' + k.nama + '</strong><br>' +
                '<span style="font-size:11px;color:#78716c">' + k.tipe + ' · ' + k.jarak + ' dari kampus</span><br>' +
                '<span style="font-size:12px;font-weight:700">' + k.harga_fmt + '</span> <span style="font-size:11px;color:#78716c">/ bulan</span>' +
                '</div>'
            );

            const garis = L.polyline([[kampus.lat, kampus.lng], [k.lat, k.lng]], {
                color: warna, weight: 1.5, opacity: 0.7, dashArray: '4 5'
            });
            const tengah = [(kampus.lat + k.lat) / 2, (kampus.lng + k.lng) / 2];
            const labelJarak = L.marker(tengah, {
                icon: L.divIcon({
                    className: '',
                    html: '<span style="background:#fff;color:#44403c;font:600 10px/1 \'Plus Jakarta Sans\',sans-serif;padding:2px 5px;border-radius:3px;border:1px solid #d6d3d1;white-space:nowrap;transform:translate(-50%,-50%);display:inline-block">' + formatJarak(k.jarak_m) + '</span>',
                    iconSize: [0, 0]
                }),
                interactive: false
            });

            const grup = L.layerGroup([garis, labelJarak, marker]);
            return { data: k, grup: grup };
        });

        // Filter radius
        const tombol = document.querySelectorAll('.radius-btn');
        const info = document.getElementById('radius-info');
        let radiusAktif = 3000;

        function terapkanRadius(r) {
            radiusAktif = r;
            let jumlah = 0;
            items.forEach(it => {
                const dalam = it.data.jarak_m <= r;
                if (dalam) { it.grup.addTo(map); jumlah++; } else { map.removeLayer(it.grup); }
            });
            radiusBuffer.forEach(rb => {
                lingkaran[rb].setStyle({
                    weight: rb === r ? 2.5 : 1.5,
                    fillOpacity: rb === r ? 0.10 : 0.03,
                    opacity: rb <= r ? 1 : 0.35
                });
            });
            tombol.forEach(b => {
                const aktif = parseInt(b.dataset.radius, 10) === r;
                b.classList.toggle('bg-[#B46C54]', aktif);
                b.classList.toggle('text-white', aktif);
                b.classList.toggle('border-[#B46C54]', aktif);
                b.classList.toggle('bg-white', !aktif);
                b.classList.toggle('text-stone-700', !aktif);
                b.classList.toggle('border-stone-300', !aktif);
            });
            info.textContent = jumlah + ' kos dalam radius ' + (r >= 1000 ? (r / 1000) + ' km' : r + ' m');
            map.fitBounds(lingkaran[r].getBounds(), { padding: [20, 20] });
        }
        tombol.forEach(b => b.addEventListener('click', () => terapkanRadius(parseInt(b.dataset.radius, 10))));
        terapkanRadius(radiusAktif);

        setTimeout(() => map.invalidateSize(), 200);
    });
    </script>
</body>
</html>