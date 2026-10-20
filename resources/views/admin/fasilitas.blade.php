@extends('layouts.admin')

@section('title', 'Data Fasilitas')
@section('page_title', 'Data Fasilitas')
@section('breadcrumb', 'Admin / Data Fasilitas')

@section('content')
@php
    // Dummy Data Fasilitas beserta icon SVG-nya
    $dataFasilitas = [
        ['nama' => 'Wi-Fi', 'jumlah' => 98, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>'],
        ['nama' => 'AC', 'jumlah' => 43, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 8h18M5 8v-2a2 2 0 012-2h10a2 2 0 012 2v2M8 12v3M12 12v3M16 12v3"/>'],
        ['nama' => 'Kamar Mandi Dalam', 'jumlah' => 76, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 12v7a2 2 0 002 2h12a2 2 0 002-2v-7M3 12h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/>'],
        ['nama' => 'Parkir', 'jumlah' => 65, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 8h2.5a2.5 2.5 0 010 5H10V8zm0 8v-3m11-1a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
        ['nama' => 'Dapur', 'jumlah' => 54, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zM9 3v18M14 9h-2M14 15h-2"/>'],
        ['nama' => 'Laundry', 'jumlah' => 71, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 12a4 4 0 108 0 4 4 0 00-8 0zm-3 8h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM9 6h1"/>'],
        ['nama' => 'Keamanan 24 Jam', 'jumlah' => 38, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>'],
    ];
@endphp

{{-- 
  WRAPPER UTAMA ALPINE.JS 
  Menyimpan state untuk ketiga modal dan fitur pencarian 
--}}
<div x-data="{ 
    searchQuery: '',
    isAddModalOpen: false, 
    isEditModalOpen: false, 
    isDeleteModalOpen: false,
    activeFasilitas: ''
}">

    {{-- ================= HEADER ================= --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-bold text-ink">Data Fasilitas</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola jenis fasilitas yang dapat digunakan pada data kos.</p>
        </div>
        <button 
            @click="isAddModalOpen = true"
            class="flex items-center gap-2 rounded-xl bg-[#c57b5f] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#a6644b]"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Fasilitas
        </button>
    </div>

    {{-- ================= KONTEN UTAMA ================= --}}
    <div class="rounded-2xl border border-stone-200 bg-white shadow-sm">
        
        {{-- Toolbar Pencarian --}}
        <div class="flex flex-col items-start justify-between gap-4 border-b border-stone-200 p-5 sm:flex-row sm:items-center">
            <div class="relative w-full max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    type="text" 
                    x-model="searchQuery"
                    class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-11 pr-4 text-sm text-ink placeholder-slate-400 transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand shadow-sm" 
                    placeholder="Cari fasilitas..."
                >
            </div>
            <span class="text-sm font-medium text-slate-500">18 fasilitas terdaftar</span>
        </div>

        {{-- Daftar Fasilitas --}}
        <div class="divide-y divide-stone-100">
            @foreach ($dataFasilitas as $fas)
            <div 
                class="flex items-center justify-between p-5 transition-colors hover:bg-stone-50"
                x-show="'{{ strtolower($fas['nama']) }}'.includes(searchQuery.toLowerCase())"
            >
                <div class="flex items-center gap-4">
                    {{-- Ikon --}}
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#c57b5f]">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{!! $fas['icon'] !!}</svg>
                    </span>
                    {{-- Teks --}}
                    <div>
                        <h4 class="text-base font-bold text-ink">{{ $fas['nama'] }}</h4>
                        <p class="mt-0.5 text-sm text-slate-500">Digunakan pada {{ $fas['jumlah'] }} data kos</p>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="flex items-center gap-3">
                    <button 
                        @click="isEditModalOpen = true; activeFasilitas = '{{ $fas['nama'] }}'"
                        class="text-slate-400 transition-colors hover:text-blue-600" 
                        title="Edit"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button 
                        @click="isDeleteModalOpen = true; activeFasilitas = '{{ $fas['nama'] }}'"
                        class="text-slate-400 transition-colors hover:text-red-500" 
                        title="Hapus"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>


    {{-- ================= MODAL TAMBAH FASILITAS ================= --}}
    <div x-show="isAddModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <div x-show="isAddModalOpen" class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm" @click="isAddModalOpen = false"
            x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div x-show="isAddModalOpen" class="relative mx-4 w-full max-w-md overflow-hidden rounded-2xl bg-white text-left shadow-2xl"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="border-b border-stone-100 px-6 py-5">
                <h3 class="text-lg font-bold text-ink">Tambah Fasilitas</h3>
                <p class="mt-1 text-xs text-slate-500">Tambahkan jenis fasilitas baru untuk data kos.</p>
            </div>
            <form action="#" method="POST">
                @csrf
                <div class="px-6 py-6">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Fasilitas</label>
                    <input type="text" name="nama_fasilitas" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand" placeholder="Contoh: Area Belajar">
                </div>
                <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                    <button @click="isAddModalOpen = false" type="button" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800">Batal</button>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#c57b5f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a6644b] sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ================= MODAL EDIT FASILITAS ================= --}}
    <div x-show="isEditModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <div x-show="isEditModalOpen" class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm" @click="isEditModalOpen = false"
            x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div x-show="isEditModalOpen" class="relative mx-4 w-full max-w-md overflow-hidden rounded-2xl bg-white text-left shadow-2xl"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="border-b border-stone-100 px-6 py-5">
                <h3 class="text-lg font-bold text-ink">Edit Fasilitas</h3>
                <p class="mt-1 text-xs text-slate-500">Perbarui nama fasilitas yang tersedia.</p>
            </div>
            <form action="#" method="POST">
                @csrf @method('PUT')
                <div class="px-6 py-6">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Fasilitas</label>
                    {{-- Nilai input diikat secara real-time dari tombol edit yang diklik menggunakan Alpine x-model --}}
                    <input type="text" name="nama_fasilitas" x-model="activeFasilitas" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
                </div>
                <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                    <button @click="isEditModalOpen = false" type="button" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800">Batal</button>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#c57b5f] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#a6644b] sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ================= MODAL HAPUS FASILITAS ================= --}}
    <div x-show="isDeleteModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <div x-show="isDeleteModalOpen" class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm" @click="isDeleteModalOpen = false"
            x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div x-show="isDeleteModalOpen" class="relative mx-4 w-full max-w-md overflow-hidden rounded-2xl bg-white text-left shadow-2xl"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            <div class="border-b border-stone-100 px-6 py-5">
                <h3 class="text-lg font-bold text-ink">Hapus Fasilitas?</h3>
            </div>
            <div class="px-6 py-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-ink" x-text="activeFasilitas"></h4>
                        <p class="mt-1 text-sm text-slate-500">Apakah kamu yakin ingin menghapus fasilitas ini? Fasilitas akan dihilangkan dari semua kos yang terkait.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                <button @click="isDeleteModalOpen = false" type="button" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800">Batal</button>
                <form method="POST" action="#">
                    @csrf @method('DELETE')
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