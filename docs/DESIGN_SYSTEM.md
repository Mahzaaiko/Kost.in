# Kost.in — Design System & Kerangka Halaman Beranda

Panduan palet warna, tipografi, dan struktur *section* untuk halaman beranda **Kost.in** (Laravel Blade + Tailwind CSS).

---

## 1. Tipografi & Gaya Bahasa
* **Font Utama:** `Plus Jakarta Sans` (Google Fonts) — bobot `400`, `500`, `600`, `700`, `800`, `900`.
* **Gaya Bahasa:** Semi-formal santai yang ramah mahasiswa Sekolah Vokasi IPB dan pengunjung umum. Penulisan kata **"kost"** diseragamkan memakai huruf **t**.

---

## 2. Palet Warna (Color Tokens)

### A. Warna Hijau (Background Section & Teks Utama)
| Nama Token | Hex Code | Tailwind Class | Keterangan Penggunaan |
| :--- | :--- | :--- | :--- |
| **Deep Ink Green** | `#163A27` | `text-[#163A27]` | Judul utama (`H1`, `H2`, `H3`) di atas latar terang |
| **Emerald Brand** | `#0E7A4D` | `text-[#0E7A4D]` | Highlight teks Hero (*"Sekolah Vokasi IPB University!"*) |
| **Body Forest** | `#244B35` | `text-[#244B35]` | Teks paragraf/subjudul di atas latar hijau muda |
| **Forest Footer** | `#24593C` | `bg-[#24593C]` | Background utama **Footer** |
| **Leaf Green** | `#C1E6A7` | `bg-[#C1E6A7]` | Background **Section Kost Populer** |
| **Sage Mint** | `#CDE7C1` | `bg-[#CDE7C1]` | Background **Section Tentang** & **Section Peta** |
| **Hero Sky Top** | `#D5EEE0` | `from-[#D5EEE0]` | Gradasi langit atas **Hero Header** (area belakang navbar) |
| **Hero Sky Mid** | `#DFF2E6` | `via-[#DFF2E6]` | Gradasi langit tengah **Hero Header** |
| **Hero Sky Bottom** | `#E6F5E8` | `to-[#E6F5E8]` | Gradasi langit bawah **Hero Header** |
| **Soft Mint Card** | `#EAF6E8` | `bg-[#EAF6E8]` | Background kartu keunggulan **02** & **04** (Border: `#D4EAD2`) |

### B. Warna Hangat (Aksen, CTA & Kartu)
| Nama Token | Hex Code | Tailwind Class | Keterangan Penggunaan |
| :--- | :--- | :--- | :--- |
| **Terracotta Primary** | `#B8694C` | `bg-[#B8694C]` / `text-[#B8694C]` | Tombol CTA (*Cari Kost*), teks *"Cari kost nyaman"*, harga kost, tombol panah kartu |
| **Terracotta Hover** | `#955844` | `hover:bg-[#955844]` | Warna *hover* tombol terakota |
| **Warm Cream Card** | `#FDF8EE` | `bg-[#FDF8EE]` | Background **Kartu Kost Populer** (Border: `#EFE5D1`) |
| **Soft Cream Card** | `#FDF7EA` | `bg-[#FDF7EA]` | Background kartu keunggulan **01** & **03** (Border: `#EFE3CC`) |
| **Chip Cream** | `#F5EFE0` | `bg-[#F5EFE0]` | Tag/badge fasilitas di dalam kartu kost (Border: `#E6DEC8`) |
| **Peach Badge** | `#FDE7D2` | `bg-[#FDE7D2]` | Lingkaran nomor `01` & `03` (Teks: `#C2410C`) |
| **Mint Badge** | `#CBE6C9` | `bg-[#CBE6C9]` | Lingkaran nomor `02` & `04` (Teks: `#15803D`) |

### C. Warna Kategori Kost (Badge & Legenda Peta)
* **Kost Putri:** Text `text-pink-700`, BG `bg-pink-50`, Border `border-pink-200`, Pin/Titik `#EC4899`
* **Kost Putra:** Text `text-blue-700`, BG `bg-blue-50`, Border `border-blue-200`, Pin/Titik `#3B82F6`
* **Kost Campur:** Text `text-purple-700`, BG `bg-purple-50`, Border `border-purple-200`, Pin/Titik `#A855F7`

---

## 3. Kerangka Desain Beranda (Urutan Atas ke Bawah)

### 0. Navbar (`layouts/pengguna.blade.php`)
* **Wrapper:** Transparan (`bg-transparent`) agar gradasi langit Hero menyatu sampai ujung atas layar.
* **Kontainer:** *Floating pill* putih (`bg-white rounded-full shadow-md`), berisi logo `public/images/logo kost.in.svg` di kiri, link navigasi di tengah (`Beranda`, `Tentang`, `Peta`, `Jelajah Kost`), dan tombol CTA `Cari Kost` (`bg-[#B8694C] text-white`) di kanan.

