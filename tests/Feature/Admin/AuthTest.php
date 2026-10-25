<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@kostin.test',
            'username' => 'admin',
            'password' => 'rahasia-123',
        ]);
    }

    public function test_halaman_login_bisa_dibuka(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Email atau Username');
    }

    public function test_tamu_diarahkan_ke_login_saat_buka_halaman_admin(): void
    {
        foreach (['/admin', '/admin/dasbor', '/admin/datakos', '/admin/datakos/tambah', '/admin/fasilitas'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    public function test_login_dengan_email_berhasil(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['login' => 'admin@kostin.test', 'password' => 'rahasia-123'])
            ->assertRedirect('/admin/dasbor');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_dengan_username_berhasil(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['login' => 'admin', 'password' => 'rahasia-123'])
            ->assertRedirect('/admin/dasbor');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $this->admin();

        $this->from('/admin/login')
            ->post('/admin/login', ['login' => 'admin', 'password' => 'salah'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['login' => 'Email/username atau password salah.']);

        $this->assertGuest();
    }

    public function test_login_gagal_dengan_input_kosong(): void
    {
        $this->post('/admin/login', ['login' => '', 'password' => ''])
            ->assertSessionHasErrors(['login', 'password']);

        $this->assertGuest();
    }

    public function test_login_dikunci_setelah_lima_kali_gagal(): void
    {
        $this->admin();
        RateLimiter::clear('admin|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['login' => 'admin', 'password' => 'salah']);
        }

        // Percobaan ke-6 ditolak walau password benar.
        $this->post('/admin/login', ['login' => 'admin', 'password' => 'rahasia-123'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('login'));
    }

    public function test_admin_yang_sudah_login_diarahkan_dari_halaman_login_ke_dasbor(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/login')
            ->assertRedirect('/admin/dasbor');
    }

    public function test_dasbor_menampilkan_nama_admin_yang_login(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/dasbor')
            ->assertOk()
            ->assertSee('admin@kostin.test');
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_logout_butuh_login(): void
    {
        $this->post('/admin/logout')->assertRedirect('/admin/login');
    }
}
