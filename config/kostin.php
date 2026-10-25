<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi domain Kost.in
|--------------------------------------------------------------------------
| Nilai di sini dipakai oleh pencarian, rekomendasi, dan routing.
| Ubah lewat .env bila perlu, jangan ditulis langsung di kode.
*/

return [

    // Titik kampus: pusat buffer jarak dan tujuan rute pejalan kaki.
    'kampus' => [
        'nama' => env('KOSTIN_KAMPUS_NAMA', 'Kampus SV IPB'),
        'lat' => (float) env('KOSTIN_KAMPUS_LAT', -6.5895875),
        'lng' => (float) env('KOSTIN_KAMPUS_LNG', 106.8067344),
    ],

    // Koordinat kos yang lebih jauh dari ini dari kampus dianggap salah input
    // (misalnya lat/lng tertukar).
    'radius_validasi_km' => 15,

    // Batas maksimal filter jarak di halaman Jelajah.
    'jarak_maks_km' => 10,

    // Periode minimal sewa yang diizinkan (dalam bulan).
    'periode_bulan' => [1, 3, 6, 12],

    // Bobot rekomendasi (SAW, normalisasi min-max). Total harus 1.
    'rekomendasi' => [
        'bobot' => [
            'jarak' => 0.5,
            'harga' => 0.3,
            'fasilitas' => 0.2,
        ],
    ],

    // Routing pejalan kaki.
    'routing' => [
        'kecepatan_jalan_kmh' => 4.8,
        'toleransi_snapping_m' => 15,
    ],

    // Akun admin pertama, dibaca AdminSeeder. Isi lewat .env.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Kost.in'),
        'email' => env('ADMIN_EMAIL'),
        'username' => env('ADMIN_USERNAME'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Upload foto kos.
    'foto' => [
        'maks_kb' => 3072,
        'maks_jumlah' => 10,
    ],
];
