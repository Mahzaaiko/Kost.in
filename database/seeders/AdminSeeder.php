<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Membuat (atau memperbarui) akun admin dari config kostin.admin (variabel ADMIN_* di .env).
 * Password tidak pernah ditulis di kode.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('kostin.admin.email');
        $password = (string) config('kostin.admin.password');

        if ($email === '' || $password === '') {
            throw new RuntimeException('Isi ADMIN_EMAIL dan ADMIN_PASSWORD di .env sebelum menjalankan seeder.');
        }

        if (strlen($password) < 8) {
            throw new RuntimeException('ADMIN_PASSWORD minimal 8 karakter.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('kostin.admin.name'),
                'username' => config('kostin.admin.username') ?: null,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
