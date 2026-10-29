# Progres Backend Kost.in

Dokumen ini mencatat apa yang sudah dikerjakan di backend, keputusan teknis yang sudah disepakati, dan alur kerja tim. Baca ini dulu sebelum mulai mengerjakan apa pun di repo.

Terakhir diperbarui: 29 Oktober 2026 (Fase Routing selesai, form kos menerima Plus Code)

---

## 1. Status singkat

| Fase | Jadwal | Status |
|---|---|---|
| Persiapan (audit, klarifikasi, rencana) | 25 Okt | Selesai |
| Fondasi (database, model, login admin) | 26–28 Okt | Selesai |
| Admin (CRUD kos, foto, fasilitas, dasbor, import) | 29 Okt–1 Nov | Selesai |
| Pengguna (API Jelajah, filter, rekomendasi, Beranda) | 2–4 Nov | Selesai |
| Routing (OSM + pgRouting, tombol Lihat Rute) | 5–8 Nov | Selesai 26 Okt |
| Deploy percobaan (VPS) | 8–9 Nov | Belum |
| Locality (jalan tikus, validasi jarak) | 9–11 Nov | Belum |
| Finishing (blackbox testing, bug, dokumentasi, deploy final) | 11–14 Nov | Belum |

Tenggat: **15 November 2026**.

Pekerjaan lapangan yang berjalan paralel (target 5 November):
- Survei titik lokasi semua kos (GPS di HP atau pin di form admin)
- Foto kos
- Rekam jalan tikus (GPX dari HP atau gambar GeoJSON)

---

## 2. Keputusan teknis yang sudah disepakati

| Topik | Keputusan | Alasan singkat |
|---|---|---|
| Arsitektur | Laravel 12 monolith. Admin pakai Blade + form POST, halaman Jelajah ambil data lewat API JSON | Sesuai proposal, form admin sudah siap |
| Database | PostgreSQL 18 + PostGIS + pgRouting 4.0.1 | Filter radius dan routing dihitung di database sendiri |
| Peta | Leaflet + OpenStreetMap, bukan Google Maps | Saran dosen: fungsi spasial sendiri, bisa dicek, bisa menambah jalan tikus. Rute Google wajib tampil di Google Map dan butuh billing |
| Titik kampus | Jl. Kumbang No.14 (Plus Code `CR64+5M9`) = `-6.5895875, 106.8067344` | Satu titik untuk pusat buffer dan tujuan rute |
| Filter jarak dan rekomendasi | Jarak garis lurus dari titik kampus | Bogor Tengah padat permukiman, garis lurus cukup mewakili |
| Rute "Lihat Rute" | Jalur pejalan kaki dari pgRouting (OSM + jalan tikus hasil survei) | Aspek locality yang diminta dosen |
| Rekomendasi | SAW, normalisasi min-max, bobot Jarak 50 / Harga 30 / Fasilitas 20 | Transparan dan mudah dijelaskan di laporan |
| Harga | Disimpan sebagai **harga per bulan** + periode minimal sewa (1/3/6/12 bulan) | Harga tahunan dibagi 12 supaya bisa dibandingkan |
| Nama pemilik | Tidak disimpan | Datanya sulit didapat |
| Login | Hanya admin, session Laravel, bisa pakai email atau username | Mahasiswa tidak perlu akun |
| Foto | Disimpan lokal di `storage/app/public/kos/{id}` | Cukup untuk skala proyek |
| Notifikasi admin | Ditunda (backlog) | Bukan kebutuhan inti |
| Pengujian | Blackbox testing + feature test PHPUnit | Blackbox untuk laporan, PHPUnit untuk verifikasi internal |

---

## 3. Yang sudah dikerjakan

