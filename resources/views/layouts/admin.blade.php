<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Kost.in</title>

    {{-- Font: Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Alpine.js untuk interaksi buka-tutup notifikasi --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Memanggil Tailwind v4 via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#fbfbfb] font-sans antialiased">
<div class="flex h-screen overflow-hidden">

    {{-- ================= SIDEBAR ================= --}}
    <aside class="hidden lg:flex w-64 shrink-0 flex-col justify-between overflow-y-auto border-r border-stone-200 bg-white px-4 py-6">
        <div>
            {{-- Logo PNG --}}
            <a href="{{ route('admin.dasbor') }}" class="flex items-center gap-3 px-2">
                <img src="{{ asset('images/logo kost.in.png') }}" alt="Kost.in Logo" class="h-8 w-auto object-contain">
                <span class="text-xl font-extrabold text-brand">Kost<span class="text-ink">.in</span></span>
            </a>

            {{-- Menu --}}
            <p class="mt-10 mb-3 px-3 text-xs font-semibold tracking-wide text-muted">MENU UTAMA</p>
            <nav class="space-y-1">
                {{-- Menu 1: Dashboard --}}
                <a href="{{ route('admin.dasbor') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.dasbor') ? 'bg-brand-soft font-semibold text-brand-dark' : 'font-medium text-slate-600 hover:bg-stone-100' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/></svg>
                        Dashboard
                    </span>
                    @if (request()->routeIs('admin.dasbor'))
                        <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                    @endif
                </a>

                {{-- Menu 2: Data Kos --}}
                <a href="{{ route('admin.datakos') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.datakos*') ? 'bg-brand-soft font-semibold text-brand-dark' : 'font-medium text-slate-600 hover:bg-stone-100' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path stroke-linecap="round" d="M9 8h2m2 0h2M9 12h2m2 0h2M10 21v-4h4v4"/></svg>
                        Data Kos
                    </span>
                    @if (request()->routeIs('admin.datakos*'))
                        <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                    @endif
                </a>

                {{-- Menu 3: Fasilitas --}}
                <a href="{{ route('admin.fasilitas') }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('admin.fasilitas*') ? 'bg-brand-soft font-semibold text-brand-dark' : 'font-medium text-slate-600 hover:bg-stone-100' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/></svg>
                        Fasilitas
                    </span>
                    @if (request()->routeIs('admin.fasilitas*'))
                        <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                    @endif
                </a>
            </nav>
        </div>

        {{-- Profil & keluar --}}
        <div class="mt-8 rounded-2xl bg-page p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand text-sm font-bold text-white">{{ strtoupper(mb_substr(auth()->user()?->name ?? 'AD', 0, 2)) }}</span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()?->name ?? 'Administrator' }}</p>
                    <p class="truncate text-xs text-muted">{{ auth()->user()?->email }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-[#B46C54]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h3a2 2 0 012 2v14a2 2 0 01-2 2h-3M10 17l-5-5 5-5M5 12h11"/></svg>
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- ================= KONTEN ================= --}}
    {{-- PERBAIKAN: overflow-y-auto diubah menjadi overflow-y-scroll di sini --}}
    <div class="flex flex-1 flex-col overflow-y-scroll bg-page relative">

        {{-- Header --}}
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-stone-200 bg-white/80 px-6 py-4 backdrop-blur-md lg:px-8">
            <div>
                <h1 class="text-xl font-bold">@yield('page_title', 'Dashboard')</h1>
                <p class="mt-0.5 text-xs text-muted">@yield('breadcrumb', 'Admin / Dashboard')</p>
            </div>
            
            <div class="flex items-center gap-4">
                
                {{-- WRAPPER NOTIFIKASI DENGAN ALPINE.JS --}}
                <div x-data="{ notifOpen: false, unreadCount: {{ $jumlahNotif ?? 0 }} }" class="relative">
                    
                    {{-- Lonceng Notifikasi --}}
                    <button 
                        @click="notifOpen = !notifOpen" 
                        :class="notifOpen ? 'bg-stone-100 text-slate-700' : 'bg-transparent text-slate-400'"
                        type="button" 
                        class="relative flex h-10 w-10 items-center justify-center rounded-xl transition-colors hover:bg-stone-100 hover:text-slate-600" 
                        aria-label="Notifikasi"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                        
                        {{-- Angka notifikasi merah --}}
                        <span 
                            x-show="unreadCount > 0" 
                            x-cloak
                            x-text="unreadCount"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-75"
                            class="absolute right-1 top-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#FA3C4C] text-[10px] font-bold text-white shadow-sm ring-2 ring-white"
                        ></span>
                    </button>

                    {{-- Dropdown Panel Notifikasi --}}
                    <div 
                        x-show="notifOpen" 
                        x-cloak
                        @click.outside="notifOpen = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                        style="display: none;"
                        class="absolute right-0 mt-3 w-[320px] origin-top-right overflow-hidden rounded-2xl bg-white shadow-[0_8px_30px_rgb(0,0,0,0.12)] ring-1 ring-stone-200"
                    >
                        {{-- Header Panel Dropdown --}}
                        <div class="flex items-start justify-between border-b border-stone-100 px-4 py-3">
                            <div>
                                <h3 class="text-sm font-bold text-ink">Notifikasi</h3>
                                <p class="text-[11px] text-muted">Pembaruan data KosVokasi</p>
                            </div>
                            <button @click="notifOpen = false" class="text-slate-400 hover:text-slate-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- STATE 1: Jika ada notifikasi. Fitur notifikasi masih di backlog,
                             jadi contoh item di bawah tidak dirender sampai $notifikasi tersedia. --}}
                        @if (! empty($notifikasi))
                        <div x-show="unreadCount > 0" class="max-h-[300px] overflow-y-auto overscroll-contain">
                            {{-- Item 1 --}}
                            <a href="#" class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-stone-50">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></span>
                                <div>
                                    <p class="text-[13px] font-bold leading-tight text-ink">Data Kos Putri Melati diperbarui</p>
                                    <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Informasi harga dan periode sewa telah diubah.</p>
                                    <p class="mt-1 text-[11px] font-semibold text-brand">2 menit lalu</p>
                                </div>
                            </a>
                            <div class="mx-4 h-px bg-stone-100"></div>

                            {{-- Item 2 --}}
                            <a href="#" class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-stone-50">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path stroke-linecap="round" d="M9 8h2m2 0h2M9 12h2m2 0h2M10 21v-4h4v4"/></svg></span>
                                <div>
                                    <p class="text-[13px] font-bold leading-tight text-ink">Kos baru berhasil ditambahkan</p>
                                    <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Kos Green Residence sekarang tersedia.</p>
                                    <p class="mt-1 text-[11px] font-semibold text-brand">18 menit lalu</p>
                                </div>
                            </a>
                            <div class="mx-4 h-px bg-stone-100"></div>

                            {{-- Item 3 --}}
                            <a href="#" class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-stone-50">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/></svg></span>
                                <div>
                                    <p class="text-[13px] font-bold leading-tight text-ink">Fasilitas baru ditambahkan</p>
                                    <p class="mt-0.5 text-[11px] leading-snug text-slate-500">Fasilitas CCTV telah tersedia.</p>
                                    <p class="mt-1 text-[11px] font-semibold text-brand">1 jam lalu</p>
                                </div>
                            </a>
                            <div class="mx-4 h-px bg-stone-100"></div>

                            {{-- Item 4 --}}
                            <a href="#" class="flex items-start gap-3 px-4 py-3 transition-colors hover:bg-stone-50">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
                                <div>
                                    <p class="text-[13px] font-bold leading-tight text-ink">Verifikasi data selesai</p>
                                    <p class="mt-0.5 text-[11px] leading-snug text-slate-500">12 data kos telah diperiksa hari ini.</p>
                                    <p class="mt-1 text-[11px] font-semibold text-brand">3 jam lalu</p>
                                </div>
                            </a>
                        </div>

                        @endif

                        {{-- STATE 2: Jika kosong/semua sudah dibaca (Muncul saat unreadCount == 0) --}}
                        <div x-show="unreadCount === 0" x-cloak style="display: none;" class="flex flex-col items-center justify-center px-4 py-10">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-stone-100 text-slate-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <p class="mt-3 text-[13px] font-semibold text-slate-500">Belum ada notifikasi</p>
                            <p class="text-[11px] text-slate-400">Pembaruan data kos akan muncul di sini.</p>
                        </div>

                        {{-- Footer Dropdown (Tombol eksekusi yang mengubah nilai unreadCount jadi 0) --}}
                        <div x-show="unreadCount > 0" class="border-t border-stone-100 bg-stone-50/50 py-3 text-center">
                            <button 
                                @click="unreadCount = 0" 
                                class="text-[13px] font-bold text-brand hover:text-brand-dark transition-colors"
                            >
                                Tandai semua sudah dibaca
                            </button>
                        </div>
                    </div>
                </div>
                {{-- END WRAPPER NOTIFIKASI --}}

                <span class="hidden h-8 w-px bg-stone-200 sm:block"></span>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-soft text-xs font-bold text-brand-dark">{{ strtoupper(mb_substr(auth()->user()?->name ?? 'AD', 0, 2)) }}</span>
                    <div class="hidden leading-tight sm:block">
                        <p class="text-sm font-semibold">{{ auth()->user()?->name ?? 'Admin' }}</p>
                        <p class="text-xs text-muted">Administrator</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 space-y-6 p-6 lg:p-8">
            {{-- Tempat menyuntikkan isi konten dari view lain --}}
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>