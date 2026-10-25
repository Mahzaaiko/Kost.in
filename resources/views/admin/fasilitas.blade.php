@extends('layouts.admin')

@section('title', 'Data Fasilitas')
@section('page_title', 'Data Fasilitas')
@section('breadcrumb', 'Admin / Data Fasilitas')

@section('content')
@php $pilihanIkon = \App\Models\Fasilitas::IKON; @endphp

{{-- 
  WRAPPER UTAMA ALPINE.JS 
  Menyimpan state untuk ketiga modal dan fitur pencarian 
--}}
<div x-data="{ 
    searchQuery: '',
    isAddModalOpen: false, 
    isEditModalOpen: false, 
    isDeleteModalOpen: false,
    activeFasilitas: '',
    activeIkon: '',
    activeJumlah: 0,
    urlEdit: '',
    urlHapus: ''
}">

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
    @endif

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
            <span class="text-sm font-medium text-slate-500">{{ $dataFasilitas->count() }} fasilitas terdaftar</span>
        </div>

        {{-- Daftar Fasilitas --}}
        <div class="divide-y divide-stone-100">
            @foreach ($dataFasilitas as $fas)
            <div 
                class="flex items-center justify-between p-5 transition-colors hover:bg-stone-50"
                x-show="@js(mb_strtolower($fas->nama)).includes(searchQuery.toLowerCase())"
            >
                <div class="flex items-center gap-4">
                    {{-- Ikon (Ukurannya dikecilkan sedikit menjadi h-11 w-11) --}}
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-[#B46C54]">
                        <x-ikon-fasilitas :ikon="$fas->ikon" />
                    </span>
                    {{-- Teks --}}
                    <div>
                        <h4 class="text-base font-bold text-ink">{{ $fas->nama }}</h4>
                        <p class="mt-0.5 text-sm text-slate-500">Digunakan pada {{ $fas->kos_count }} data kos</p>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="flex items-center gap-3">
                    <button 
                        @click="isEditModalOpen = true; activeFasilitas = @js($fas->nama); activeIkon = @js($fas->ikon ?? ''); urlEdit = @js(route('admin.fasilitas.update', $fas))"
                        class="text-slate-400 transition-colors hover:text-[#B46C54]" 
                        title="Edit"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button 
                        @click="isDeleteModalOpen = true; activeFasilitas = @js($fas->nama); activeJumlah = {{ $fas->kos_count }}; urlHapus = @js(route('admin.fasilitas.hapus', $fas))"
                        class="text-slate-400 transition-colors hover:text-red-500" 
                        title="Hapus"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @endforeach
            @if ($dataFasilitas->isEmpty())
                <p class="p-10 text-center text-sm text-slate-500">Belum ada fasilitas. Klik "Tambah Fasilitas" untuk menambahkan.</p>
            @endif
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
            <form action="{{ route('admin.fasilitas.store') }}" method="POST">
                @csrf
                <div class="px-6 py-6">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Fasilitas</label>
                    <input type="text" name="nama_fasilitas" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="Contoh: Area Belajar">
                    <label for="ikon-tambah" class="mb-1.5 mt-4 block text-sm font-semibold text-slate-700">Ikon</label>
                    <select id="ikon-tambah" name="ikon"  class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">
                        <option value="">Ikon umum (centang)</option>
                        @foreach ($pilihanIkon as $kunci => $label)
                            <option value="{{ $kunci }}">{{ $label }}</option>
                        @endforeach
                    </select>
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
            <form :action="urlEdit" method="POST">
                @csrf @method('PUT')
                <div class="px-6 py-6">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Fasilitas</label>
                    <input type="text" name="nama_fasilitas" x-model="activeFasilitas" required class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">
                    <label for="ikon-edit" class="mb-1.5 mt-4 block text-sm font-semibold text-slate-700">Ikon</label>
                    <select id="ikon-edit" name="ikon" x-model="activeIkon" class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]">
                        <option value="">Ikon umum (centang)</option>
                        @foreach ($pilihanIkon as $kunci => $label)
                            <option value="{{ $kunci }}">{{ $label }}</option>
                        @endforeach
                    </select>
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
                        <p class="mt-1 text-sm text-slate-500">Apakah kamu yakin ingin menghapus fasilitas ini? Fasilitas akan dihilangkan dari <span class="font-semibold" x-text="activeJumlah"></span> kos yang memakainya.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col-reverse justify-end gap-3 bg-stone-50/50 px-6 py-5 sm:flex-row">
                <button @click="isDeleteModalOpen = false" type="button" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition-colors hover:bg-stone-100 hover:text-slate-800">Batal</button>
                <form method="POST" :action="urlHapus">
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