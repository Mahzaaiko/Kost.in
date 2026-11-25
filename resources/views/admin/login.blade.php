<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Kost.in</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/logo kost.in.svg') }}" type="image/svg+xml">
    
    {{-- Font: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Leaflet CSS & JS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom Pin Kos di Peta Preview */
        .custom-pin {
            background-color: white;
            border: 2px solid #B8694C;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px -1px rgba(0, 0, 0, 0.15);
        }
        .custom-pin-inner {
            width: 8px;
            height: 8px;
            background-color: #B8694C;
            border-radius: 50%;
        }

        /* Custom Pin Kampus SV IPB */
        .custom-pin-main {
            background-color: #362C29;
            color: white;
            border: 2px solid #F4EFEA;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 15px -3px rgba(54, 44, 41, 0.4);
        }

        /* Custom Scrollbar Khusus Panel Kiri (Gelap Muted & Elegan) */
        .panel-kiri-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4D3D38 transparent;
        }
        .panel-kiri-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .panel-kiri-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .panel-kiri-scroll::-webkit-scrollbar-thumb {
            background-color: #4D3D38;
            border-radius: 9999px;
        }
        .panel-kiri-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #634F49;
        }
        .panel-kiri-scroll::-webkit-scrollbar-button {
            display: none;
            width: 0;
            height: 0;
        }
    </style>
</head>
<body class="h-screen w-full overflow-hidden bg-[#F4EFEA] font-sans antialiased text-stone-800 selection:bg-[#B8694C] selection:text-white">

