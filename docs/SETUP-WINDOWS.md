# Setup Backend Kost.in di Windows

## Prasyarat
- PHP 8.2+ dengan `extension=pdo_pgsql` dan `extension=pgsql` aktif di `php.ini`
- Composer, Node.js (untuk Vite)
- PostgreSQL 18 + PostGIS Bundle (sudah termasuk pgRouting dan osm2pgrouting)

## 1. Buat database
Di pgAdmin (Query Tool pada database `postgres`):

```sql
CREATE DATABASE kostin;
CREATE DATABASE kostin_test;  -- dipakai php artisan test
```

Extension `postgis` dan `pgrouting` dibuat otomatis oleh migrasi, asalkan user
database punya hak `CREATE EXTENSION` (user `postgres` punya).

## 2. Konfigurasi .env
```bash
copy .env.example .env
php artisan key:generate
```
Lalu isi di `.env`:
- `DB_USERNAME` dan `DB_PASSWORD` sesuai PostgreSQL di laptop
- `ADMIN_EMAIL`, `ADMIN_USERNAME`, `ADMIN_PASSWORD` (minimal 8 karakter)

## 3. Migrasi dan seed
```bash
composer install
php artisan migrate --seed
```
Hasilnya: tabel `kos`, `fasilitas`, `fasilitas_kos`, `foto_kos`, 24 master
fasilitas, dan satu akun admin.

## 4. Jalankan
```bash
npm install
composer run dev
```
Buka http://127.0.0.1:8000/admin/login dan masuk dengan email atau username admin.

## 5. Test
```bash
php artisan test
```
Test memakai database `kostin_test` (PostgreSQL, bukan SQLite, karena butuh PostGIS).