### Fase Fondasi
- Migrasi mengaktifkan extension `postgis` dan `pgrouting`.
- Tabel `kos` dengan kolom `lokasi` bertipe `geography(Point, 4326)`, `fasilitas`, `fasilitas_kos`, `foto_kos`, serta kolom `username` di `users`.
- Aturan di database: hanya satu foto utama per kos, periode minimal hanya 1/3/6/12 bulan, harga harus lebih dari 0.
- `App\Support\Koordinat` dan cast `GeographyPoint`: semua koordinat lewat sini supaya urutan lat/lng tidak tertukar (PostGIS memakai urutan lng lat).
- Seeder 24 master fasilitas dan akun admin dari variabel `ADMIN_*` di `.env`.
- Login admin dengan email atau username, dikunci sementara setelah 5 kali gagal dalam semenit. Semua halaman `/admin` wajib login.

### Fase Admin
- **Data kos:** tambah, lihat detail, edit, hapus. Form Tambah dan Edit memakai satu partial (`admin/partials/form-kos.blade.php`).
- **Foto:** upload banyak foto (JPG/PNG, maks 3 MB, maks 10 per kos), pilih foto utama, hapus foto lama. File fisiknya ikut dihapus.
- **Lokasi:** klik peta, cari alamat (Nominatim), GPS, atau tempel koordinat / Plus Code dari Google Maps (contoh `CR66+V3`, diubah ke koordinat lewat `GET /admin/plus-code`). Titik yang lebih dari 15 km dari kampus ditolak.
- **Status Draft/Aktif:** kos baru selalu Draft. Kos hanya bisa diaktifkan kalau tipe, harga, periode, alamat, titik lokasi, dan minimal 1 foto sudah lengkap. Kos Aktif tidak bisa disimpan dalam keadaan tidak lengkap.
- **Fasilitas:** tambah, ubah nama dan ikon, hapus. Nama tidak boleh kembar (tidak membedakan huruf besar). Menghapus fasilitas melepasnya dari semua kos.
- **Dasbor:** statistik, kos terbaru, dan peta persebaran dari database.
- **Import spreadsheet:** perintah `kos:import` (lihat bagian 6).

### Fase Pengguna
- **API `GET /api/kos`** (publik, maks 60 request/menit): hanya kos Aktif yang punya titik lokasi. Parameter:
  - `harga_max` (rupiah per bulan)
  - `jarak_max_km` (0,1 sampai 10, buffer garis lurus `ST_DWithin`)
  - `tipe` (`putri`, `putra`, `campur`, atau `semua`)
  - `fasilitas[]` (id fasilitas, kos harus punya **semua** yang dipilih)
  - `q` (cari di nama dan alamat)
  - `urut` (`rekomendasi` default, `jarak`, `murah`, `mahal`)
- **Rekomendasi SAW** (`app/Services/RekomendasiService.php`): skor 0 sampai 1 = 0,5 x jarak + 0,3 x harga + 0,2 x jumlah fasilitas, dinormalisasi min-max terhadap kos yang lolos filter. Contoh hitungan ada di `tests/Unit/RekomendasiServiceTest.php`.
- **API `POST /api/kos/{id}/dilihat`**: menambah jumlah dilihat, sekali per IP per kos dalam 6 jam.
- **Halaman Jelajah**: data dari API, filter dan urutan berjalan di server, ada opsi urutan "Rekomendasi" dengan skor, fasilitas di filter hanya yang dimiliki kos aktif, bisa dibuka dengan `?q=` dan `?kos={id}`.
- **Beranda**: jumlah kos, 3 kos yang paling sering dilihat, dan peta radius dari data asli.

### Fase Routing
- **Tabel `ruas_jalan`**: satu baris = satu ruas jalan di antara dua simpul (persimpangan atau ujung jalan), geometri `LineString` 4326, panjang dalam meter, `sumber` (`osm` atau `survei` untuk jalan tikus nanti), dan `utama` (ruas yang terhubung ke jaringan terbesar).
- **Perintah `jalan:import-osm`** (lihat bagian 7): mengambil jalan pejalan kaki dari OpenStreetMap lewat Overpass API dalam radius 15 km dari kampus (sama dengan batas validasi titik kos, jadi semua kos yang bisa disimpan admin bisa dirutekan). Jalan tol dan jalan bertanda `foot=no` atau `access=no` dibuang. Jalan dipotong di setiap persimpangan, lalu komponen terhubung terbesar ditandai `utama` dengan `pgr_connectedComponents`.
- **Perhitungan rute** (`app/Services/Routing/RuteService.php`):
  1. Titik kos dan titik kampus ditempelkan ke ruas `utama` terdekat. Karena hanya ruas utama yang dipakai, rute selalu bisa ditemukan.
  2. `pgr_dijkstra` (tidak berarah, pejalan kaki boleh melawan arah jalan satu arah) dihitung dari kedua ujung ruas kos ke kedua ujung ruas kampus, lalu dipilih total terpendek.
  3. Graf dibatasi ke area sekitar kedua titik supaya cepat. Bila tidak ketemu, dihitung ulang dengan seluruh jaringan.
  4. Panjang rute = jarak kos ke jalan + jarak di jaringan + jarak jalan ke kampus. Waktu tempuh memakai 4,8 km/jam.
  5. Hasil disimpan di cache 30 hari. Setiap import ulang mengganti versi jaringan, jadi cache lama otomatis tidak dipakai.
