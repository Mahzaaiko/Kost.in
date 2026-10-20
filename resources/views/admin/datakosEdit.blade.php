@extends('layouts.admin')

@section('title', 'Edit Data Kos')
@section('page_title', 'Edit Data Kos')
@section('breadcrumb', 'Admin / Edit Data Kos')

@section('content')
<form action="#" method="POST" enctype="multipart/form-data">
    @csrf
    {{-- Method PUT standar Laravel untuk Update Data --}}
    @method('PUT')

    {{-- ================= HEADER & TOMBOL AKSI ================= --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 border-b border-stone-200 pb-5 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-bold text-ink">Edit Data Kos</h2>
            <p class="mt-1 text-sm text-slate-500">Perbarui informasi kos agar data tetap akurat.</p>
        </div>
        <div class="flex w-full items-center gap-3 sm:w-auto">
            <a href="{{ route('admin.datakos') }}" class="flex w-full items-center justify-center rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-stone-50 hover:text-slate-800 sm:w-auto">
                Batal
            </a>
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#c57b5f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a6644b] sm:w-auto shadow-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Simpan Perubahan
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
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-sm font-extrabold text-brand-dark">01</span>
                    <div>
                        <h3 class="text-base font-bold text-ink">Informasi Kos</h3>
                        <p class="text-xs text-slate-500">Informasi utama yang akan dilihat mahasiswa.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    {{-- Nama Kos (Prefilled) --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Kos <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_kos" value="Kos Putri Melati" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                    </div>
                    
                    {{-- No Telepon (Prefilled) --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">No. Telepon <span class="text-red-500">*</span></label>
                        <input type="text" name="no_telepon" value="0812 3456 7890" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                    </div>

                    {{-- Alamat (Prefilled) --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Alamat <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" name="alamat" value="Jl. Kemuning No. 12, Dramaga, Bogor" required class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-4 pr-32 text-sm text-ink transition placeholder:text-slate-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                            <button type="button" class="absolute right-1.5 top-1.5 flex items-center gap-1.5 rounded-lg bg-brand-soft px-3 py-1.5 text-[11px] font-bold text-brand-dark transition hover:bg-brand hover:text-white">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                Cari di Peta
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        {{-- Custom Dropdown Tipe Kos (Initial State: Putri) --}}
                        <div x-data="{ openTipe: false, selectedTipe: 'Putri' }">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tipe Kos <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <button 
                                    @click="openTipe = !openTipe" 
                                    @click.outside="openTipe = false"
                                    type="button" 
                                    class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                                >
                                    <span x-text="selectedTipe" class="text-ink"></span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openTipe ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                
                                <div 
                                    x-show="openTipe" 
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
                                        <li @click="selectedTipe = 'Putri'; openTipe = false" :class="selectedTipe === 'Putri' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Putri</li>
                                        <li @click="selectedTipe = 'Putra'; openTipe = false" :class="selectedTipe === 'Putra' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Putra</li>
                                        <li @click="selectedTipe = 'Campur'; openTipe = false" :class="selectedTipe === 'Campur' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Campur</li>
                                    </ul>
                                </div>
                            </div>
                            <input type="hidden" name="tipe_kos" :value="selectedTipe" required>
                        </div>

                        {{-- Custom Dropdown Periode (Initial State: 1 Bulan) --}}
                        <div x-data="{ openPeriode: false, selectedPeriode: '1 Bulan' }">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Periode Minimal Sewa <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <button 
                                    @click="openPeriode = !openPeriode" 
                                    @click.outside="openPeriode = false"
                                    type="button" 
                                    class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                                >
                                    <span x-text="selectedPeriode"></span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openPeriode ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                
                                <div 
                                    x-show="openPeriode" 
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
                                        <li @click="selectedPeriode = '1 Bulan'; openPeriode = false" :class="selectedPeriode === '1 Bulan' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">1 Bulan</li>
                                        <li @click="selectedPeriode = '3 Bulan'; openPeriode = false" :class="selectedPeriode === '3 Bulan' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">3 Bulan</li>
                                        <li @click="selectedPeriode = '6 Bulan'; openPeriode = false" :class="selectedPeriode === '6 Bulan' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">6 Bulan</li>
                                        <li @click="selectedPeriode = 'Tahunan'; openPeriode = false" :class="selectedPeriode === 'Tahunan' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">Tahunan</li>
                                    </ul>
                                </div>
                            </div>
                            <input type="hidden" name="periode_sewa" :value="selectedPeriode" required>
                        </div>
                    </div>

                    {{-- Harga Sewa (Prefilled) --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Harga Sewa — 1 bulan <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-medium text-slate-500">Rp</span>
                            <input type="number" name="harga" value="700000" required class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-12 pr-4 text-sm text-ink transition placeholder:text-slate-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-500">Masukkan total harga sesuai periode minimal sewa yang dipilih.</p>
                    </div>

                    {{-- Deskripsi (Prefilled Textarea) --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi <span class="text-red-500">*</span></label>
                        <textarea name="deskripsi" required rows="4" class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-ink transition placeholder:text-slate-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">Kos putri yang nyaman dan aman di lingkungan asri dekat kampus. Dilengkapi dengan keamanan 24 jam dan fasilitas lengkap untuk mendukung aktivitas belajar.</textarea>
                    </div>
                </div>
            </div>

            {{-- 2. FASILITAS (Tiga fasilitas sudah aktif otomatis via Alpine) --}}
            <div x-data="{ selectedFasilitas: ['Wi-Fi', 'AC', 'Kamar Mandi Dalam'] }" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-sm font-extrabold text-brand-dark">02</span>
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
                            :class="selectedFasilitas.includes('{{ $fas }}') ? 'border-[#c57b5f] bg-[#fbf5f3] text-[#c57b5f]' : 'border-stone-200 bg-white text-slate-600 hover:bg-stone-50'"
                        >
                            <input type="checkbox" value="{{ $fas }}" x-model="selectedFasilitas" class="hidden" name="fasilitas[]">
                            
                            {{-- Checkbox kustom --}}
                            <div class="flex h-4 w-4 items-center justify-center rounded-[4px] border"
                                 :class="selectedFasilitas.includes('{{ $fas }}') ? 'border-[#c57b5f] bg-[#c57b5f] text-white' : 'border-stone-300 bg-white'">
                                <svg x-show="selectedFasilitas.includes('{{ $fas }}')" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            {{ $fas }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 3. FOTO KOS (Desain Edit: Menampilkan Foto Tersimpan) --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-sm font-extrabold text-brand-dark">03</span>
                    <div>
                        <h3 class="text-base font-bold text-ink">Foto Kos</h3>
                        <p class="text-xs text-slate-500">Gunakan foto terang dengan kualitas terbaik.</p>
                    </div>
                </div>

                {{-- Galeri Foto Tersimpan --}}
                <div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    {{-- Foto 1 (Utama) --}}
                    <div class="group relative aspect-[4/3] w-full overflow-hidden rounded-xl border border-stone-200 sm:col-span-2">
                        <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?q=80&w=800&auto=format&fit=crop" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" alt="Kamar">
                        <span class="absolute left-3 top-3 rounded-md bg-white px-2.5 py-1 text-[10px] font-bold text-slate-700 shadow-sm">Foto utama</span>
                        
                        {{-- Overlay Hapus --}}
                        <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                            <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition hover:bg-red-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                    
                    {{-- Placeholder untuk foto lain --}}
                    <div class="flex aspect-[4/3] w-full flex-col items-center justify-center rounded-xl border border-stone-200 bg-stone-50">
                        <svg class="h-8 w-8 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    </div>
                </div>

                {{-- Area Dropzone Upload Tambahan --}}
                <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-stone-200 bg-stone-50 py-8 transition hover:bg-stone-100 hover:border-brand-soft">
                    <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-brand-soft text-brand">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </span>
                    <span class="text-sm font-bold text-ink">Upload Foto Kos</span>
                    <span class="mt-1 text-[11px] text-slate-500">PNG atau JPG, maksimal 5 MB per foto</span>
                    <input type="file" name="foto_kos[]" class="hidden" accept="image/png, image/jpeg" multiple>
                </label>
            </div>
        </div>

        {{-- KOLOM KANAN (Lokasi Map) --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm sticky top-24">
                <div class="mb-6 flex items-start gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-sm font-extrabold text-brand-dark">04</span>
                    <div>
                        <h3 class="text-base font-bold text-ink">Lokasi</h3>
                        <p class="text-xs text-slate-500">Tentukan titik kos pada peta.</p>
                    </div>
                </div>

                {{-- Peta Placeholder --}}
                <div class="relative mb-5 h-48 w-full overflow-hidden rounded-xl border border-stone-200 bg-[#EFE4DF]">
                    <span class="absolute left-1/2 top-1/2 h-[120%] w-[120%] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-[#c57b5f]/40"></span>
                    <span class="absolute left-1/2 top-1/2 h-[80%] w-[80%] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-[#c57b5f]/50"></span>
                    
                    {{-- Kampus Pin --}}
                    <div class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink text-white shadow-lg"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-5 9 5-9 5-9-5zm4 3v4c0 1 2.2 2 5 2s5-1 5-2v-4"/></svg></span>
                        <span class="mt-1 whitespace-nowrap rounded bg-ink px-1.5 py-0.5 text-[9px] font-bold text-white">Kampus SV IPB</span>
                    </div>

                    {{-- Target Pin Kos --}}
                    <div class="absolute left-[35%] top-[30%] flex -translate-x-1/2 -translate-y-full flex-col items-center">
                        <svg class="h-10 w-10 text-[#c57b5f] drop-shadow-md" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    </div>

                    {{-- Tooltip --}}
                    <div class="absolute bottom-3 left-3 rounded-lg bg-white/90 px-2.5 py-1.5 text-[10px] font-bold text-slate-700 shadow-sm backdrop-blur-sm">
                        <span class="mr-1 inline-block h-2 w-2 rounded-full bg-[#c57b5f]"></span>
                        Geser pin untuk menyesuaikan
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Latitude</label>
                        <input type="text" name="latitude" value="-6.5590" class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-slate-500 focus:outline-none" readonly>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700">Longitude</label>
                        <input type="text" name="longitude" value="106.7240" class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-slate-500 focus:outline-none" readonly>
                    </div>
                </div>

                {{-- Alert Info --}}
                <div class="mt-5 flex gap-3 rounded-xl border border-brand-soft bg-brand-soft/50 p-4">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[11px] leading-relaxed text-brand-dark">Titik peta otomatis menyesuaikan kolom Alamat. Geser pin pada peta jika lokasi belum tepat.</p>
                </div>
            </div>
        </div>

    </div>
</form>
@endsection