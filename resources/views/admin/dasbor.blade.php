@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb', 'Admin / Dashboard')

@section('content')
@php
    // Data contoh — nanti diganti dengan variabel dari DasborController
    $stats = [
        ['label' => 'Total Kos',         'value' => 128, 'unit' => 'kos',      'note' => '+8 bulan ini',   'icon' => 'building', 'primary' => true],
        ['label' => 'Kos Aktif',         'value' => 115, 'unit' => 'kos',      'note' => '89,8% dari total', 'icon' => 'check'],
        ['label' => 'Data Fasilitas',    'value' => 18,  'unit' => 'fasilitas', 'note' => 'Jenis fasilitas', 'icon' => 'sparkles'],
        ['label' => 'Lokasi Terdaftar',  'value' => 128, 'unit' => 'lokasi',   'note' => 'Semua terpetakan', 'icon' => 'pin'],
    ];

    $kosTerbaru = [
        ['nama' => 'Kos Putri Melati',   'alamat' => 'Jl. Kemuning No. 12, Dramaga, Bogor', 'harga' => 'Rp700rb', 'waktu' => '2 menit lalu', 'foto' => 'https://placehold.co/88x88/6b4a3a/ffffff?text=K'],
        ['nama' => 'Kos Putra Mandiri',  'alamat' => 'Jl. Raya Dramaga No. 45, Bogor',      'harga' => 'Rp550rb', 'waktu' => '2 hari lalu',  'foto' => 'https://placehold.co/88x88/2f2a28/ffffff?text=K'],
        ['nama' => 'Kos Griya Sejahtera', 'alamat' => 'Jl. Babakan Raya No. 7, Bogor',      'harga' => 'Rp950rb', 'waktu' => '3 hari lalu',  'foto' => 'https://placehold.co/88x88/b9b0a4/ffffff?text=K'],
        ['nama' => 'Kos Anggrek Indah',  'alamat' => 'Jl. Anggrek No. 3, Dramaga, Bogor',   'harga' => 'Rp800rb', 'waktu' => '4 hari lalu',  'foto' => 'https://placehold.co/88x88/4a403b/ffffff?text=K'],
    ];
@endphp

{{-- Kartu statistik --}}
<section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($stats as $s)
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-sm font-medium text-slate-600">{{ $s['label'] }}</p>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ !empty($s['primary']) ? 'bg-brand text-white' : 'bg-brand-soft text-brand' }}">
                    @switch($s['icon'])
                        @case('building')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path stroke-linecap="round" d="M9 8h2m2 0h2M9 12h2m2 0h2M10 21v-4h4v4"/></svg>
                            @break
                        @case('check')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            @break
                        @case('sparkles')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8L12 3zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/></svg>
                            @break
                        @default
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.686 7-11a7 7 0 10-14 0c0 5.314 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    @endswitch
                </span>
            </div>
            <p class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold">{{ $s['value'] }}</span>
                <span class="text-xs font-medium text-muted">{{ $s['unit'] }}</span>
            </p>
            <p class="mt-2 text-xs font-medium text-brand-dark">{{ $s['note'] }}</p>
        </div>
    @endforeach
</section>

{{-- Daftar kos & peta --}}
<section class="grid grid-cols-1 gap-6 xl:grid-cols-3">

    {{-- Data Kos Terbaru --}}
    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm xl:col-span-2">
        <div class="flex items-center justify-between px-6 py-5">
            <div>
                <h2 class="text-base font-bold">Data Kos Terbaru</h2>
                <p class="mt-0.5 text-xs text-muted">Pembaruan data kos terakhir</p>
            </div>
            <a href="{{ route('admin.datakos') }}" class="flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-brand-dark">
                Lihat semua
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-5-5l5 5-5 5"/></svg>
            </a>
        </div>

        <ul class="divide-y divide-stone-200 border-t border-stone-200">
            @foreach ($kosTerbaru as $kos)
                <li class="flex items-center gap-4 px-6 py-4">
                    <img src="{{ $kos['foto'] }}" alt="Foto {{ $kos['nama'] }}" class="h-11 w-11 shrink-0 rounded-md object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold">{{ $kos['nama'] }}</p>
                        <p class="mt-0.5 truncate text-xs text-muted">{{ $kos['alamat'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold">{{ $kos['harga'] }}</p>
                        <p class="mt-0.5 text-xs text-muted">{{ $kos['waktu'] }}</p>
                    </div>
                    <span class="rounded-full bg-brand-soft px-3 py-1.5 text-xs font-semibold text-brand-dark">Aktif</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Persebaran Kos --}}
    <div class="flex flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        <div class="px-6 py-5">
            <h2 class="text-base font-bold">Persebaran Kos</h2>
            <p class="mt-0.5 text-xs text-muted">Area sekitar kampus</p>
        </div>

        {{-- Peta ilustrasi (Nanti kita ganti dengan Leaflet sungguhan) --}}
        <div class="relative min-h-[260px] flex-1 overflow-hidden border-t border-stone-200 bg-[#EFE4DF]">
            {{-- Penanda kos --}}
            @foreach ([['left-[32%]','top-[34%]'], ['left-[66%]','top-[20%]'], ['left-[88%]','top-[40%]'], ['left-[72%]','top-[60%]'], ['left-[26%]','top-[76%]']] as [$x, $y])
                <span class="absolute {{ $x }} {{ $y }} flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md">
                    <svg class="h-4 w-4 text-brand" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.686 7-11a7 7 0 10-14 0c0 5.314 7 11 7 11z"/><circle cx="12" cy="10" r="2"/></svg>
                </span>
            @endforeach

            {{-- Penanda kampus --}}
            <div class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-ink text-white shadow-lg">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-5 9 5-9 5-9-5zm4 3v4c0 1 2.2 2 5 2s5-1 5-2v-4"/></svg>
                </span>
                <span class="mt-1 whitespace-nowrap rounded bg-ink px-2 py-0.5 text-[11px] font-semibold text-white">Kampus SV IPB</span>
            </div>
        </div>

        <div class="border-t border-stone-200 px-6 py-4 text-xs text-slate-600">
            <span class="font-bold text-ink">128</span> titik lokasi
        </div>
    </div>
</section>
@endsection