@extends('layouts.admin')

@section('title', 'Data Fasilitas')
@section('page_title', 'Data Fasilitas')
@section('breadcrumb', 'Admin / Data Fasilitas')

@section('content')
@php
    // Dummy Data Fasilitas beserta tag icon penuh (Mendukung SVG maupun FontAwesome)
    $dataFasilitas = [
        ['nama' => 'Wi-Fi', 'jumlah' => 98, 'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/></svg>'],
        ['nama' => 'AC', 'jumlah' => 43, 'icon' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M800-480H160q-33 0-56.5-23.5T80-560v-240q0-33 23.5-56.5T160-880h640q33 0 56.5 23.5T880-800v240q0 33-23.5 56.5T800-480ZM160-200v-80q50 0 85-35t35-85h80q0 83-58.5 141.5T160-200Zm640 0q-83 0-141.5-58.5T600-400h80q0 50 35 85t85 35v80Zm-360 40v-240h80v240h-80Zm360-400H160h640Zm-560 0v-80q0-33 23.5-56.5T320-720h320q33 0 56.5 23.5T720-640v80h-80v-80H320v80h-80Zm-80 0h640v-240H160v240Z"/></svg>'],
        ['nama' => 'Kamar Mandi Dalam', 'jumlah' => 76, 'icon' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M320-240q-17 0-28.5-11.5T280-280q0-17 11.5-28.5T320-320q17 0 28.5 11.5T360-280q0 17-11.5 28.5T320-240Zm160 0q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm160 0q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240ZM200-400v-80q0-106 68-184t172-92v-84h80v84q104 14 172 92t68 184v80H200Zm80-80h400q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480Zm40 360q-17 0-28.5-11.5T280-160q0-17 11.5-28.5T320-200q17 0 28.5 11.5T360-160q0 17-11.5 28.5T320-120Zm160 0q-17 0-28.5-11.5T440-160q0-17 11.5-28.5T480-200q17 0 28.5 11.5T520-160q0 17-11.5 28.5T480-120Zm160 0q-17 0-28.5-11.5T600-160q0-17 11.5-28.5T640-200q17 0 28.5 11.5T680-160q0 17-11.5 28.5T640-120ZM480-480Z"/></svg>'],
        ['nama' => 'Parkir', 'jumlah' => 65, 'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 8h2.5a2.5 2.5 0 010 5H10V8zm0 8v-3m11-1a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'],
        ['nama' => 'Dapur', 'jumlah' => 54, 'icon' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M320-640v-120h80v120h-80Zm0 360v-200h80v200h-80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h480q33 0 56.5 23.5T800-800v640q0 33-23.5 56.5T720-80H240Zm0-80h480v-360H240v360Zm0-440h480v-200H240v200Z"/></svg>'],
        ['nama' => 'Laundry', 'jumlah' => 71, 'icon' => '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" fill="#B46C54"><path d="M240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h480q33 0 56.5 23.5T800-800v640q0 33-23.5 56.5T720-80H240Zm0-80h480v-640H240v640Zm381.5-98.5Q680-317 680-400t-58.5-141.5Q563-600 480-600t-141.5 58.5Q280-483 280-400t58.5 141.5Q397-200 480-200t141.5-58.5ZM480-268q-26 0-50.5-9.5T386-306l188-188q19 19 28.5 43.5T612-400q0 55-38.5 93.5T480-268ZM320-680q17 0 28.5-11.5T360-720q0-17-11.5-28.5T320-760q-17 0-28.5 11.5T280-720q0 17 11.5 28.5T320-680Zm148.5-11.5Q480-703 480-720t-11.5-28.5Q457-760 440-760t-28.5 11.5Q400-737 400-720t11.5 28.5Q423-680 440-680t28.5-11.5ZM240-160v-640 640Z"/></svg>'],
        ['nama' => 'Keamanan 24 Jam', 'jumlah' => 38, 'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'],
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
            class="flex items-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#955844]"
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
                    class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-11 pr-4 text-sm text-ink placeholder-slate-400 transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54] shadow-sm" 
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
                    {{-- Ikon (Ukurannya dikecilkan sedikit menjadi h-11 w-11) --}}
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B46C54]">
                        {!! $fas['icon'] !!}
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
                        class="text-slate-400 transition-colors hover:text-[#B46C54]" 
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
    <div x-show="isAddModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
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
                    <input type="text" name="nama_fasilitas" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="Contoh: Area Belajar">
                </div>
                <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                    <button @click="isAddModalOpen = false" type="button" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800">Batal</button>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#955844] sm:w-auto shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ================= MODAL EDIT FASILITAS ================= --}}
    <div x-show="isEditModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
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
                    <input type="text" name="nama_fasilitas" x-model="activeFasilitas" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">
                </div>
                <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                    <button @click="isEditModalOpen = false" type="button" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800">Batal</button>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#955844] sm:w-auto shadow-sm">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ================= MODAL HAPUS FASILITAS ================= --}}
    <div x-show="isDeleteModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center">
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