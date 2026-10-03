# Kost.in - Platform WebGIS Pencarian Kos

**Kost.in** adalah platform WebGIS interaktif yang dikembangkan secara spesifik untuk membantu mahasiswa, khususnya di lingkungan Sekolah Vokasi IPB University, dalam mencari dan memilih tempat kos secara terintegrasi.

Proyek ini memadukan visualisasi data spasial (peta digital) dengan informasi properti kos (harga, fasilitas, jarak) untuk mengatasi kendala kurangnya informasi ketersediaan kos yang akurat dan mudah diakses.

## Tujuan Proyek

Tujuan utama pengembangan Kost.in adalah:

1. Menyediakan solusi terintegrasi berupa platform WebGIS untuk membantu mahasiswa menemukan kos yang sesuai.
2. Memvisualisasikan titik persebaran kos di sekitar kampus secara interaktif.
3. Membantu pengguna melakukan perbandingan jarak, harga, dan fasilitas kos dengan lebih efisien.

## Fitur Utama

### 1. Peta Interaktif & Visualisasi Spasial

Menampilkan titik lokasi kos secara akurat pada peta digital yang mencakup area sekitar Sekolah Vokasi IPB University.

### 2. Proximity Analysis (Buffer Zone)

Fitur analisis spasial yang memungkinkan pengguna melihat radius atau area cakupan jarak tertentu dari kampus menuju lokasi kos, mempermudah pertimbangan mobilitas.

### 3. Pencarian & Filter Terintegrasi

Memungkinkan pengguna untuk mengeksplorasi dan menyaring kos berdasarkan preferensi tertentu (seperti harga, tipe kos, dan fasilitas) guna menemukan pilihan terbaik.

### 4. Dashboard Admin

Panel manajemen khusus (CMS) untuk mengelola data kos, memperbarui ketersediaan, dan menambahkan titik koordinat baru secara dinamis.

## Alur Penggunaan (Userflow)

Secara garis besar, alur interaksi pengguna pada Kost.in adalah sebagai berikut:

```text
┌─────────────────────────┐
│  Eksplorasi Awal Peta   │
└────────────┬────────────┘
             ↓
┌─────────────────────────┐
│ Terapkan Filter & Radius│
└────────────┬────────────┘
             ↓
┌─────────────────────────┐
│ Lihat Detail Kos & Rute │
└────────────┬────────────┘
             ↓
┌─────────────────────────┐
│   Hubungi Pemilik Kos   │
└─────────────────────────┘
```
