@extends('layouts.admin')

@section('title', 'Data Kos')
@section('page_title', 'Data Kos')
@section('breadcrumb', 'Admin / Data kos')

@section('content')
@php
    // Dummy data — Nanti dipindahkan ke Controller
    $dataKos = [
        ['nama' => 'Kos Putri Melati',   'alamat' => 'Jl. Kemuning No. 12, Dramaga, Bogor', 'tipe' => 'Putri',  'harga' => 'Rp700.000', 'jarak' => '1.2 km', 'fasilitas_utama' => 'Wi-Fi', 'fasilitas_plus' => '+3', 'status' => 'Aktif', 'foto' => 'https://placehold.co/88x88/6b4a3a/ffffff?text=K'],
        ['nama' => 'Kos Putra Mandiri',  'alamat' => 'Jl. Raya Dramaga No. 45, Bogor',      'tipe' => 'Putra',  'harga' => 'Rp550.000', 'jarak' => '0.8 km', 'fasilitas_utama' => 'Wi-Fi', 'fasilitas_plus' => '+2', 'status' => 'Aktif', 'foto' => 'https://placehold.co/88x88/2f2a28/ffffff?text=K'],
        ['nama' => 'Kos Griya Sejahtera', 'alamat' => 'Jl. Babakan Raya No. 7, Bogor',      'tipe' => 'Campur', 'harga' => 'Rp950.000', 'jarak' => '1.8 km', 'fasilitas_utama' => 'Wi-Fi', 'fasilitas_plus' => '+5', 'status' => 'Aktif', 'foto' => 'https://placehold.co/88x88/b9b0a4/ffffff?text=K'],
        ['nama' => 'Kos Anggrek Indah',  'alamat' => 'Jl. Anggrek No. 3, Dramaga, Bogor',   'tipe' => 'Putri',  'harga' => 'Rp800.000', 'jarak' => '1.5 km', 'fasilitas_utama' => 'Wi-Fi', 'fasilitas_plus' => '+3', 'status' => 'Aktif', 'foto' => 'https://placehold.co/88x88/4a403b/ffffff?text=K'],
        ['nama' => 'Kos Budi Jaya',      'alamat' => 'Jl. Kampus IPB No. 22, Bogor',        'tipe' => 'Putra',  'harga' => 'Rp450.000', 'jarak' => '0.5 km', 'fasilitas_utama' => 'Wi-Fi', 'fasilitas_plus' => '+1', 'status' => 'Draft', 'foto' => 'https://placehold.co/88x88/d3cdc4/ffffff?text=K'],
        ['nama' => 'Kos Harmoni Vokasi', 'alamat' => 'Jl. Raya Ciampea No. 15, Bogor',      'tipe' => 'Campur', 'harga' => 'Rp1.200.000', 'jarak' => '2.1 km', 'fasilitas_utama' => 'Wi-Fi', 'fasilitas_plus' => '+6', 'status' => 'Aktif', 'foto' => 'https://placehold.co/88x88/a49d94/ffffff?text=K'],
    ];
@endphp

