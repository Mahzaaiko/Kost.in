<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin | Kost.in</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('images/logo kost.in.png') }}" type="image/png">
    {{-- Font: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Leaflet CSS & JS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Tailwind CSS via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        /* Style custom pin peta preview */
        .custom-pin {
            background-color: white;
            border: 2px solid #B46C54;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .custom-pin-inner {
            width: 8px;
            height: 8px;
            background-color: #B46C54;
            border-radius: 50%;
        }
        /* Style custom pin utama */
        .custom-pin-main {
            background-color: #2D2727;
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="bg-[#E8E0D9] font-sans antialiased text-stone-800">

<div class="flex min-h-screen w-full">
    
    {{-- ================= PANEL KIRI (DARK BRAND + MAP PREVIEW) ================= --}}
    <div class="relative hidden w-1/2 flex-col justify-between bg-[#38302E] p-12 text-white lg:flex">
        
        {{-- Header Logo --}}
        <div>
            <a href="#" class="inline-flex items-center gap-3">
                <img src="{{ asset('images/logo kost.in.png') }}" alt="Kost.in Logo" class="h-9 w-auto object-contain">
                <span class="text-2xl font-extrabold tracking-tight text-[#E8A88F]">Kost<span class="text-[#FFFFFF]">.in</span></span>
            </a>
        </div>

        {{-- Konten Utama Panel Kiri --}}
        <div class="my-auto space-y-6 pt-12">
            <h1 class="text-4xl font-extrabold leading-tight text-white xl:text-5xl">
                Kelola Data Kos<br>dengan Mudah
            </h1>
            <p class="max-w-md text-sm text-stone-300 leading-relaxed">
                Kelola informasi kos, fasilitas, dan lokasi untuk memastikan data KosVokasi tetap akurat dan terbaru.
            </p>

            {{-- Kartu Preview Peta Leaflet --}}
            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-2 shadow-2xl backdrop-blur-sm">
                {{-- Container Peta Leaflet --}}
                <div id="login-map" class="h-72 w-full rounded-2xl"></div>

                {{-- Floating Badge Status Peta --}}
                <div class="absolute bottom-5 left-5 z-[1000] flex items-center gap-3 rounded-2xl bg-white/95 px-4 py-3 shadow-lg backdrop-blur-md">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B46C54]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-stone-900">128 lokasi terpetakan</p>
                        <p class="text-[10px] text-stone-500 font-semibold">Area Sekolah Vokasi IPB</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Panel Kiri --}}
        <p class="text-xs text-stone-400">
            &copy; {{ date('Y') }} Kost.in — Sistem Informasi Pemetaan Kos
        </p>
    </div>

    {{-- ================= PANEL KANAN (FORM LOGIN) ================= --}}
    <div class="flex w-full lg:w-1/2 flex-col items-center justify-center p-6 sm:p-12">
        
        {{-- Card Container Login --}}
        <div class="w-full max-w-md rounded-[32px] border border-stone-200/50 bg-white p-8 shadow-xl shadow-stone-300/30 sm:p-10">
            
            {{-- Header Form --}}
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-stone-900 sm:text-3xl">Login Admin</h2>
                <p class="mt-2 text-sm text-stone-500">Masuk untuk mengelola data Kost.in</p>
            </div>

            {{-- Alert Error jika gagal login --}}
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-semibold text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form Login --}}
            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
                @csrf

                {{-- Input 1: Email / Username --}}
                <div>
                    <label class="mb-2 block text-xs font-bold text-stone-700">Email atau Username</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-stone-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </span>
                        <input 
                            type="text" 
                            name="login" 
                            required 
                            value="{{ old('login') }}"
                            class="block w-full rounded-xl border border-stone-200 bg-white py-3 pl-11 pr-4 text-sm text-stone-900 placeholder:text-stone-300 transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" 
                            placeholder="admin@kostin.com"
                        >
                    </div>
                </div>

                {{-- Input 2: Password (dengan toggle Alpine.js) --}}
                <div x-data="{ showPassword: false }">
                    <label class="mb-2 block text-xs font-bold text-stone-700">Password</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-stone-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        <input 
                            :type="showPassword ? 'text' : 'password'" 
                            name="password" 
                            required 
                            class="block w-full rounded-xl border border-stone-200 bg-white py-3 pl-11 pr-11 text-sm text-stone-900 placeholder:text-stone-300 transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" 
                            placeholder="Masukkan password"
                        >
                        {{-- Tombol Mata Show/Hide Password --}}
                        <button 
                            type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-stone-400 transition hover:text-stone-600"
                        >
                            <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Row Checkbox & Lupa Password --}}
                <div class="flex items-center justify-between pt-1">
                    <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-stone-600">
                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-stone-300 text-[#B46C54] focus:ring-[#B46C54]">
                        Ingat saya
                    </label>
                    <a href="#" class="text-xs font-bold text-[#B46C54] transition hover:text-[#955844]">
                        Lupa password?
                    </a>
                </div>

                {{-- Tombol Submit --}}
                <button 
                    type="submit" 
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-full bg-[#B46C54] py-3.5 text-sm font-bold text-white shadow-lg shadow-[#B46C54]/20 transition hover:bg-[#955844]"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    Masuk
                </button>
            </form>

            {{-- Footer Keterangan Khusus Admin --}}
            <div class="mt-8 flex items-center justify-center gap-2 text-xs text-stone-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Akses khusus administrator Kost.in</span>
            </div>

        </div>

    </div>

</div>

{{-- Script Inisialisasi Peta Preview Leaflet --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Titik Kampus SV IPB Bogor
    const centerSV = [-6.5888, 106.8062];

    // Inisialisasi Peta (zoomControl & scrollWheelZoom ditiadakan agar pas untuk preview UI)
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

    // Custom Icon untuk Pin Utama (Gedung SV IPB)
    const mainIcon = L.divIcon({
        className: 'custom-pin-main',
        html: `<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });
    L.marker(centerSV, { icon: mainIcon }).addTo(map);

    // Dummy Pin Kos Sekitar (Simulasi sebaran kos di peta)
    const kosLocations = [
        [-6.5865, 106.8035],
        [-6.5910, 106.8090],
        [-6.5840, 106.8080],
        [-6.5925, 106.8020],
        [-6.5895, 106.8110]
    ];

    const smallPinIcon = L.divIcon({
        className: 'custom-pin',
        html: `<div class="custom-pin-inner"></div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });

    kosLocations.forEach(loc => {
        L.marker(loc, { icon: smallPinIcon }).addTo(map);
    });
});
</script>

</body>
</html>