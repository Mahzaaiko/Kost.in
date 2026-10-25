@extends('layouts.admin')

@section('title', 'Tambah Data Kos')
@section('page_title', 'Tambah Data Kos')
@section('breadcrumb', 'Admin / Tambah Data Kos')

@section('content')
<form action="{{ route('admin.datakos.store') }}" method="POST" enctype="multipart/form-data">
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
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#B46C54] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#955844] sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Simpan Data Kos
            </button>
        </div>
    </div>

    @include('admin.partials.form-kos')
</form>
@endsection