{{-- 
  WRAPPER ALPINE UTAMA 
  Menyimpan semua state (Search, Filter, dan Delete Modal) dalam satu otak yang pintar.
--}}
<div x-data="{ 
    isDeleteModalOpen: false, 
    kosToDelete: '',
    isFilterOpen: false,
    searchQuery: '',
    filterTipe: '',
    filterStatus: ''
}">
{{-- 
      =======================================================
      UI ALERT SUKSES 
      ======================================================= 
    --}}
    <div 
        x-data="{ showAlert: true }" 
        x-show="showAlert" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm"
    >
        <div class="flex items-center gap-3">
            {{-- Ikon Ceklis --}}
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-200/50 text-emerald-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h4 class="text-sm font-bold text-emerald-800">Berhasil!</h4>
                <p class="text-xs text-emerald-600">Data kos berhasil disimpan dan diterbitkan.</p>
            </div>
        </div>
        
        {{-- Tombol Close Alert --}}
        <button @click="showAlert = false" class="text-emerald-500 transition-colors hover:text-emerald-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    {{-- END ALERT --}}
    
    {{-- Header Section --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-bold text-ink">Data Kos</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola informasi kos yang tersedia di sekitar Sekolah Vokasi IPB University.</p>
        </div>
        <a href="{{ route('admin.datakos.tambah') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-dark">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Kos
        </a>
    </div>

    {{-- Tabel Container --}}
    <div class="rounded-2xl border border-stone-200 bg-white shadow-sm">
        
        {{-- Toolbar (Search & Filter) --}}
        <div class="flex flex-col items-start justify-between gap-4 border-b border-stone-200 p-5 sm:flex-row sm:items-center">
            
            {{-- SEARCH INPUT (Terkoneksi x-model="searchQuery") --}}
            <div class="relative w-full max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    type="text" 
                    x-model="searchQuery"
                    class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-11 pr-4 text-sm text-ink placeholder-slate-400 transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand shadow-sm" 
                    placeholder="Cari nama kos..."
                >
            </div>
            
            <div class="flex w-full items-center justify-between gap-4 sm:w-auto relative">
                
                {{-- TOMBOL FILTER --}}
                <button 
                    @click="isFilterOpen = !isFilterOpen"
                    :class="isFilterOpen ? 'bg-stone-100 border-stone-300' : 'bg-white border-stone-200 hover:bg-stone-50'"
                    class="flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                
                <span class="text-sm font-medium text-slate-500">128 data kos</span>

                {{-- DROPDOWN MENU FILTER --}}
                <div 
                    x-show="isFilterOpen" 
                    @click.outside="isFilterOpen = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                    style="display: none;"
                    class="absolute right-0 top-full mt-3 w-72 z-30 rounded-2xl bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.12)] ring-1 ring-stone-200"
                >
                    <h4 class="mb-4 text-sm font-bold text-ink">Filter Data Kos</h4>
                    
                    {{-- Select Tipe (Custom Dropdown) --}}
                    <div class="mb-4" x-data="{ openTipe: false }">
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Tipe Kos</label>
                        <div class="relative">
                            {{-- Tombol Pemicu --}}
                            <button 
                                @click="openTipe = !openTipe" 
                                @click.outside="openTipe = false"
                                type="button" 
                                class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                            >
                                <span x-text="filterTipe === '' ? 'Semua tipe' : filterTipe"></span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openTipe ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            
                            {{-- Daftar Pilihan --}}
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
                                    <li @click="filterTipe = ''; openTipe = false" :class="filterTipe === '' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Semua tipe</li>
                                    <li @click="filterTipe = 'Putri'; openTipe = false" :class="filterTipe === 'Putri' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Putri</li>
                                    <li @click="filterTipe = 'Putra'; openTipe = false" :class="filterTipe === 'Putra' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Putra</li>
                                    <li @click="filterTipe = 'Campur'; openTipe = false" :class="filterTipe === 'Campur' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Campur</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Select Status (Custom Dropdown) --}}
                    <div class="mb-6" x-data="{ openStatus: false }">
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <div class="relative">
                            {{-- Tombol Pemicu --}}
                            <button 
                                @click="openStatus = !openStatus" 
                                @click.outside="openStatus = false"
                                type="button" 
                                class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                            >
                                <span x-text="filterStatus === '' ? 'Semua status' : filterStatus"></span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openStatus ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            
                            {{-- Daftar Pilihan --}}
                            <div 
                                x-show="openStatus" 
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
                                    <li @click="filterStatus = ''; openStatus = false" :class="filterStatus === '' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Semua status</li>
                                    <li @click="filterStatus = 'Aktif'; openStatus = false" :class="filterStatus === 'Aktif' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Aktif</li>
                                    <li @click="filterStatus = 'Draft'; openStatus = false" :class="filterStatus === 'Draft' ? 'bg-brand-soft font-bold text-brand-dark' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Draft</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Aksi Filter --}}
                    <div class="flex items-center gap-3">
                        <button @click="filterTipe = ''; filterStatus = ''" class="w-full rounded-xl border border-stone-200 bg-white py-2 text-sm font-bold text-slate-600 transition hover:bg-stone-50 hover:text-slate-800">
                            Reset
                        </button>
                        <button @click="isFilterOpen = false" class="w-full rounded-xl bg-brand py-2 text-sm font-bold text-white transition hover:bg-brand-dark">
                            Terapkan
                        </button>
                    </div>
                </div>
                {{-- END DROPDOWN FILTER --}}

            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full table-fixed min-w-[900px] text-left text-sm text-ink">
                
                {{-- KUNCI ABSOLUT: Gunakan colgroup agar kolom tidak bergeser walau di-filter --}}
                <colgroup>
                    <col class="w-[30%]"> {{-- Kos --}}
                    <col class="w-[10%]"> {{-- Tipe --}}
                    <col class="w-[15%]"> {{-- Harga --}}
                    <col class="w-[10%]"> {{-- Jarak --}}
                    <col class="w-[15%]"> {{-- Fasilitas --}}
                    <col class="w-[10%]"> {{-- Status --}}
                    <col class="w-[10%]"> {{-- Aksi --}}
                </colgroup>

                <thead class="bg-stone-50/50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-stone-200">
                    <tr>
                        <th scope="col" class="px-6 py-4">Kos</th>
                        <th scope="col" class="px-6 py-4">Tipe</th>
                        <th scope="col" class="px-6 py-4">Harga</th>
                        <th scope="col" class="px-6 py-4">Jarak</th>
                        <th scope="col" class="px-6 py-4">Fasilitas</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($dataKos as $kos)
                    {{-- 
                        LOGIKA x-show ALPINE UNTUK FILTER OTOMATIS: 
                        Baris ini hanya akan muncul jika teks pencarian cocok dengan nama, 
                        serta tipe & status cocok dengan dropdown yang dipilih.
                    --}}
                    <tr class="transition-colors hover:bg-stone-50/80"
                        x-show="
                            ('{{ strtolower($kos['nama']) }}'.includes(searchQuery.toLowerCase())) &&
                            (filterTipe === '' || '{{ $kos['tipe'] }}' === filterTipe) &&
                            (filterStatus === '' || '{{ $kos['status'] }}' === filterStatus)
                        "
                    >
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img src="{{ $kos['foto'] }}" alt="{{ $kos['nama'] }}" class="h-12 w-12 rounded-lg object-cover">
                                <div>
                                    <p class="font-bold text-ink">{{ $kos['nama'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $kos['alamat'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-600">{{ $kos['tipe'] }}</td>
                        <td class="whitespace-nowrap px-6 py-4 font-bold text-ink">{{ $kos['harga'] }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ $kos['jarak'] }}</td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                <span class="rounded bg-brand-soft px-2 py-1 text-[11px] font-bold text-brand-dark">{{ $kos['fasilitas_utama'] }}</span>
                                <span class="text-xs font-medium text-slate-400">{{ $kos['fasilitas_plus'] }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                {{-- Warna Status (Hijau untuk Aktif, Kuning untuk Draft) --}}
                                @if($kos['status'] == 'Aktif')
                                    <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                    <span class="font-bold text-green-700">{{ $kos['status'] }}</span>
                                @else
                                    <span class="h-2 w-2 rounded-full bg-yellow-400"></span>
                                    <span class="font-bold text-yellow-600">{{ $kos['status'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3 text-slate-400">
                                {{-- AKSI --}}
                                {{-- Detail --}}
                                <a href="#" class="hover:text-brand-dark transition-colors" title="Lihat">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                {{-- Edit --}}
                                <a href="{{ route('admin.datakos.edit', 1) }}" class="hover:text-blue-600 transition-colors" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                {{-- Delete (Trigger Modal) --}}
                                <button @click="isDeleteModalOpen = true; kosToDelete = '{{ $kos['nama'] }}'" class="hover:text-red-500 transition-colors" title="Hapus">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            {{-- Pesan Data Tidak Ditemukan --}}
            <div x-show="
                ![...document.querySelectorAll('tbody tr')].some(tr => tr.style.display !== 'none')
            " style="display: none;" class="flex flex-col items-center justify-center py-12">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-stone-100 text-slate-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-500">Pencarian tidak ditemukan.</p>
                <p class="text-xs text-slate-400">Coba gunakan kata kunci atau filter lain.</p>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between border-t border-stone-200 px-6 py-4">
            <p class="text-xs text-slate-500">Menampilkan data tabel</p>
            <div class="flex items-center gap-1">
                <button class="flex h-8 w-8 items-center justify-center rounded-md border border-stone-200 text-slate-400 hover:bg-stone-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md bg-brand text-sm font-bold text-white">1</button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md text-sm font-bold text-slate-600 hover:bg-stone-50">2</button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md text-sm font-bold text-slate-600 hover:bg-stone-50">3</button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md border border-stone-200 text-slate-600 hover:bg-stone-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
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

        {{-- Modal Content Box --}}
        <div 
            x-show="isDeleteModalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white text-left shadow-2xl mx-4"
        >
            {{-- Header Modal --}}
            <div class="border-b border-stone-100 px-6 py-5">
                <h3 class="text-lg font-bold text-ink">Hapus Data Kos?</h3>
            </div>

            {{-- Body Modal --}}
            <div class="px-6 py-6">
                <div class="flex items-start gap-4">
                    {{-- Ikon Peringatan Hapus --}}
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    
                    <div>
                        {{-- Nama Kos Dilempar dari Alpine.js --}}
                        <h4 class="text-base font-bold text-ink" x-text="kosToDelete"></h4>
                        <p class="mt-1 text-sm text-slate-500">
                            Apakah kamu yakin ingin menghapus data ini? Data kos tidak akan ditampilkan kembali pada website.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="flex flex-col-reverse justify-end gap-3 px-6 py-5 sm:flex-row bg-stone-50/50">
                <button 
                    @click="isDeleteModalOpen = false" 
                    type="button" 
                    class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800"
                >
                    Batal
                </button>
                
                {{-- Form Hapus Asli (Bisa diaktifkan nanti oleh Backend) --}}
                <form method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button 
                        type="submit" 
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#D64545] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-700 sm:w-auto"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection