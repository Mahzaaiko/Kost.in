<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DasborController extends Controller
{
    public function index()
    {
        // 1. Data statistik
        $stats = [
            ['label' => 'Total Kos',         'value' => 128, 'unit' => 'kos',      'note' => '+8 bulan ini',   'icon' => 'building', 'primary' => true],
            ['label' => 'Kos Aktif',         'value' => 115, 'unit' => 'kos',      'note' => '89,8% dari total', 'icon' => 'check'],
            ['label' => 'Data Fasilitas',    'value' => 18,  'unit' => 'fasilitas', 'note' => 'Jenis fasilitas', 'icon' => 'sparkles'],
            ['label' => 'Lokasi Terdaftar',  'value' => 128, 'unit' => 'lokasi',   'note' => 'Semua terpetakan', 'icon' => 'pin'],
        ];

        // 2. Data tabel kos terbaru
        $kosTerbaru = [
            ['nama' => 'Kos Putri Melati',   'alamat' => 'Jl. Kemuning No. 12, Dramaga, Bogor', 'harga' => 'Rp700rb', 'waktu' => '2 menit lalu', 'foto' => 'https://placehold.co/88x88/6b4a3a/ffffff?text=K'],
            ['nama' => 'Kos Putra Mandiri',  'alamat' => 'Jl. Raya Dramaga No. 45, Bogor',      'harga' => 'Rp550rb', 'waktu' => '2 hari lalu',  'foto' => 'https://placehold.co/88x88/2f2a28/ffffff?text=K'],
            ['nama' => 'Kos Griya Sejahtera', 'alamat' => 'Jl. Babakan Raya No. 7, Bogor',      'harga' => 'Rp950rb', 'waktu' => '3 hari lalu',  'foto' => 'https://placehold.co/88x88/b9b0a4/ffffff?text=K'],
            ['nama' => 'Kos Anggrek Indah',  'alamat' => 'Jl. Anggrek No. 3, Dramaga, Bogor',   'harga' => 'Rp800rb', 'waktu' => '4 hari lalu',  'foto' => 'https://placehold.co/88x88/4a403b/ffffff?text=K'],
        ];

        // 3. Jumlah notifikasi yang belum dibaca (dikirim ke Alpine.js)
        $jumlahNotif = 4;

        // Kirim semua variabel ke view menggunakan compact()
        return view('admin.dasbor', compact('stats', 'kosTerbaru', 'jumlahNotif'));
    }
}