<div class="flex h-screen w-full flex-col lg:flex-row overflow-hidden">
    
    {{-- ================= PANEL KIRI (WARM DARK CHOCOLATE + MAP PREVIEW, BISA DI-SCROLL MANDIRI) ================= --}}
    <div data-lenis-scroll class="panel-kiri-scroll relative hidden w-full lg:w-1/2 h-full flex-col bg-[#362C29] text-white lg:flex overflow-y-auto">
        <div data-lenis-content class="relative z-10 flex min-h-full flex-col justify-between p-8 xl:p-12 pb-14 xl:pb-16 space-y-8">
            
            {{-- Header Logo --}}
            <div class="flex items-center justify-between shrink-0">
                <a href="{{ route('pengguna.beranda') }}" class="inline-flex items-center gap-3 group transition">
                    <img src="{{ asset('images/logo kost.in.svg') }}" alt="Kost.in Logo" class="h-9 w-auto object-contain">
                    <span class="text-2xl font-black tracking-tight text-white">Kost<span class="text-[#B8694C]">.in</span></span>
                </a>
            </div>

            {{-- Konten Utama Panel Kiri --}}
            <div class="my-auto space-y-6 py-4">
                <div class="space-y-3">
                    <h1 class="text-3xl font-extrabold leading-tight text-white xl:text-4xl 2xl:text-5xl">
                        Kelola Data Kos<br>dengan Mudah
                    </h1>
                    <p class="max-w-md text-sm text-stone-300 leading-relaxed">
                        Kelola informasi kos, fasilitas, dan lokasi untuk memastikan data pemetaan kos di sekitar Sekolah Vokasi IPB tetap akurat dan terverifikasi.
                    </p>
                </div>

                {{-- Kartu Preview Peta Leaflet --}}
                <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-2 shadow-2xl backdrop-blur-sm">
                    {{-- Container Peta Leaflet --}}
                    <div id="login-map" class="h-64 xl:h-72 w-full rounded-2xl"></div>

                    {{-- Floating Badge Status Data Kos Real-time --}}
                    <div class="absolute bottom-5 left-5 z-[1000] flex items-center gap-3 rounded-2xl bg-white/95 px-4 py-2.5 shadow-xl backdrop-blur-md border border-stone-200/80">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#F8EFEA] text-[#B8694C] shrink-0">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-bold text-stone-900">{{ $totalKos ?? 83 }} Total Kos</p>
                                <span class="h-1.5 w-1.5 rounded-full bg-stone-300"></span>
                                <span class="rounded-md bg-[#FDF7EA] px-1.5 py-0.5 text-[10px] font-bold text-[#B8694C]">{{ $kosAktif ?? 23 }} Aktif</span>
                            </div>
                            <p class="text-[10px] text-stone-500 font-medium">Area Sekolah Vokasi IPB</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Panel Kiri --}}
            <div class="flex items-center justify-between text-xs text-stone-400 shrink-0 pt-6 pb-2">
                <p>&copy; {{ date('Y') }} Kost.in &mdash; Sistem Informasi Pemetaan Kos</p>
                <a href="{{ route('pengguna.beranda') }}" class="hover:text-white transition inline-flex items-center gap-1 font-semibold">
                    <span>Lihat Beranda</span>
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

        </div>
    </div>

    {{-- ================= PANEL KANAN (FORM LOGIN TETAP STAY / TIDAK BISA DI-SCROLL DI DESKTOP) ================= --}}
    <div class="relative flex flex-1 w-full lg:w-1/2 h-full flex-col items-center justify-center p-4 sm:p-8 md:p-12 overflow-y-auto lg:overflow-hidden bg-[#F4EFEA]">
        
        {{-- Header Mobile (Hanya tampil di tablet & layar kecil) --}}
        <div class="mb-6 flex flex-col items-center gap-2 lg:hidden text-center shrink-0">
            <a href="{{ route('pengguna.beranda') }}" class="inline-flex items-center gap-2.5">
                <img src="{{ asset('images/logo kost.in.svg') }}" alt="Kost.in Logo" class="h-8 w-auto object-contain">
                <span class="text-2xl font-black tracking-tight text-stone-900">Kost<span class="text-[#B8694C]">.in</span></span>
            </a>
        </div>

        {{-- Card Container Login (Tetap Stay dan Berdiri Kokoh di Tengah) --}}
        <div class="w-full max-w-md rounded-[32px] border border-stone-200/80 bg-white p-7 sm:p-10 shadow-xl shadow-stone-300/30 shrink-0">
            
            {{-- Header Form --}}
            <div class="mb-7">
                <h2 class="text-2xl font-extrabold text-stone-900 sm:text-3xl">Login Admin</h2>
                <p class="mt-1.5 text-xs sm:text-sm font-medium text-stone-500">Masuk untuk mengelola data Kost.in</p>
            </div>

            {{-- Alert Error jika gagal login --}}
            @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50/80 p-3.5 text-xs font-semibold text-red-700">
                    <svg class="h-4 w-4 shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Form Login --}}
            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4 sm:space-y-5">
                @csrf

                {{-- Input 1: Email / Username --}}
                <div>
                    <label class="mb-2 block text-xs font-bold text-stone-700">Email atau Username</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/></svg>
                        </span>
                        <input 
                            type="text" 
                            name="login" 
                            required 
                            autofocus
                            value="{{ old('login') }}"
                            class="block w-full rounded-2xl border border-stone-200 bg-stone-50/50 py-3 pl-10 pr-4 text-xs sm:text-sm text-stone-900 placeholder:text-stone-400 transition focus:border-[#B8694C] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#B8694C]/20" 
                            placeholder="admin@kostin.test"
                        >
                    </div>
                </div>

                {{-- Input 2: Password (dengan toggle Alpine.js) --}}
                <div x-data="{ showPassword: false }">
                    <label class="mb-2 block text-xs font-bold text-stone-700">Password</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-stone-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <input 
                            :type="showPassword ? 'text' : 'password'" 
                            name="password" 
                            required 
                            class="block w-full rounded-2xl border border-stone-200 bg-stone-50/50 py-3 pl-10 pr-11 text-xs sm:text-sm text-stone-900 placeholder:text-stone-400 transition focus:border-[#B8694C] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#B8694C]/20" 
                            placeholder="Masukkan password"
                        >
                        {{-- Tombol Mata Show/Hide Password --}}
                        <button 
                            type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-stone-400 transition hover:text-stone-700"
                            aria-label="Tampilkan / sembunyikan password"
                        >
                            <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Row Checkbox & Lupa Password --}}
                <div class="flex items-center justify-between pt-1 text-xs">
                    <label class="flex cursor-pointer items-center gap-2 font-semibold text-stone-600 hover:text-stone-900">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-stone-300 accent-[#B8694C] text-[#B8694C] focus:ring-[#B8694C]">
                        Ingat saya
                    </label>
                    <a href="mailto:admin@kostin.test?subject=Bantuan%20Reset%20Password%20Admin" class="font-bold text-[#B8694C] transition hover:text-[#955844] hover:underline">
                        Lupa password?
                    </a>
                </div>

                {{-- Tombol Submit --}}
                <button 
                    type="submit" 
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-full bg-[#B8694C] py-3.5 text-xs sm:text-sm font-bold text-white shadow-lg shadow-[#B8694C]/25 transition hover:bg-[#955844] hover:shadow-xl active:scale-[0.99]"
                >
                    <span>Masuk</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            {{-- Footer Keterangan Khusus Admin --}}
            <div class="mt-8 flex flex-col items-center gap-3 border-t border-stone-100 pt-6 text-xs text-stone-400">
                <div class="flex items-center gap-1.5 font-medium">
                    <svg class="h-3.5 w-3.5 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Akses khusus administrator Kost.in</span>
                </div>
                <a href="{{ route('pengguna.beranda') }}" class="font-bold text-stone-600 hover:text-[#B8694C] transition inline-flex items-center gap-1">
                    <span>&larr; Kembali ke Beranda</span>
                </a>
            </div>

        </div>

    </div>

</div>

{{-- Script Inisialisasi Peta Preview Leaflet --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Koordinat Kampus SV IPB Bogor
    const centerSV = [{{ $kampus->lat ?? -6.5888 }}, {{ $kampus->lng ?? 106.8062 }}];

    // Inisialisasi Peta Leaflet Preview
    const map = L.map('login-map', {
        center: centerSV,
        zoom: 15,
        zoomControl: false,
        scrollWheelZoom: false,
        dragging: true
    });

    // Layer Peta OpenStreetMap
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Pastikan ukuran Leaflet map terhitung dengan pas saat layout render
    setTimeout(() => {
        map.invalidateSize();
    }, 250);

    // Custom Icon untuk Pin Utama (Kampus SV IPB)
    const mainIcon = L.divIcon({
        className: 'custom-pin-main',
        html: `<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });
    L.marker(centerSV, { icon: mainIcon }).addTo(map).bindTooltip("Kampus SV IPB", { direction: 'top', offset: [0, -18] });

    // Titik Kos dari Database BE
    const titikKos = @json($titikKos ?? []);
    
    // Fallback data jika titikKos kosong
    const fallbackLocations = [
        [-6.5865, 106.8035],
        [-6.5910, 106.8090],
        [-6.5840, 106.8080],
        [-6.5925, 106.8020],
        [-6.5895, 106.8110],
        [-6.5878, 106.8055],
        [-6.5902, 106.8042]
    ];

    const smallPinIcon = L.divIcon({
        className: 'custom-pin',
        html: `<div class="custom-pin-inner"></div>`,
        iconSize: [18, 18],
        iconAnchor: [9, 9]
    });

    if (titikKos && titikKos.length > 0) {
        titikKos.forEach(k => {
            if (k.lat && k.lng) {
                L.marker([k.lat, k.lng], { icon: smallPinIcon })
                    .addTo(map)
                    .bindTooltip(k.nama || 'Kos Terverifikasi', { direction: 'top', offset: [0, -12] });
            }
        });
    } else {
        fallbackLocations.forEach(loc => {
            L.marker(loc, { icon: smallPinIcon }).addTo(map);
        });
    }
});
</script>

</body>
</html>