### 1. Hero Header
* **Background:** Gradasi `bg-gradient-to-b from-[#D5EEE0] via-[#DFF2E6] to-[#E6F5E8]` dengan `-mt-24 pt-24` agar naik ke belakang navbar.
* **Komposisi Visual & Layer:**
  * **Latar Bawah:** Ilustrasi lingkungan kampus, rumah kost, dan Tugu Kujang (`public/images/Main Content.svg`).
  * **Kolom Kiri:** Pill tag `REKOMENDASI KOST SV IPB`, Headline utama 4 baris, subheadline, *search bar* pill putih (`bg-white rounded-full shadow-lg`), dan counter `{{ $jumlahKos }}+ kost terverifikasi`.
  * **Kolom Kanan:** Peta 3D (`public/images/map_3d_header.svg`) yang dikelilingi dekorasi awan putih dan daun (`public/images/daun_1.svg`, `public/images/daun_2.svg`).
* **Transisi ke Section 2:** Gelombang SVG S-curve dengan `fill="#CDE7C1"` dan `-mb-[2px]` supaya sambungannya rapat tanpa garis celah.

### 2. Section Tentang Kost.in (`#tentang`)
* **Background:** `#CDE7C1` (`bg-[#CDE7C1]`).
* **Header Section (Rata Tengah):**
  * Label kecil: `TENTANG KOST.IN` (`text-[#7E6722]`)
  * Judul: `Kenapa memilih Kost.in?` (`text-[#163A27]`)
  * Subjudul: `Kost.in hadir untuk membantu kamu menemukan kost terbaik di sekitar Sekolah Vokasi IPB University, dengan informasi yang akurat dan mudah diakses.`
* **Konten Utama (4 Kartu Keunggulan):**
  * Susunan 4 kartu sejajar yang sedikit miring membentuk kipas (*fanned deck*) dan berdiri tegak saat di-hover (`border-[#B8694C]`):
    1. **01 — Data Terverifikasi** (BG `#FDF7EA`)
    2. **02 — Filter Lengkap** (BG `#EAF6E8`)
    3. **03 — Hubungi Langsung** (BG `#FDF7EA`)
    4. **04 — 100% Gratis** (BG `#EAF6E8`)
* **Dekorasi Ambient:** Partikel daun jatuh (`daun_1.svg` & `daun_2.svg`) melayang dari section ini sampai batas atas footer.
* **Transisi ke Section 3:** Gelombang SVG S-curve dengan `fill="#71A857"` dan `-mb-[2px]`.

### 3. Section Kost Populer (`#kos`)
* **Background:** `#71A857` (`bg-[#71A857]`).
* **Header Section:**
  * Label kecil: `PALING DIMINATI` (`text-[#143B22]`)
  * Judul: `Kost yang paling banyak dilihat` (`text-[#11331D]`)
  * Subjudul: `Hunian favorit pengunjung dengan informasi lokasi, fasilitas, dan harga bulanan yang lengkap.` (`text-[#194529]`)
  * Link kanan: `Lihat semua kost →`
* **Konten Utama (Grid 3 Kolom Kartu Kost):**
  * Kartu menggunakan warna krem hangat `#FDF8EE` (`bg-[#FDF8EE] rounded-3xl border border-[#EFE5D1]`).
  * Isi kartu: Foto (`aspect-[16/10] rounded-2xl`), badge tipe kost di atas foto, nama kost, jarak dari kampus, chips fasilitas (`bg-[#F5EFE0]`), harga bulanan (`text-[#B8694C] font-black`), dan tombol bulat panah kanan (`bg-[#B8694C]`).
* **Transisi ke Section 4:** Gelombang SVG S-curve dengan `fill="#CDE7C1"` dan `-mb-[2px]`.

### 4. Section Peta Lokasi (`#peta`)
* **Background:** `#CDE7C1` (`bg-[#CDE7C1]`).
* **Konten Utama (Grid 2 Kolom):**
  * **Kiri:** Ilustrasi peta Kota Bogor (`public/images/peta.svg`).
  * **Kanan:** Label `ANTI TELAT NGAMPUS` (`text-[#B8694C]`), Judul `Biar ke kampus makin sat-set, cek kost di sekitar kampus.` (`text-[#163A27]`), deskripsi singkat, legenda titik warna (`Putri`, `Putra`, `Campur`), dan link `Buka peta kost →`.

### 5. Transisi Pepohonan & Footer
* **Transisi Atas Footer:** `public/images/footer_pepohonan.svg` selebar layar penuh (`w-full h-auto block -mt-[2px] -mb-[2px]`) di atas latar `#CDE7C1`.
* **Background Footer:** `#24593C` (`bg-[#24593C]`) menyambung langsung dari dasar siluet pepohonan.
* **Konten Footer (3 Kolom + Bottom Bar):**
  * **Kolom 1:** Logo `logo kost.in.svg` + teks `Kost.in` + deskripsi singkat (`Bantu kamu nemuin kost yang nyaman dan aman di sekitar kampus SV IPB, Bogor.`).
  * **Kolom 2 (Menu):** `Beranda`, `Tentang`, `Peta`, `Jelajah Kost`.
  * **Kolom 3 (Kampus):** `Sekolah Vokasi IPB University`, `Kota Bogor, Jawa Barat`, `100% Gratis Tanpa Biaya Admin`.
  * **Bottom Bar:** `© 2026 Kost.in. Dibuat khusus buat anak kost SV IPB.` serta link `Privasi` dan `Syarat penggunaan`.