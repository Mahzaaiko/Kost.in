@extends('layouts.admin')

@section('title', 'Data Kos')
@section('page_title', 'Data Kos')
@section('breadcrumb', 'Admin / Data kos')

@section('content')

{{-- 
  WRAPPER ALPINE UTAMA 
  Menyimpan semua state (Search, Filter, Pagination, dan Delete Modal) dalam satu otak yang pintar.
--}}
<div x-data="{ 
    isDeleteModalOpen: false, 
    kosToDelete: '',
    urlHapus: '',
    isFilterOpen: false,
    searchQuery: '',
    filterTipe: '',
    filterStatus: '',
    currentPage: 1,
    perPage: 10,
    items: [
        @foreach ($dataKos as $kos)
        {
            id: {{ $kos->id }},
            nama: @js(mb_strtolower($kos->nama)),
            alamat: @js(mb_strtolower($kos->alamat ?? '')),
            tipe: @js($kos->tipe?->label() ?? ''),
            status: @js($kos->status->label()),
        },
        @endforeach
    ],
    init() {
        this.$watch('searchQuery', () => { this.currentPage = 1; });
        this.$watch('filterTipe', () => { this.currentPage = 1; });
        this.$watch('filterStatus', () => { this.currentPage = 1; });
    },
    get filteredItems() {
        const q = this.searchQuery ? this.searchQuery.toLowerCase().trim() : '';
        return this.items.filter(item => {
            const matchSearch = !q || item.nama.includes(q) || item.alamat.includes(q);
            const matchTipe = !this.filterTipe || item.tipe === this.filterTipe;
            const matchStatus = !this.filterStatus || item.status === this.filterStatus;
            return matchSearch && matchTipe && matchStatus;
        });
    },
    get totalFiltered() {
        return this.filteredItems.length;
    },
    get totalPages() {
        return Math.max(1, Math.ceil(this.totalFiltered / this.perPage));
    },
    get pagedItems() {
        const start = (this.currentPage - 1) * this.perPage;
        return this.filteredItems.slice(start, start + this.perPage);
    },
    get pagedIds() {
        return new Set(this.pagedItems.map(i => i.id));
    },
    get itemNumberMap() {
        const map = new Map();
        this.filteredItems.forEach((item, index) => {
            map.set(item.id, index + 1);
        });
        return map;
    },
    getRowNumber(id) {
        return this.itemNumberMap.get(id) ?? '';
    },
    isRowVisible(id) {
        return this.pagedIds.has(id);
    },
    get displayStart() {
        return this.totalFiltered === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
    },
    get displayEnd() {
        return Math.min(this.currentPage * this.perPage, this.totalFiltered);
    },
    get paginationPages() {
        const total = this.totalPages;
        const current = this.currentPage;
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        if (current <= 4) {
            return [1, 2, 3, 4, 5, '...', total];
        }
        if (current >= total - 3) {
            return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        }
        return [1, '...', current - 1, current, current + 1, '...', total];
    },
    goToPage(page) {
        if (typeof page === 'number' && page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            if (window.kostinResizeLenis) window.kostinResizeLenis();
        }
    },
    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            if (window.kostinResizeLenis) window.kostinResizeLenis();
        }
    },
    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            if (window.kostinResizeLenis) window.kostinResizeLenis();
        }
    }
}">

    {{-- ================= UI ALERT (dari session) ================= --}}
    @if (session('success'))
    <div 
        x-data="{ showAlert: true }" 
        x-show="showAlert" 
        x-cloak
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 shadow-sm"
    >
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-200/50 text-emerald-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <div>
                <h4 class="text-sm font-bold text-emerald-800">Berhasil!</h4>
                <p class="text-xs text-emerald-600">{{ session('success') }}</p>
            </div>
        </div>
        <button type="button" @click="showAlert = false" class="text-emerald-500 transition-colors hover:text-emerald-700" aria-label="Tutup">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    @if ($errors->any())
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 shadow-sm">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </span>
        <div>
            <h4 class="text-sm font-bold text-red-800">Belum bisa disimpan</h4>
            <p class="text-xs text-red-600">{{ $errors->first() }}</p>
        </div>
    </div>
    @endif

    {{-- ================= HEADER ================= --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Data Kos</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola informasi kos yang tersedia di sekitar Sekolah Vokasi IPB University.</p>
        </div>
        <a href="{{ route('admin.datakos.tambah') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-brand-dark">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Kos
        </a>
    </div>

    {{-- ================= KOTAK UTAMA (TABEL + TOOLBAR + PAGINASI) ================= --}}
    <div class="rounded-2xl border border-stone-200/90 bg-white shadow-sm overflow-hidden">
        
        {{-- Toolbar (Search & Filter) --}}
        <div class="flex flex-col items-start justify-between gap-4 border-b border-stone-100 p-4 sm:p-5 sm:flex-row sm:items-center">
            
            {{-- SEARCH INPUT --}}
            <div class="relative w-full max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    type="text" 
                    x-model="searchQuery"
                    class="block w-full rounded-xl border border-stone-200 bg-white py-2.5 pl-11 pr-4 text-sm text-ink placeholder-slate-400 transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand shadow-xs" 
                    placeholder="Cari nama kos atau alamat..."
                >
            </div>
            
            <div class="relative flex w-full items-center justify-between gap-3 sm:w-auto">
                
                {{-- TOMBOL FILTER --}}
                <button 
                    @click="isFilterOpen = !isFilterOpen"
                    :class="isFilterOpen ? 'bg-stone-100 border-stone-300' : 'bg-white border-stone-200 hover:bg-stone-50'"
                    class="flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors shadow-xs"
                >
                    <svg class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Filter</span>
                    <span x-show="filterTipe !== '' || filterStatus !== ''" class="flex h-2 w-2 rounded-full bg-brand" x-cloak></span>
                </button>
                
                {{-- COUNTER REAL-TIME SESUAI FILTER/PENCARIAN --}}
                <span class="text-sm font-semibold text-slate-500 whitespace-nowrap" x-text="totalFiltered + ' data kos'"></span>

                {{-- DROPDOWN MENU FILTER --}}
                <div 
                    x-show="isFilterOpen" 
                    x-cloak
                    @click.outside="isFilterOpen = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                    style="display: none;"
                    class="absolute right-0 top-full z-30 mt-3 w-72 rounded-2xl bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.12)] ring-1 ring-stone-200"
                >
                    <h4 class="mb-4 text-sm font-bold text-ink">Filter Data Kos</h4>
                    
                    {{-- Select Tipe (Custom Dropdown) --}}
                    <div class="mb-4" x-data="{ openTipe: false }">
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Tipe Kos</label>
                        <div class="relative">
                            <button 
                                @click="openTipe = !openTipe" 
                                @click.outside="openTipe = false"
                                type="button" 
                                class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                            >
                                <span x-text="filterTipe === '' ? 'Semua tipe' : filterTipe"></span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openTipe ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            
                            <div 
                                x-show="openTipe" 
                                x-cloak
                                style="display: none;"
                                class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg"
                            >
                                <ul class="flex flex-col py-1">
                                    <li @click="filterTipe = ''; openTipe = false" :class="filterTipe === '' ? 'bg-brand-soft font-bold text-brand' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Semua tipe</li>
                                    <li @click="filterTipe = 'Putri'; openTipe = false" :class="filterTipe === 'Putri' ? 'bg-brand-soft font-bold text-brand' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Putri</li>
                                    <li @click="filterTipe = 'Putra'; openTipe = false" :class="filterTipe === 'Putra' ? 'bg-brand-soft font-bold text-brand' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Putra</li>
                                    <li @click="filterTipe = 'Campur'; openTipe = false" :class="filterTipe === 'Campur' ? 'bg-brand-soft font-bold text-brand' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Campur</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Select Status (Custom Dropdown) --}}
                    <div class="mb-6" x-data="{ openStatus: false }">
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <div class="relative">
                            <button 
                                @click="openStatus = !openStatus" 
                                @click.outside="openStatus = false"
                                type="button" 
                                class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                            >
                                <span x-text="filterStatus === '' ? 'Semua status' : filterStatus"></span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openStatus ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            
                            <div 
                                x-show="openStatus" 
                                x-cloak
                                style="display: none;"
                                class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg"
                            >
                                <ul class="flex flex-col py-1">
                                    <li @click="filterStatus = ''; openStatus = false" :class="filterStatus === '' ? 'bg-brand-soft font-bold text-brand' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Semua status</li>
                                    <li @click="filterStatus = 'Aktif'; openStatus = false" :class="filterStatus === 'Aktif' ? 'bg-brand-soft font-bold text-brand' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Aktif</li>
                                    <li @click="filterStatus = 'Draft'; openStatus = false" :class="filterStatus === 'Draft' ? 'bg-brand-soft font-bold text-brand' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2 text-sm transition-colors">Draft</li>
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
            </div>
        </div>

        {{-- ================= TABLE AREA ================= --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-500">
                <thead class="border-b border-stone-200 bg-stone-50/50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="pl-6 pr-2 py-4 text-center w-12">No</th>
                        <th scope="col" class="px-6 py-4 text-left">Kos</th>
                        <th scope="col" class="px-6 py-4 text-center">Tipe</th>
                        <th scope="col" class="px-6 py-4 text-center">Harga</th>
                        <th scope="col" class="px-6 py-4 text-center">Jarak</th>
                        <th scope="col" class="px-6 py-4 text-center">Fasilitas</th>
                        <th scope="col" class="px-6 py-4 text-center">Status</th>
                        <th scope="col" class="px-6 py-4 text-center">Aksi</th> 
                    </tr>
                </thead>
                
                {{-- TBODY DENGAN REAKTIVITAS ALPINE --}}
                <tbody class="divide-y divide-stone-100">
                    @foreach ($dataKos as $kos)
                    @php
                        $tipeLabel = $kos->tipe?->label() ?? '';
                        $statusLabel = $kos->status->label();
                        $fasPertama = $kos->fasilitas->first();
                    @endphp
                    <tr class="data-row transition-colors hover:bg-stone-50/80"
                        x-show="isRowVisible({{ $kos->id }})"
                    >
                        {{-- Kolom 0: Nomor --}}
                        <td class="whitespace-nowrap pl-6 pr-2 py-4 text-center text-xs font-bold text-slate-400" x-text="getRowNumber({{ $kos->id }})"></td>

                        {{-- Kolom 1: Kos (Rata Kiri) --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-4">
                                @if ($kos->fotoUtama)
                                    <img src="{{ $kos->fotoUtama->url() }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover shadow-sm">
                                @else
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-ink text-lg font-bold text-white shadow-sm">
                                        {{ mb_strtoupper(mb_substr($kos->nama, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="w-48">
                                    <p class="truncate font-bold text-ink">{{ $kos->nama }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $kos->alamat ?? 'Alamat belum diisi' }}</p>
                                </div>
                            </div>
                        </td>
                        
                        {{-- Kolom 2: Tipe --}}
                        <td class="whitespace-nowrap px-6 py-4 text-center font-medium text-slate-600">
                            {{ $tipeLabel ?: '-' }}
                        </td>
                        
                        {{-- Kolom 3: Harga --}}
                        <td class="whitespace-nowrap px-6 py-4 text-center font-bold text-ink">
                            {{ \App\Support\Format::rupiah($kos->harga_per_bulan) }}
                        </td>
                        
                        {{-- Kolom 4: Jarak (garis lurus ke kampus) --}}
                        <td class="whitespace-nowrap px-6 py-4 text-center text-slate-600">
                            {{ \App\Support\Format::jarak($kos->jarak_m !== null ? (float) $kos->jarak_m : null) }}
                        </td>
                        
                        {{-- Kolom 5: Fasilitas --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center justify-center gap-1.5">
                                @if ($fasPertama)
                                    <span class="rounded bg-brand-soft px-2 py-1 text-[11px] font-bold text-brand">{{ $fasPertama->nama }}</span>
                                    @if ($kos->fasilitas->count() > 1)
                                        <span class="text-xs font-medium text-slate-400">+{{ $kos->fasilitas->count() - 1 }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </div>
                        </td>
                        
                        {{-- Kolom 6: Status (klik untuk mengubah) --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            @php $aktif = $kos->status === \App\Enums\StatusKos::Aktif; @endphp
                            <form method="POST" action="{{ route('admin.datakos.status', $kos) }}" class="flex justify-center">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $aktif ? 'draft' : 'aktif' }}">
                                <button 
                                    type="submit"
                                    title="{{ $aktif ? 'Klik untuk mengubah ke Draft' : 'Klik untuk mengaktifkan' }}"
                                    class="flex w-[86px] items-center gap-2 rounded-full p-1 pr-3 transition-colors focus:outline-none {{ $aktif ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' }}"
                                >
                                    <div class="relative flex h-5 w-9 shrink-0 items-center rounded-full bg-white shadow-sm">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full transition-all duration-200 ease-in-out {{ $aktif ? 'translate-x-4 bg-emerald-500' : 'translate-x-1 bg-amber-500' }}"></span>
                                    </div>
                                    <span class="text-xs font-bold">{{ $statusLabel }}</span>
                                </button>
                            </form>
                        </td>

                        {{-- Kolom 7: Aksi --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center justify-center gap-3 text-slate-400">
                                <a href="{{ route('admin.datakos.detail', $kos) }}" class="transition-colors hover:text-brand" title="Lihat">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('admin.datakos.edit', $kos) }}" class="transition-colors hover:text-blue-600" title="Edit">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <button type="button" @click="isDeleteModalOpen = true; kosToDelete = @js($kos->nama); urlHapus = @js(route('admin.datakos.hapus', $kos))" class="transition-colors hover:text-red-500" title="Hapus">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    @if ($dataKos->isEmpty())
                    <tr>
                        <td colspan="8" class="py-16">
                            <div class="flex flex-col items-center justify-center text-center">
                                <h3 class="text-sm font-bold text-ink">Belum ada data kos</h3>
                                <p class="mt-1 text-xs text-slate-500">Klik "Tambah Kos" untuk menambahkan data pertama.</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- Pesan Data Tidak Ditemukan --}}
                    <tr x-show="totalFiltered === 0 && items.length > 0" x-cloak style="display: none;">
                        <td colspan="8" class="py-16">
                            <div class="flex flex-col items-center justify-center text-center">
                                <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-stone-50 text-slate-300">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <h3 class="text-sm font-bold text-ink">Pencarian tidak ditemukan</h3>
                                <p class="mt-1 text-xs text-slate-500">Coba gunakan kata kunci atau filter lain untuk menemukan data kos.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ================= FOOTER TABEL (INFORMASI JUMLAH + SLIDER SECTION PAGINASI) ================= --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white px-6 py-4 border-t border-stone-100 rounded-b-2xl">
            <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-500">
                <span>
                    Menampilkan 
                    <span class="font-bold text-ink" x-text="displayStart"></span>&ndash;<span class="font-bold text-ink" x-text="displayEnd"></span> 
                    dari 
                    <span class="font-bold text-ink" x-text="totalFiltered"></span> data
                </span>
                <span x-show="totalFiltered !== items.length" class="text-xs text-slate-400 font-medium" x-cloak>
                    (difilter dari {{ $dataKos->count() }} total)
                </span>
            </div>

            {{-- Slider Section Paginasi --}}
            <div class="flex items-center gap-1.5" x-show="totalPages > 1" x-cloak>
                {{-- Tombol Prev (<) --}}
                <button 
                    type="button" 
                    @click="prevPage()" 
                    :disabled="currentPage === 1"
                    :class="currentPage === 1 ? 'opacity-30 cursor-not-allowed border-stone-200 text-stone-300' : 'border-stone-200 text-stone-600 hover:bg-stone-50 hover:text-ink shadow-xs'"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border bg-white transition"
                    title="Halaman sebelumnya"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>

                {{-- Angka-angka Halaman --}}
                <template x-for="(p, index) in paginationPages" :key="index">
                    <div class="flex items-center">
                        <template x-if="p === '...'">
                            <span class="flex h-9 w-7 items-center justify-center text-xs font-bold text-stone-400 select-none">...</span>
                        </template>
                        <template x-if="p !== '...'">
                            <button 
                                type="button" 
                                @click="goToPage(p)"
                                :class="currentPage === p ? 'bg-brand text-white font-bold shadow-xs' : 'bg-transparent text-stone-600 hover:bg-stone-100 font-semibold'"
                                class="flex h-9 min-w-9 px-2 items-center justify-center rounded-xl text-xs sm:text-sm transition"
                                x-text="p"
                            ></button>
                        </template>
                    </div>
                </template>

                {{-- Tombol Next (>) --}}
                <button 
                    type="button" 
                    @click="nextPage()" 
                    :disabled="currentPage === totalPages"
                    :class="currentPage === totalPages ? 'opacity-30 cursor-not-allowed border-stone-200 text-stone-300' : 'border-stone-200 text-stone-600 hover:bg-stone-50 hover:text-ink shadow-xs'"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border bg-white transition"
                    title="Halaman berikutnya"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

    </div>   {{-- END KOTAK UTAMA --}}

    {{-- ================= MODAL HAPUS DATA ================= --}}
    <div 
        x-show="isDeleteModalOpen" 
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center"
    >
        <div 
            x-show="isDeleteModalOpen"
            x-cloak
            x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm"
            @click="isDeleteModalOpen = false"
        ></div>

        <div 
            x-show="isDeleteModalOpen"
            x-cloak
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
                        <h4 class="text-base font-bold text-ink" x-text="kosToDelete"></h4>
                        <p class="mt-1 text-sm text-slate-500">
                            Apakah kamu yakin ingin menghapus data ini? Data kos tidak akan ditampilkan kembali pada website.
                        </p>
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
                <form method="POST" :action="urlHapus">
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
