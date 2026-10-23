<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Kost.in')</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/logo kost.in.png') }}" type="image/png">

    {{-- Font: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @stack('styles')

    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body_class', 'bg-white') font-sans text-stone-800 antialiased selection:bg-[#B46C54] selection:text-white">

    @php
        $onBeranda = request()->routeIs('pengguna.beranda');
        $prefix = $onBeranda ? '' : route('pengguna.beranda');

        $menu = [
            ['key' => 'beranda', 'label' => 'Beranda',     'url' => route('pengguna.beranda'),    'aktif' => $onBeranda],
            ['key' => 'peta',    'label' => 'Peta Kos',    'url' => $prefix . '#peta',            'aktif' => false],
            ['key' => 'tentang', 'label' => 'Tentang',     'url' => $prefix . '#tentang',         'aktif' => false],
            ['key' => 'jelajah', 'label' => 'Jelajah Kos', 'url' => route('pengguna.jelajahKos'), 'aktif' => request()->routeIs('pengguna.jelajahKos')],
        ];
    @endphp

    {{-- NAVBAR UTAMA (Lebar disamakan menjadi max-w-[1600px] dengan padding px-6) --}}
    <header class="sticky top-0 z-[2000] border-b border-stone-200 bg-white/95 backdrop-blur-md">
        <nav class="mx-auto flex max-w-[1600px] items-center justify-between px-6 py-3 md:grid md:grid-cols-[1fr_auto_1fr] md:py-4">
            {{-- Logo --}}
            <a href="{{ route('pengguna.beranda') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo kost.in.png') }}" alt="Kost.in" class="h-7 w-auto object-contain">
                <span class="text-lg font-extrabold tracking-tight text-stone-900">Kost<span class="text-[#B46C54]">.in</span></span>
            </a>

            {{-- Menu Desktop --}}
            <ul class="hidden items-center justify-center gap-1 text-sm md:flex">
                @foreach ($menu as $item)
                    <li>
                        <a href="{{ $item['url'] }}" data-key="{{ $item['key'] }}" data-aktif="{{ $item['aktif'] ? 'true' : 'false' }}"
                           class="nav-link relative block px-4 py-2 font-medium text-stone-600 transition-colors hover:text-stone-900 data-[aktif=true]:font-bold data-[aktif=true]:text-[#B46C54]">
                            {{ $item['label'] }}
                            <span class="nav-underline absolute inset-x-4 -bottom-[17px] h-0.5 rounded-full bg-[#B46C54] opacity-0 transition-opacity [[data-aktif=true]_&]:opacity-100"></span>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Spacer --}}
            <div class="hidden md:block"></div>

            {{-- Tombol Mobile --}}
            <button type="button" id="nav-toggle" class="flex h-10 w-10 items-center justify-center rounded-lg border border-stone-200 text-stone-700 md:hidden">
                <svg id="nav-ikon-buka" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg id="nav-ikon-tutup" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </nav>

        {{-- Menu Mobile --}}
        <div id="nav-mobile" class="hidden border-t border-stone-100 bg-white md:hidden">
            <ul class="mx-auto max-w-[1600px] space-y-1 px-4 py-3">
                @foreach ($menu as $item)
                    <li>
                        <a href="{{ $item['url'] }}" data-key="{{ $item['key'] }}" data-aktif="{{ $item['aktif'] ? 'true' : 'false' }}"
                           class="nav-link block rounded-lg px-4 py-3 text-sm font-medium text-stone-700 transition-colors data-[aktif=true]:bg-[#fbf5f3] data-[aktif=true]:font-bold data-[aktif=true]:text-[#B46C54]">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
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
            if (btn) {
                btn.addEventListener('click', () => {
                    const buka = panel.classList.contains('hidden');
                    panel.classList.toggle('hidden', !buka);
                    document.getElementById('nav-ikon-buka').classList.toggle('hidden', buka);
                    document.getElementById('nav-ikon-tutup').classList.toggle('hidden', !buka);
                });
            }

            // Penanda aktif otomatis saat di scroll (khusus beranda)
            const sections = ['peta', 'tentang'].map(id => document.getElementById(id)).filter(Boolean);
            if (awal === 'beranda' && sections.length) {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(en => { if (en.isIntersecting) setAktif(en.target.id); });
                }, { rootMargin: '-45% 0px -50% 0px' });
                sections.forEach(s => observer.observe(s));
                window.addEventListener('scroll', () => { if (window.scrollY < 200) setAktif(awal); }, { passive: true });
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>