- **API `GET /api/kos/{id}/rute`**: hanya kos Aktif. Isinya `tersedia`, `panjang_m`, `durasi_menit`, `jarak_ke_jalan_m`, `catatan` (bila titik kos lebih dari 50 m dari jalan terpetakan), dan `geometri` (GeoJSON LineString).
- **Halaman Jelajah**: tombol "Lihat Rute" menggambar rute yang mengikuti jalan dengan label jarak dan waktu. Bila rute tidak tersedia, kembali ke garis lurus dengan keterangan.
- **Perintah `rute:cek`**: mengecek semua kos bisa dirutekan dan membandingkan panjang rute dengan garis lurus (bahan tabel validasi jarak di laporan).

### Perbaikan pada tampilan lama
- Checkbox fasilitas di form Tambah dulu tidak terkirim (tidak punya `name`).
- Foto di form Tambah dulu tidak pernah terkirim ke server.
- Peta form Tambah dulu langsung memasang pin di titik default, sehingga kos bisa tersimpan di lokasi yang salah.
- Ikon "sembunyikan password" di halaman login tidak tergambar karena path SVG rusak.
- Notifikasi dummy di header admin disembunyikan, header dan sidebar menampilkan admin yang sedang login.

---

## 4. Peta kode

| Bagian | Lokasi |
|---|---|
| Konfigurasi domain (kampus, bobot, batas foto) | `config/kostin.php` |
| Model | `app/Models/Kos.php`, `Fasilitas.php`, `FotoKos.php` |
| Enum | `app/Enums/TipeKos.php`, `StatusKos.php`, `PeriodeSewa.php` |
| Controller admin | `app/Http/Controllers/Admin/` (Auth, Dasbor, Kos, Fasilitas) |
| Validasi | `app/Http/Requests/Admin/` |
| Logika simpan kos dan foto | `app/Services/KosService.php` |
| Import spreadsheet | `app/Services/Import/NormalisasiKos.php` |
| Pencarian dan rekomendasi | `app/Services/PencarianKosService.php`, `app/Services/RekomendasiService.php` |
| API pengguna | `app/Http/Controllers/Api/KosController.php`, `routes/api.php` |
| Routing (import jalan dan rute) | `app/Services/Routing/ImportJaringanJalan.php`, `app/Services/Routing/RuteService.php` |
| Perintah artisan | `app/Console/Commands/` (`kos:import`, `jalan:import-osm`, `rute:cek`) |
| Halaman pengguna | `app/Http/Controllers/BerandaController.php`, `JelajahController.php` |
| Utilitas | `app/Support/Koordinat.php`, `Format.php`, `PlusCode.php` |
| Ikon fasilitas | `resources/views/components/ikon-fasilitas.blade.php` |
| Test | `tests/Feature`, `tests/Unit` (132 test) |

### Route admin

| URL | Fungsi |
|---|---|
| `/admin/login` | Login admin |
| `/admin/dasbor` | Dasbor |
| `/admin/datakos` | Daftar kos (+ `/tambah`, `/{id}`, `/{id}/edit`, `/{id}/status`) |
| `/admin/fasilitas` | Daftar dan kelola fasilitas |
| `/admin/plus-code?kode=` | Ubah Plus Code ke koordinat (JSON, dipakai form kos) |

