<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlusCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_admin_yang_login(): void
    {
        $this->getJson('/admin/plus-code?kode=CR66%2BV3')->assertUnauthorized();
    }

    public function test_kode_pendek_dari_google_maps(): void
    {
        $this->actingAs(User::factory()->create());

        // Nilai acuan dari library resmi openlocationcode (Python): recoverNearest('CR66+V3', kampus).
        $this->getJson('/admin/plus-code?kode='.urlencode('CR66+V3'))
            ->assertOk()
            ->assertJson(['kode' => 'CR66+V3', 'lat' => -6.5878125, 'lng' => 106.8101875, 'presisi_m' => 14]);
    }

    public function test_kode_dengan_teks_alamat_dan_kode_lengkap(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/admin/plus-code?kode='.urlencode('cr64+5m9 Babakan, Bogor Tengah'))
            ->assertOk()
            ->assertJson(['kode' => 'CR64+5M9', 'lat' => -6.5895875, 'lng' => 106.8067344, 'presisi_m' => 3]);

        $this->getJson('/admin/plus-code?kode='.urlencode('6P58CR66+V3'))
            ->assertOk()
            ->assertJson(['lat' => -6.5878125, 'lng' => 106.8101875]);
    }

    public function test_kode_tidak_dikenali_atau_terlalu_jauh_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson('/admin/plus-code?kode=Jl.%20Malabar%20Ujung')
            ->assertStatus(422)
            ->assertJsonPath('pesan', 'Plus Code tidak dikenali. Contoh: CR66+V3');

        // 6P58RR28+2M4 berada di Jakarta, sekitar 43 km dari kampus.
        $this->getJson('/admin/plus-code?kode='.urlencode('6P58RR28+2M4'))
            ->assertStatus(422)
            ->assertJsonPath('pesan', 'Plus Code 6P58RR28+2M4 berada lebih dari 15 km dari kampus. Periksa lagi kodenya.');

        $this->getJson('/admin/plus-code')->assertStatus(422)->assertJsonValidationErrors('kode');
    }

    public function test_form_tambah_memuat_isian_plus_code(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/datakos/tambah')
            ->assertOk()
            ->assertSee('Tempel koordinat atau Plus Code dari Google Maps')
            ->assertSee('URL_PLUS_CODE', false)
            ->assertSee('admin\\/plus-code', false);
    }
}