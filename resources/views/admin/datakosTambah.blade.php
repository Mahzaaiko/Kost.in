@extends('layouts.admin')

@section('title', 'Tambah Data Kos')
@section('page_title', 'Tambah Data Kos')
@section('breadcrumb', 'Admin / Tambah Data Kos')

@section('content')
<form action="#" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- ================= HEADER & TOMBOL AKSI ================= --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 border-b border-stone-200 pb-5 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-bold text-ink">Tambah Data Kos</h2>
            <p class="mt-1 text-sm text-slate-500">Lengkapi informasi kos baru yang akan ditampilkan di Kost.in</p>
        </div>
        <div class="flex w-full items-center gap-3 sm:w-auto">
            <a href="{{ route('admin.datakos') }}" class="flex w-full items-center justify-center rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-stone-50 hover:text-slate-800 sm:w-auto">
                Batal
            </a>
            {{-- Tombol dengan warna B46C54 --}}
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#955844] sm:w-auto shadow-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Simpan Data Kos
            </button>
        </div>
    </div>

    {{-- ================= FORM GRID ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        {{-- KOLOM KIRI (Informasi, Fasilitas, Foto) --}}
        <div class="space-y-6 lg:col-span-2">
            
            {{-- 1. INFORMASI KOS --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">01</span>
                    <div>
                        <h3 class="text-base font-bold text-ink">Informasi Kos</h3>
                        <p class="text-xs text-slate-500">Informasi utama yang akan dilihat mahasiswa.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    {{-- Nama Kos --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Kos <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_kos" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="Contoh: Kos Putri Melati">
                    </div>
                    
                    {{-- No Telepon --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">No. Telepon <span class="text-red-500">*</span></label>
                        <input type="text" name="no_telepon" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="Contoh: 0812 3456 7890">
                    </div>

                    {{-- Alamat --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Alamat <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" id="alamat" name="alamat" required class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-4 pr-32 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="Masukkan alamat lengkap">
                            <button type="button" id="btn-cari-peta" class="absolute right-1.5 top-1.5 flex items-center gap-1.5 rounded-lg bg-[#fbf5f3] px-3 py-1.5 text-[11px] font-bold text-[#B46C54] transition hover:bg-[#B46C54] hover:text-white">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                Cari di Peta
                            </button>
                        </div>
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">Tulis alamat selengkapnya untuk ditampilkan ke mahasiswa. Klik "Cari di Peta" untuk perkiraan lokasi, lalu geser pin ke titik kos yang tepat.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        {{-- Custom Dropdown Tipe Kos --}}
                        <div x-data="{ openTipe: false, selectedTipe: '' }">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tipe Kos <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <button 
                                    @click="openTipe = !openTipe" 
                                    @click.outside="openTipe = false"
                                    type="button" 
                                    class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]"
                                >
                                    <span x-text="selectedTipe === '' ? 'Pilih tipe kos' : selectedTipe" :class="selectedTipe === '' ? 'text-slate-400' : 'text-ink'"></span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openTipe ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                
                                <div 
                                    x-show="openTipe" 
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    style="display: none;"
                                    class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg"
                                >
                                    <ul class="flex flex-col py-1">
                                        <li @click="selectedTipe = 'Putri'; openTipe = false" :class="selectedTipe === 'Putri' ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Putri</li>
                                        <li @click="selectedTipe = 'Putra'; openTipe = false" :class="selectedTipe === 'Putra' ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Putra</li>
                                        <li @click="selectedTipe = 'Campur'; openTipe = false" :class="selectedTipe === 'Campur' ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Campur</li>
                                    </ul>
                                </div>
                            </div>
                            <input type="hidden" name="tipe_kos" :value="selectedTipe" required>
                        </div>

                        {{-- Custom Dropdown Periode Minimal Sewa --}}
                        <div x-data="{ openPeriode: false, selectedPeriode: '1 Bulan' }">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Periode Minimal Sewa <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <button 
                                    @click="openPeriode = !openPeriode" 
                                    @click.outside="openPeriode = false"
                                    type="button" 
                                    class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]"
                                >
                                    <span x-text="selectedPeriode"></span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openPeriode ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                
                                <div 
                                    x-show="openPeriode" 
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    style="display: none;"
                                    class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg"
                                >
                                    <ul class="flex flex-col py-1">
                                        <li @click="selectedPeriode = '1 Bulan'; openPeriode = false" :class="selectedPeriode === '1 Bulan' ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">1 Bulan</li>
                                        <li @click="selectedPeriode = '3 Bulan'; openPeriode = false" :class="selectedPeriode === '3 Bulan' ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">3 Bulan</li>
                                        <li @click="selectedPeriode = '6 Bulan'; openPeriode = false" :class="selectedPeriode === '6 Bulan' ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">6 Bulan</li>
                                        <li @click="selectedPeriode = 'Tahunan'; openPeriode = false" :class="selectedPeriode === 'Tahunan' ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Tahunan</li>
                                    </ul>
                                </div>
                            </div>
                            <input type="hidden" name="periode_sewa" :value="selectedPeriode" required>
                        </div>
                    </div>

                    {{-- Harga Sewa --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Harga Sewa <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-medium text-slate-500">Rp</span>
                            <input type="number" name="harga" required class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-11 pr-4 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="0">
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-500">Masukkan total harga sesuai periode minimal sewa yang dipilih.</p>
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi <span class="text-red-500">*</span></label>
                        <textarea name="deskripsi" required rows="4" class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="Jelaskan suasana, akses, dan keunggulan kos..."></textarea>
                    </div>
                </div>
            </div>

            {{-- 2. FASILITAS --}}
            <div x-data="{ selectedFasilitas: ['Wi-Fi'] }" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">02</span>
                    <div>
                        <h3 class="text-base font-bold text-ink">Fasilitas</h3>
                        <p class="text-xs text-slate-500">Pilih fasilitas yang tersedia di kos.</p>
                    </div>
                </div>

                @php
                    $fasilitas_list = ['Wi-Fi', 'AC', 'Kamar Mandi Dalam', 'Parkir', 'Dapur', 'Laundry', 'Keamanan 24 Jam'];
                @endphp

                <div class="flex flex-wrap gap-3">
                    @foreach ($fasilitas_list as $fas)
                        <label 
                            class="flex cursor-pointer items-center gap-2.5 rounded-xl border py-2 pl-3 pr-4 text-sm font-medium transition-colors"
                            :class="selectedFasilitas.includes('{{ $fas }}') ? 'border-[#B46C54] bg-[#fbf5f3] text-[#B46C54]' : 'border-stone-200 bg-white text-slate-600 hover:bg-stone-50'"
                        >
                            <input type="checkbox" value="{{ $fas }}" x-model="selectedFasilitas" class="hidden">
                            
                            <div class="flex h-4 w-4 items-center justify-center rounded-[4px] border transition-colors"
                                 :class="selectedFasilitas.includes('{{ $fas }}') ? 'border-[#B46C54] bg-[#B46C54] text-white' : 'border-stone-300 bg-white'">
                                <svg x-show="selectedFasilitas.includes('{{ $fas }}')" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            {{ $fas }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 3. FOTO KOS (ALGORITHM MULTI-UPLOAD + FOTO UTAMA + VALIDASI 3MB) --}}
            <div 
                x-data="{ 
                    photos: [], 
                    mainPhotoIndex: 0,
                    handleFiles(event) {
                        const newFiles = Array.from(event.target.files);
                        const maxSize = 3 * 1024 * 1024; // 3 MB dalam satuan Bytes
                        
                        newFiles.forEach(file => {
                            // Cek ukuran file
                            if (file.size > maxSize) {
                                alert(`Ups! Ukuran foto '${file.name}' terlalu besar. Maksimal 3 MB ya.`);
                                return; // Lewati file ini
                            }
                            
                            this.photos.push({
                                url: URL.createObjectURL(file),
                                name: file.name
                            });
                        });
                        // Kosongkan input agar bisa mengupload file yang sama jika dihapus
                        event.target.value = '';
                    },
                    removePhoto(index) {
                        this.photos.splice(index, 1);
                        
                        // Menyesuaikan logika indeks jika Foto Utama dihapus
                        if (this.mainPhotoIndex === index) {
                            this.mainPhotoIndex = 0;
                        } else if (this.mainPhotoIndex > index) {
                            this.mainPhotoIndex--;
                        }
                    }
                }" 
                class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm"
            >
                <div class="mb-6 flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">03</span>
                    <div>
                        <h3 class="text-base font-bold text-ink">Foto Kos</h3>
                        <p class="text-xs text-slate-500">Pilih beberapa foto, lalu tentukan satu sebagai Foto Utama (cuplikan).</p>
                    </div>
                </div>

                {{-- Input tersembunyi yang merekam index foto utama untuk Backend --}}
                <input type="hidden" name="main_photo_index" :value="mainPhotoIndex">
                
                {{-- Input File Asli (Multiple) --}}
                <input type="file" name="fotos[]" multiple accept="image/png, image/jpeg" class="hidden" x-ref="fileInput" @change="handleFiles">

                {{-- Dropzone Area --}}
                <div 
                    @click="$refs.fileInput.click()"
                    class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-stone-200 bg-stone-50 py-10 transition hover:border-[#fbf5f3] hover:bg-stone-100"
                >
                    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#fbf5f3] text-[#B46C54]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </span>
                    <span class="text-sm font-bold text-ink">Upload Foto Kos</span>
                    <span class="mt-1 text-xs text-slate-500">PNG atau JPG, maksimal 3 MB per foto</span>
                </div>

                {{-- Gallery Preview & Pemilihan Foto Utama --}}
                <div x-show="photos.length > 0" x-cloak class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <template x-for="(photo, index) in photos" :key="index">
                        
                        <div 
                            class="group relative aspect-[4/3] overflow-hidden rounded-xl border bg-stone-100 transition-all"
                            :class="mainPhotoIndex === index ? 'border-[#B46C54] ring-2 ring-[#B46C54] ring-offset-1' : 'border-stone-200'"
                        >
                            {{-- Gambar Preview --}}
                            <img :src="photo.url" class="h-full w-full object-cover">
                            
                            {{-- Shadow Gradien agar tombol lebih terbaca --}}
                            <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100" :class="mainPhotoIndex === index ? 'opacity-100' : ''"></div>

                            {{-- BADGE: Jika ini adalah Foto Utama --}}
                            <div x-show="mainPhotoIndex === index" class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-[#B46C54] px-2 py-1 text-[10px] font-bold text-white shadow-sm">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                Foto Utama
                            </div>

                            {{-- TOMBOL: Jadikan Foto Utama (Muncul saat di-hover) --}}
                            <button 
                                x-show="mainPhotoIndex !== index" 
                                @click.prevent="mainPhotoIndex = index" 
                                class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-white/95 px-2 py-1 text-[10px] font-bold text-slate-700 opacity-0 shadow-sm backdrop-blur-sm transition-all hover:text-[#B46C54] group-hover:opacity-100"
                            >
                                Jadikan Utama
                            </button>

                            {{-- TOMBOL: Hapus Foto --}}
                            <button 
                                @click.prevent="removePhoto(index)" 
                                class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-white/95 text-slate-500 opacity-0 shadow-sm backdrop-blur-sm transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                    </template>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN (Lokasi Map) --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sticky top-24">
                <div class="mb-6 flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">04</span>
                    <div>
                        <h3 class="text-base font-bold text-ink">Lokasi</h3>
                        <p class="text-xs text-slate-500">Tentukan titik kos pada peta.</p>
                    </div>
                </div>

                {{-- Peta Asli (Leaflet + OpenStreetMap) --}}
                <button type="button" id="btn-lokasi-saya" class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl border border-[#B46C54] bg-white px-3 py-2 text-xs font-bold text-[#B46C54] transition hover:bg-[#B46C54] hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8a4 4 0 100 8 4 4 0 000-8zm0-6v3m0 14v3M2 12h3m14 0h3"/></svg>
                    <span id="btn-lokasi-saya-teks">Gunakan lokasi saya</span>
                </button>

                <div class="relative z-0 mb-3 overflow-hidden rounded-xl border border-stone-200">
                    <div id="map-kos" class="h-64 w-full"></div>
                </div>
                <p id="map-status" class="mb-4 hidden text-[11px] font-semibold leading-relaxed"></p>

                <div class="mb-4">
                    <label class="mb-1.5 block text-xs font-semibold text-slate-700">Tempel koordinat dari Google Maps</label>
                    <div class="flex gap-2">
                        <input type="text" id="paste-koordinat" class="block w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="-6.5975, 106.7995">
                        <button type="button" id="btn-pakai-koordinat" class="shrink-0 rounded-xl bg-[#fbf5f3] px-3 text-xs font-bold text-[#B46C54] transition hover:bg-[#B46C54] hover:text-white">Pakai</button>
                    </div>
                    <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">Klik kanan titik kos di Google Maps, klik angka koordinat untuk menyalin, lalu tempel di sini.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Latitude</label>
                        <input type="text" id="latitude" name="latitude" required class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-slate-500 focus:outline-none" readonly>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Longitude</label>
                        <input type="text" id="longitude" name="longitude" required class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-slate-500 focus:outline-none" readonly>
                    </div>
                </div>

                <div class="mt-5 flex gap-3 rounded-xl border border-[#fbf5f3] bg-[#fbf5f3]/50 p-4">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[11px] leading-relaxed text-[#B46C54]">Isi kolom Alamat lalu klik "Cari di Peta". Geser pin atau klik peta jika lokasi belum tepat.</p>
                </div>
            </div>
        </div>

    </div>
</form>

{{-- ================= LEAFLET + OPENSTREETMAP ================= --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Titik awal: sekitar Kota Bogor (perkiraan, bukan koordinat kos tertentu)
    const titikAwal = [-6.5975, 106.7995];

    const layerPeta = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    });
    const layerSatelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        maxNativeZoom: 18,
        attribution: 'Tiles &copy; Esri'
    });

    const mapKos = L.map('map-kos', { layers: [layerPeta] }).setView(titikAwal, 14);
    L.control.layers({ 'Peta': layerPeta, 'Satelit': layerSatelit }, null, { position: 'topright' }).addTo(mapKos);

    const markerKos = L.marker(titikAwal, { draggable: true }).addTo(mapKos);

    const inputLat = document.getElementById('latitude');
    const inputLng = document.getElementById('longitude');
    const statusEl = document.getElementById('map-status');

    function updateKoordinat(latlng) {
        inputLat.value = latlng.lat.toFixed(7);
        inputLng.value = latlng.lng.toFixed(7);
    }
    function showStatus(msg, tone) {
        statusEl.textContent = msg || '';
        statusEl.classList.toggle('hidden', !msg);
        statusEl.classList.toggle('text-red-500', tone === 'error');
        statusEl.classList.toggle('text-[#B46C54]', tone !== 'error');
    }
    function setPosisi(latlng, zoom) {
        markerKos.setLatLng(latlng);
        mapKos.setView(latlng, zoom || Math.max(mapKos.getZoom(), 17));
        updateKoordinat(latlng);
    }

    updateKoordinat(markerKos.getLatLng());
    markerKos.on('dragend', () => updateKoordinat(markerKos.getLatLng()));
    mapKos.on('click', e => { markerKos.setLatLng(e.latlng); updateKoordinat(e.latlng); });

    // ---------- 1. Gunakan lokasi saya (GPS) ----------
    const btnLokasi = document.getElementById('btn-lokasi-saya');
    const btnLokasiTeks = document.getElementById('btn-lokasi-saya-teks');
    btnLokasi.addEventListener('click', function () {
        if (!navigator.geolocation) { showStatus('Browser tidak mendukung deteksi lokasi.', 'error'); return; }
        showStatus('');
        btnLokasi.disabled = true;
        btnLokasiTeks.textContent = 'Mendeteksi lokasi...';
        navigator.geolocation.getCurrentPosition(
            pos => {
                setPosisi(L.latLng(pos.coords.latitude, pos.coords.longitude), 18);
                showStatus('Lokasi perangkat terdeteksi. Geser pin jika masih kurang tepat.', 'info');
                btnLokasi.disabled = false; btnLokasiTeks.textContent = 'Gunakan lokasi saya';
            },
            () => {
                showStatus('Lokasi tidak bisa diambil. Pastikan izin lokasi diaktifkan (butuh HTTPS atau localhost).', 'error');
                btnLokasi.disabled = false; btnLokasiTeks.textContent = 'Gunakan lokasi saya';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

    // ---------- 2. Tempel koordinat / link dari Google Maps ----------
    function parseKoordinat(teks) {
        const pola = [
            /@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/,
            /!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/,
            /(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)/
        ];
        for (const p of pola) {
            const m = teks.match(p);
            if (m) {
                const lat = parseFloat(m[1]), lng = parseFloat(m[2]);
                if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) return L.latLng(lat, lng);
            }
        }
        return null;
    }
    const inputPaste = document.getElementById('paste-koordinat');
    function pakaiKoordinat() {
        const ll = parseKoordinat(inputPaste.value.trim());
        if (!ll) { showStatus('Format koordinat tidak dikenali. Contoh: -6.5975, 106.7995', 'error'); return; }
        setPosisi(ll, 18);
        showStatus('Pin dipindahkan ke koordinat yang ditempel.', 'info');
    }
    document.getElementById('btn-pakai-koordinat').addEventListener('click', pakaiKoordinat);
    inputPaste.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); pakaiKoordinat(); } });
    inputPaste.addEventListener('paste', () => setTimeout(pakaiKoordinat, 0));

    // ---------- 3. Cari alamat (Nominatim) dengan fallback ----------
    const inputAlamat = document.getElementById('alamat');
    const btnCari = document.getElementById('btn-cari-peta');
    const tunggu = ms => new Promise(r => setTimeout(r, ms));

    function buatKandidat(alamat) {
        let s = alamat
            .replace(/\bRT\.?\s*\d+/gi, '')
            .replace(/\bRW\.?\s*\d+/gi, '')
            .replace(/\bNo\.?\s*[\w\-\/]+/gi, '')
            .replace(/\bBlok\.?\s*[\w\-]+/gi, '')
            .replace(/\b(komplek|komp|perumahan|perum)\.?\s*[^,]*/gi, '')
            .replace(/\b\d{5}\b/g, '');
        const bagian = s.split(',')
            .map(p => p.replace(/[\/\s]+$/g, '').replace(/^[\/\s]+/g, '').trim())
            .filter(Boolean);
        const daftar = [alamat.trim(), bagian.join(', ')];
        for (let i = 1; i < bagian.length - 1; i++) daftar.push(bagian.slice(i).join(', '));
        return [...new Set(daftar.filter(Boolean))].slice(0, 5);
    }

    async function nominatim(q) {
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&accept-language=id'
            + '&viewbox=106.70,-6.50,106.90,-6.70&q=' + encodeURIComponent(q);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    async function cariAlamat() {
        const alamat = inputAlamat.value.trim();
        if (!alamat) { showStatus('Isi alamat terlebih dahulu.', 'error'); return; }
        showStatus('');
        btnCari.disabled = true;
        try {
            const kandidat = buatKandidat(alamat);
            for (let i = 0; i < kandidat.length; i++) {
                if (i > 0) await tunggu(1100); // batas Nominatim: 1 request/detik
                const data = await nominatim(kandidat[i]);
                if (data.length) {
                    setPosisi(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), i === 0 ? 17 : 16);
                    showStatus(i === 0
                        ? 'Lokasi ditemukan. Zoom lalu geser pin ke titik kos yang tepat.'
                        : 'Alamat persis tidak ada di peta, ini perkiraan lokasi (' + kandidat[i] + '). Geser pin ke titik kos yang tepat.', 'info');
                    return;
                }
            }
            showStatus('Alamat tidak ditemukan. Coba tulis nama jalan + kelurahan + kota, atau klik langsung di peta.', 'error');
        } catch (err) {
            showStatus('Gagal mencari alamat. Cek koneksi internet, atau klik langsung di peta.', 'error');
        } finally {
            btnCari.disabled = false;
        }
    }
    btnCari.addEventListener('click', cariAlamat);
    inputAlamat.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); cariAlamat(); } });

    setTimeout(() => mapKos.invalidateSize(), 200);
});
</script>
@endsection