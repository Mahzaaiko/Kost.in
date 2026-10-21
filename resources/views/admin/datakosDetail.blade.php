@extends('layouts.admin')

@section('title', 'Detail Data Kos')
@section('page_title', 'Detail Data Kos')
@section('breadcrumb', 'Admin / Detail Data Kos')

@section('content')
{{-- Wrapper Utama Alpine.js untuk Modal Hapus --}}
<div x-data="{ isDeleteModalOpen: false }">

    {{-- ================= HEADER ================= --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <a href="{{ route('admin.datakos') }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition-colors hover:text-brand">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Kembali ke Data Kos
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-bold text-ink">Detail Data Kos</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-bold text-green-700 ring-1 ring-inset ring-green-600/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>
                    Aktif
                </span>
            </div>
            <p class="mt-1.5 text-sm text-slate-500">Pratinjau informasi yang tampil pada website.</p>
        </div>

        <div class="flex w-full items-center gap-3 sm:w-auto">
            {{-- Tombol Hapus (Trigger Modal) --}}
            <button 
                @click="isDeleteModalOpen = true"
                type="button" 
                class="flex w-full items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 sm:w-auto"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Hapus Data
            </button>
            
            {{-- Tombol Edit --}}
            <a 
                href="{{ route('admin.datakos.edit', 1) }}" 
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#955844] sm:w-auto"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Data
            </a>
        </div>
    </div>

    {{-- ================= KONTEN UTAMA ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        
        {{-- KOLOM KIRI (Foto, Harga, Deskripsi) - Proporsi 60% --}}
        <div class="space-y-6 lg:col-span-3">
            
            {{-- Card Foto & Info Utama --}}
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                
                {{-- Alpine.js Slider Foto --}}
                <div 
                    x-data="{ 
                        activeSlide: 1, 
                        slides: [
                            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?q=80&w=1200&auto=format&fit=crop',
                            'https://images.unsplash.com/photo-1502672260266-1c1de2d9d00c?q=80&w=1200&auto=format&fit=crop',
                            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?q=80&w=1200&auto=format&fit=crop',
                            'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=1200&auto=format&fit=crop'
                        ] 
                    }" 
                    class="relative aspect-[4/3] w-full bg-stone-100 group"
                >
                    {{-- Gambar --}}
                    <template x-for="(slide, index) in slides" :key="index">
                        <img 
                            x-show="activeSlide === index + 1"
                            :src="slide" 
                            x-transition:enter="transition duration-300"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            class="absolute inset-0 h-full w-full object-cover"
                            alt="Foto Kos"
                        >
                    </template>

                    {{-- Tombol Kiri --}}
                    <button 
                        @click="activeSlide = activeSlide > 1 ? activeSlide - 1 : slides.length" 
                        class="absolute left-4 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/30 text-white backdrop-blur-md transition hover:bg-white hover:text-slate-800 opacity-0 group-hover:opacity-100"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    {{-- Tombol Kanan --}}
                    <button 
                        @click="activeSlide = activeSlide < slides.length ? activeSlide + 1 : 1" 
                        class="absolute right-4 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/30 text-white backdrop-blur-md transition hover:bg-white hover:text-slate-800 opacity-0 group-hover:opacity-100"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    {{-- Counter Badge --}}
                    <div class="absolute bottom-4 right-4 flex items-center gap-2 rounded-lg bg-black/60 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-md">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="activeSlide"></span> dari <span x-text="slides.length"></span> foto
                    </div>
                </div>

                {{-- Detail Teks Utama --}}
                <div class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-extrabold text-ink">Kos Putri Melati</h1>
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                                <svg class="h-4 w-4 text-brand" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Jl. Kemuning No. 12, Dramaga, Bogor
                            </p>
                        </div>
                        <span class="inline-flex rounded-lg bg-brand-soft px-3 py-1.5 text-xs font-bold text-brand-dark">Kos Putri</span>
                    </div>

                    <hr class="my-6 border-stone-100">

                    <div>
                        <p class="text-xs font-semibold text-slate-500">Harga per bulan</p>
                        <p class="mt-1 text-3xl font-extrabold text-[#c57b5f]">Rp700.000</p>
                    </div>
                </div>
            </div>

            {{-- Card Deskripsi --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-6 sm:p-8 shadow-sm">
                <h3 class="text-base font-bold text-ink">Deskripsi</h3>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    Kos putri yang nyaman dan aman di lingkungan asri dekat kampus. Dilengkapi dengan keamanan 24 jam dan fasilitas lengkap untuk mendukung aktivitas belajar. Jarak ke kampus sangat dekat sehingga bisa ditempuh dengan berjalan kaki. Di sekitar kos juga terdapat banyak warung makan, minimarket, dan fasilitas umum lainnya.
                </p>
            </div>
        </div>

        {{-- KOLOM KANAN (Fasilitas & Lokasi) - Proporsi 40% --}}
        <div class="space-y-6 lg:col-span-2">
            
            {{-- Fasilitas (Dengan Logo Khusus) --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <h3 class="mb-5 text-base font-bold text-ink">Fasilitas</h3>
                <div class="flex flex-col gap-3">
                    @php
                        // Array fasilitas dilengkapi dengan tag <path> icon masing-masing
                        $fasilitas_kos = [
                            ['nama' => 'Wi-Fi', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>'],
                            ['nama' => 'AC', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8h18M5 8v-2a2 2 0 012-2h10a2 2 0 012 2v2M8 12v3M12 12v3M16 12v3"/>'],
                            ['nama' => 'Kamar Mandi Dalam', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 12v7a2 2 0 002 2h12a2 2 0 002-2v-7M3 12h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/>'],
                            ['nama' => 'Parkir Motor', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 8h2.5a2.5 2.5 0 010 5H10V8zm0 8v-3m11-1a9 9 0 11-18 0 9 9 0 0118 0z"/>']
                        ];
                    @endphp
                    
                    @foreach($fasilitas_kos as $fas)
                    <div class="flex items-center gap-4 rounded-xl bg-white px-1 py-1 transition">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#c57b5f]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $fas['icon'] !!}</svg>
                        </span>
                        <span class="text-sm font-bold text-slate-700">{{ $fas['nama'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Lokasi & Map --}}
            <div class="rounded-2xl border border-stone-200 bg-white shadow-sm overflow-hidden sticky top-24">
                <div class="p-6 pb-4">
                    <h3 class="text-base font-bold text-ink">Lokasi Kos</h3>
                    <p class="text-xs text-slate-500 mt-1">1.2 km dari kampus</p>
                </div>
                
                {{-- Map Visual --}}
                <div class="relative h-56 w-full bg-[#EFE4DF]">
                    <span class="absolute left-1/2 top-1/2 h-[120%] w-[120%] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-[#c57b5f]/40"></span>
                    <span class="absolute left-1/2 top-1/2 h-[80%] w-[80%] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-[#c57b5f]/50"></span>
                    
                    <div class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink text-white shadow-lg"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-5 9 5-9 5-9-5zm4 3v4c0 1 2.2 2 5 2s5-1 5-2v-4"/></svg></span>
                        <span class="mt-1 whitespace-nowrap rounded bg-ink px-1.5 py-0.5 text-[9px] font-bold text-white">Kampus SV IPB</span>
                    </div>

                    <div class="absolute left-[35%] top-[30%] flex -translate-x-1/2 -translate-y-full flex-col items-center">
                        <svg class="h-10 w-10 text-[#c57b5f] drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    </div>
                </div>

                <div class="flex border-t border-stone-200">
                    <div class="flex-1 border-r border-stone-200 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Latitude</p>
                        <p class="mt-0.5 font-mono text-sm font-bold text-ink">-6.5590</p>
                    </div>
                    <div class="flex-1 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Longitude</p>
                        <p class="mt-0.5 font-mono text-sm font-bold text-ink">106.7240</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL HAPUS DATA ================= --}}
    <div 
        x-show="isDeleteModalOpen" 
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center"
    >
        {{-- Overlay Background Blur --}}
        <div 
            x-show="isDeleteModalOpen"
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm"
            @click="isDeleteModalOpen = false"
        ></div>

        {{-- Modal Content --}}
        <div 
            x-show="isDeleteModalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative mx-4 w-full max-w-lg overflow-hidden rounded-2xl bg-white text-left shadow-2xl"
        >
            <div class="border-b border-stone-100 px-6 py-5">
                <h3 class="text-lg font-bold text-ink">Hapus Data Kos?</h3>
            </div>

            <div class="px-6 py-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-ink">Kos Putri Melati</h4>
                        <p class="mt-1 text-sm text-slate-500">Apakah kamu yakin ingin menghapus data ini? Data kos tidak akan ditampilkan kembali pada website.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                <button 
                    @click="isDeleteModalOpen = false" 
                    type="button" 
                    class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800"
                >
                    Batal
                </button>
                <form method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-700 sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection