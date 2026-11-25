<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Kost.in')</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/logo kost.in.svg') }}" type="image/svg+xml">

    {{-- Font: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @stack('styles')

    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body_class', 'bg-white') font-sans text-stone-800 antialiased selection:bg-[#B46C54] selection:text-white">

    @php
        $onBeranda = request()->routeIs('pengguna.beranda');
        $prefix = $onBeranda ? '' : route('pengguna.beranda');

        $menu = [
            ['key' => 'beranda', 'label' => 'Beranda',      'url' => route('pengguna.beranda'),    'aktif' => $onBeranda],
            ['key' => 'tentang', 'label' => 'Tentang',      'url' => $prefix . '#tentang',         'aktif' => false],
            ['key' => 'peta',    'label' => 'Peta',         'url' => $prefix . '#peta',            'aktif' => false],
            ['key' => 'jelajah', 'label' => 'Jelajah Kost', 'url' => route('pengguna.jelajahKos'), 'aktif' => request()->routeIs('pengguna.jelajahKos')],
        ];
    @endphp

    {{-- NAVBAR UTAMA: Floating Pill Navbar Konsisten untuk Semua Halaman (Beranda, Jelajah Kos, dll) --}}
    <header class="fixed top-0 left-0 right-0 z-[2000] pt-3 sm:pt-4 pb-2 px-4 pointer-events-none bg-transparent transition-all @yield('header_class')">
        <nav class="pointer-events-auto mx-auto flex max-w-5xl items-center justify-between rounded-full bg-white px-5 sm:px-6 py-2 sm:py-2.5 shadow-md border border-emerald-100/60">
            {{-- Logo --}}
            <a href="{{ route('pengguna.beranda') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo kost.in.svg') }}" alt="Kost.in" class="h-7 sm:h-8 w-auto object-contain">
                <span class="text-base sm:text-lg font-black tracking-tight text-stone-900">Kost<span class="text-[#B8694C]">.in</span></span>
            </a>

            {{-- Menu Desktop --}}
            <ul class="hidden items-center justify-center gap-7 text-xs font-bold text-stone-700 md:flex">
                @foreach ($menu as $item)
                    <li>
                        <a href="{{ $item['url'] }}" data-key="{{ $item['key'] }}" data-aktif="{{ $item['aktif'] ? 'true' : 'false' }}"
                           class="nav-link relative block py-1 font-bold text-stone-600 transition-colors hover:text-[#B8694C] data-[aktif=true]:text-[#B8694C]">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Tombol Cari Kost Desktop --}}
            <div class="hidden md:flex justify-end">
                <a href="{{ route('pengguna.jelajahKos') }}" class="rounded-full bg-[#B8694C] px-5 py-2 text-xs font-bold text-white transition hover:bg-[#955844] shadow-sm">
                    Cari Kost
                </a>
            </div>

            {{-- Tombol Mobile --}}
            <button type="button" id="nav-toggle" aria-label="Toggle menu" class="flex h-9 w-9 items-center justify-center rounded-full border border-stone-200 text-stone-700 md:hidden">
                <svg id="nav-ikon-buka" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg id="nav-ikon-tutup" class="hidden h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </nav>

        {{-- Menu Mobile --}}
        <div id="nav-mobile" class="pointer-events-auto hidden mt-2 mx-auto max-w-5xl rounded-2xl bg-white p-4 shadow-lg border border-stone-100 md:hidden">
            <ul class="space-y-1">
                @foreach ($menu as $item)
                    <li>
                        <a href="{{ $item['url'] }}" data-key="{{ $item['key'] }}" data-aktif="{{ $item['aktif'] ? 'true' : 'false' }}"
                           class="nav-link block rounded-xl px-4 py-2.5 text-sm font-bold text-stone-700 transition-colors data-[aktif=true]:bg-[#fbf5f3] data-[aktif=true]:text-[#B8694C]">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
                <li class="pt-2">
                    <a href="{{ route('pengguna.jelajahKos') }}" class="block text-center rounded-full bg-[#B8694C] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#955844] shadow-sm">
                        Cari Kost
                    </a>
                </li>
            </ul>
        </div>
    </header>

    {{-- KONTEN HALAMAN --}}
    @yield('content')

    {{-- SCRIPT NAVBAR --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const links = [...document.querySelectorAll('.nav-link')];
            const awal = (links.find(l => l.dataset.aktif === 'true') || {}).dataset?.key;

            function setAktif(key) { links.forEach(l => { l.dataset.aktif = String(l.dataset.key === key); }); }

            // Logika Menu Mobile
            const btn = document.getElementById('nav-toggle');
            const panel = document.getElementById('nav-mobile');
            function setMenuMobile(buka) {
                if (!panel) return;
                panel.classList.toggle('hidden', !buka);
                const bukaIkon = document.getElementById('nav-ikon-buka');
                const tutupIkon = document.getElementById('nav-ikon-tutup');
                if (bukaIkon) bukaIkon.classList.toggle('hidden', buka);
                if (tutupIkon) tutupIkon.classList.toggle('hidden', !buka);
            }
            if (btn && panel) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    setMenuMobile(panel.classList.contains('hidden'));
                });
                document.addEventListener('click', (e) => {
                    if (!panel.classList.contains('hidden') && !panel.contains(e.target) && !btn.contains(e.target)) {
                        setMenuMobile(false);
                    }
                });
            }

            // Penanda aktif otomatis saat di scroll (khusus beranda)
            const sections = ['peta', 'tentang', 'kos'].map(id => document.getElementById(id)).filter(Boolean);
            if (awal === 'beranda' && sections.length) {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(en => { if (en.isIntersecting) setAktif(en.target.id); });
                }, { rootMargin: '-30% 0px -40% 0px' });
                sections.forEach(s => observer.observe(s));
                window.addEventListener('scroll', () => { if (window.scrollY < 200) setAktif(awal); }, { passive: true });
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>
