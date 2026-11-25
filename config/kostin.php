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

    // Disk penyimpanan foto kos. Lokal: public. Server (Vercel): s3 (Neon Object Storage).
    'foto_disk' => env('KOSTIN_FOTO_DISK', 'public'),

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

        // Jarak maksimal ujung jalan tikus hasil survei ditempelkan ke jalan OSM (Fase Locality).
        'toleransi_snapping_m' => 15,

        // Radius area jalan OSM yang diimpor dari titik kampus. Disamakan dengan
        // radius_validasi_km supaya setiap kos yang bisa disimpan admin bisa dirutekan.
        'radius_import_km' => (float) env('KOSTIN_ROUTING_RADIUS_KM', 15),

        // Area import dipecah menjadi kotak-kotak kecil supaya tiap permintaan ke Overpass ringan.
        'ukuran_tile_km' => 3,

        'overpass_url' => env('KOSTIN_OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),

        // Hasil rute disimpan di cache. Cache otomatis tidak dipakai lagi setelah import ulang.
        'cache_hari' => 30,

        // Kos yang lebih jauh dari ini ke jalan terpetakan diberi catatan
        // (kandidat survei jalan tikus di Fase Locality).
        'peringatan_jarak_ke_jalan_m' => 50,

        // Rekaman GPS gang dirapikan dengan toleransi ini (titik yang goyang kurang dari 3 m dibuang).
        'penyederhanaan_survei_m' => 3,

        // Batas file GPX/GeoJSON rekaman gang.
        'berkas_survei_maks_kb' => 5120,
    ],

    // Layer tematik peta Jelajah.
    'peta' => [
        // Pita jangkauan jalan kaki dari kampus (isochrone), dalam menit.
        'jangkauan_menit' => [5, 10, 15, 20],

        // Lebar area di kiri-kanan jalan yang dianggap terjangkau (rumah/kos menghadap jalan).
        'jangkauan_lebar_m' => 30,

        // Lubang di tengah poligon jangkauan yang lebih kecil dari ini ditutup (blok rumah
        // yang dikelilingi jalan terjangkau). Lubang besar seperti kebun atau kompleks tertutup tetap.
        'jangkauan_lubang_maks_m2' => 20000,

        // Radius layer jaringan jalan dari kampus.
        'radius_jalan_km' => 2,
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