Halaman pengguna: `/pengguna/beranda` dan `/pengguna/jelajah-kos` (bisa dengan `?q=` dan `?kos={id}`).

---

## 5. Alur kerja tim

### Mulai bekerja (pertama kali)
Ikuti `docs/SETUP-WINDOWS.md`, lalu:
```
php artisan migrate --seed
php artisan storage:link
php artisan jalan:import-osm
php artisan test
```
`jalan:import-osm` butuh internet dan memakan beberapa menit. Tanpa ini, tombol Lihat Rute memakai garis lurus.

### Setiap kali mulai bekerja
```
git checkout main
git pull origin main
composer install
php artisan migrate
```
`composer install` dan `migrate` perlu dijalankan setiap ada perubahan dari anggota lain, supaya library dan tabel database ikut terbaru.

### Menjalankan aplikasi di Windows
`composer run dev` tidak bisa dipakai di Windows. Pakai dua terminal:
```
php artisan serve
npm run dev
```

### Aturan Git
1. Satu fase dikerjakan di satu branch: `backend/<nama-fase>`, misalnya `backend/pengguna`.
2. Pesan commit cukup satu kalimat yang menjelaskan isi perubahan.
3. Sebelum merge ke `main`: `git pull origin main`, lalu `php artisan test` harus lulus semua.
4. Kalau ada conflict di file Blade, jangan diselesaikan asal. Diskusikan dulu dengan pemilik file tersebut.
5. Jangan pernah commit `.env`, file CSV berisi data asli, atau folder `public/storage`.
6. Setiap selesai satu fase, perbarui dokumen ini.

---

## 6. Import data kos dari spreadsheet

Sumber: tab **"Salinan + Manual"** di spreadsheet pendataan, diunduh sebagai CSV (File, Download, Comma-separated values).

```
php artisan kos:import "C:\path\ke\file.csv" --dry-run
php artisan kos:import "C:\path\ke\file.csv"
```
`--dry-run` hanya menampilkan laporan tanpa menyimpan. Perintah aman dijalankan ulang karena kos yang namanya sudah ada akan dilewati.

Aturan pembakuan:
- Semua kos masuk sebagai **Draft**.
- Telepon: nol di depan dipulihkan, `+62` jadi `0`.
- Jenis: Wanita jadi Putri, Laki-laki/Laki jadi Putra.
- Periode: kalau tertulis beberapa, diambil yang terpendek.
- Harga: di atas Rp3 juta dianggap per tahun lalu dibagi 12 (dibulatkan ke ribuan). Kalau ada beberapa pilihan harga, diambil yang termurah.
- Fasilitas: variasi penulisan dibakukan ke 24 master. Yang tidak dikenali dicatat.
- Plus Code di alamat langsung dijadikan titik lokasi.
- Baris dengan nama sama digabung.

Semua penyesuaian dicatat di kolom `catatan_import` dan tampil di halaman detail kos admin sebagai "Catatan import spreadsheet". Admin perlu mengecek catatan ini, misalnya harga Kost Bagunde dan Kost Naraya yang dianggap per tahun.

Hasil import data asli (25 Okt): 85 baris menjadi 84 kos Draft. Baru 4 kos yang punya titik lokasi (dari Plus Code), dan belum ada yang punya foto.

---

## 7. Import jaringan jalan OSM

