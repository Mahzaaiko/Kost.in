# Progres Backend Kost.in

Dokumen ini mencatat apa yang sudah dikerjakan di backend, keputusan teknis yang sudah disepakati, dan alur kerja tim. Baca ini dulu sebelum mulai mengerjakan apa pun di repo.

Terakhir diperbarui: 25 Oktober 2026 (selesai Fase Admin)

---

## 1. Status singkat

| Fase | Jadwal | Status |
|---|---|---|
| Persiapan (audit, klarifikasi, rencana) | 25 Okt | Selesai |
| Fondasi (database, model, login admin) | 26–28 Okt | Selesai |
| Admin (CRUD kos, foto, fasilitas, dasbor, import) | 29 Okt–1 Nov | Selesai |
| Pengguna (API Jelajah, filter, rekomendasi, Beranda) | 2–4 Nov | Belum |
| Routing (OSM + pgRouting, tombol Lihat Rute) | 5–8 Nov | Belum |
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
- **Lokasi:** klik peta, cari alamat (Nominatim), GPS, atau tempel koordinat Google Maps. Titik yang lebih dari 15 km dari kampus ditolak.
- **Status Draft/Aktif:** kos baru selalu Draft. Kos hanya bisa diaktifkan kalau tipe, harga, periode, alamat, titik lokasi, dan minimal 1 foto sudah lengkap. Kos Aktif tidak bisa disimpan dalam keadaan tidak lengkap.
- **Fasilitas:** tambah, ubah nama dan ikon, hapus. Nama tidak boleh kembar (tidak membedakan huruf besar). Menghapus fasilitas melepasnya dari semua kos.
- **Dasbor:** statistik, kos terbaru, dan peta persebaran dari database.
- **Import spreadsheet:** perintah `kos:import` (lihat bagian 6).

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
| Import spreadsheet | `app/Console/Commands/ImportKos.php`, `app/Services/Import/NormalisasiKos.php` |
| Utilitas | `app/Support/Koordinat.php`, `Format.php`, `PlusCode.php` |
| Ikon fasilitas | `resources/views/components/ikon-fasilitas.blade.php` |
| Test | `tests/Feature`, `tests/Unit` (86 test) |

### Route admin

| URL | Fungsi |
|---|---|
| `/admin/login` | Login admin |
| `/admin/dasbor` | Dasbor |
| `/admin/datakos` | Daftar kos (+ `/tambah`, `/{id}`, `/{id}/edit`, `/{id}/status`) |
| `/admin/fasilitas` | Daftar dan kelola fasilitas |

Halaman pengguna (`/pengguna/beranda`, `/pengguna/jelajah-kos`) **masih memakai data dummy**. Diganti di Fase Pengguna.

---

## 5. Alur kerja tim

### Mulai bekerja (pertama kali)
Ikuti `docs/SETUP-WINDOWS.md`, lalu:
```
php artisan migrate --seed
php artisan storage:link
php artisan test
```

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

## 7. Yang belum dikerjakan dan batasan yang diketahui

- Halaman pengguna (Beranda, Jelajah) masih dummy.
- Routing, jalan tikus, dan deploy belum dikerjakan.
- Tabel Data Kos di admin perlu digeser ke samping di layar kecil. Perapian responsif dijadwalkan di Fase Finishing.
- Notifikasi admin ditunda.
- Belum ada kos Aktif karena titik lokasi dan foto belum lengkap. Menunggu survei lapangan.

### Backlog
- Notifikasi di header admin
- Menggambar jalan tikus langsung di peta admin
- Area yang terjangkau dalam X menit jalan kaki (`pgr_drivingDistance`)

---

## 8. Riwayat

| Tanggal | Perubahan |
|---|---|
| 25 Okt 2026 | Audit repo, klarifikasi kebutuhan, rencana implementasi disetujui |
| 25 Okt 2026 | Fase Fondasi selesai: PostGIS, skema kos, seeder, login admin |
| 25 Okt 2026 | Fase Admin selesai: CRUD kos, foto, fasilitas, dasbor, import spreadsheet |
