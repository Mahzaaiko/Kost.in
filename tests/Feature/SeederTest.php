<?php

namespace Tests\Feature;

use App\Models\Fasilitas;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_fasilitas_membuat_24_master_dan_aman_dijalankan_ulang(): void
    {
        $this->seed(FasilitasSeeder::class);
        $this->seed(FasilitasSeeder::class);

        $this->assertSame(24, Fasilitas::count());
        $this->assertSame('wifi', Fasilitas::where('nama', 'WiFi')->value('ikon'));
    }

    public function test_seeder_admin_membuat_akun_dari_config(): void
    {
        config(['kostin.admin' => [
            'name' => 'Admin Uji', 'email' => 'uji@kostin.test', 'username' => 'uji', 'password' => 'password-uji',
        ]]);

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'uji@kostin.test')->firstOrFail();
        $this->assertSame('uji', $admin->username);
        $this->assertTrue(Hash::check('password-uji', $admin->password));
    }

    public function test_seeder_admin_menolak_password_kosong(): void
    {
        config(['kostin.admin.email' => 'uji@kostin.test', 'kostin.admin.password' => '']);

        $this->expectException(RuntimeException::class);
        $this->seed(AdminSeeder::class);
    }
}