```
php artisan jalan:import-osm --dry-run
php artisan jalan:import-osm
php artisan rute:cek --semua
```
- `--dry-run` mengambil data dan menampilkan jumlah ruas tanpa mengubah tabel.
- `--radius=5` untuk mencoba area lebih kecil (default 15 km, diatur di `.env` lewat `KOSTIN_ROUTING_RADIUS_KM`).
- Area dipecah menjadi kotak 3 x 3 km dan diambil satu per satu dengan jeda 1 detik, supaya tidak membebani server publik Overpass.
- Kalau Overpass sedang sibuk (HTTP 429/502/504) atau koneksi putus, kotak itu dicoba ulang 3 kali, lalu dilewati dan dicoba sekali lagi di akhir setelah jeda 30 detik.
- Kotak yang berhasil diunduh disimpan sementara di `storage/app/private/osm-cache`. Kalau import tetap gagal, tabel `ruas_jalan` tidak diubah, dan menjalankan ulang perintah yang sama hanya mengunduh kotak yang belum ada. Simpanan ini dihapus otomatis setelah import berhasil. Pakai `--segarkan` untuk mengabaikan simpanan dan mengunduh ulang semuanya.
- Aman dijalankan ulang: semua ruas `osm` diganti data terbaru, ruas `survei` (jalan tikus) tidak disentuh.
- Setelah progress bar 100%, masih ada tahap pengolahan di database (memotong jalan, menyimpan ruas, mencari jaringan utama). Tiap tahap ditampilkan di layar dan butuh sekitar satu sampai beberapa menit untuk area 15 km.
- Kalau perintah dihentikan paksa (Ctrl+C) saat tahap pengolahan, query di PostgreSQL bisa tetap berjalan dan mengunci tabel `ruas_jalan`. Hentikan dengan SQL berikut di pgAdmin (database `kostin`), atau restart service PostgreSQL:
  ```sql
  SELECT pg_terminate_backend(pid) FROM pg_stat_activity
  WHERE datname = 'kostin' AND pid <> pg_backend_pid();
  ```
- `rute:cek` harus berakhir dengan "Berhasil dirutekan: N dari N kos". Kos yang lebih dari 50 m dari jalan terpetakan diberi catatan: cek lagi titiknya, atau catat sebagai kandidat survei jalan tikus.
- Data jalan © kontributor OpenStreetMap (lisensi ODbL). Atribusi sudah tampil di pojok peta.

---

## 8. Yang belum dikerjakan dan batasan yang diketahui

- Import OSM data asli (26 Okt): 75.190 jalan menjadi 143.477 ruas (9.444,7 km), 99,7% ruas ada di jaringan utama. `rute:cek --semua`: 5 dari 5 kos berhasil dirutekan. Rute Griya Malabar (880 m) cocok dengan jalan yang biasa dilewati penghuninya. Kost Dito Ekonomis II memutar jauh (rasio 2,52), menjadi prioritas survei jalan tikus.
- Jalan tikus dan deploy belum dikerjakan.
- Jalan di OSM bisa belum lengkap (gang kecil sering belum terpetakan). Inilah yang akan ditutup survei jalan tikus di Fase Locality.
- Tabel Data Kos di admin perlu digeser ke samping di layar kecil. Perapian responsif dijadwalkan di Fase Finishing.
- Notifikasi admin ditunda.
- Sebagian kos sudah Aktif. Kos lain masih Draft karena titik lokasi atau foto belum lengkap, menunggu survei lapangan.

### Backlog
- Notifikasi di header admin
- Menggambar jalan tikus langsung di peta admin
- Area yang terjangkau dalam X menit jalan kaki (`pgr_drivingDistance`)

---

## 9. Riwayat

| Tanggal | Perubahan |
|---|---|
| 25 Okt 2026 | Audit repo, klarifikasi kebutuhan, rencana implementasi disetujui |
| 25 Okt 2026 | Fase Fondasi selesai: PostGIS, skema kos, seeder, login admin |
| 25 Okt 2026 | Fase Admin selesai: CRUD kos, foto, fasilitas, dasbor, import spreadsheet |
| 26 Okt 2026 | Fase Pengguna selesai: API Jelajah, filter buffer PostGIS, rekomendasi SAW, penghitung view, Beranda |
| 26 Okt 2026 | Kode Fase Routing selesai: import jalan OSM, rute pgr_dijkstra, API rute, Lihat Rute di Jelajah, `rute:cek` |
| 26 Okt 2026 | Import jalan OSM bisa dilanjutkan bila Overpass gagal di tengah jalan (simpanan kotak + coba ulang di akhir) |
| 26 Okt 2026 | Pemotongan jalan OSM dipercepat (uji 141 ribu jalan: lebih dari 21 menit menjadi sekitar 40 detik) |
| 29 Okt 2026 | Form Tambah/Edit kos menerima Plus Code dari Google Maps |