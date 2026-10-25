<?php

namespace Tests\Feature\Admin;

use App\Models\Kos;
use App\Models\User;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DasborTest extends TestCase
{
    use RefreshDatabase;

    public function test_dasbor_menghitung_statistik_dari_data_asli(): void
    {
        $this->seed(FasilitasSeeder::class);
        Kos::factory()->aktif()->count(3)->create();
        Kos::factory()->count(1)->create();
        Kos::create(['nama' => 'Kos Belum Terpetakan']);

        $response = $this->actingAs(User::factory()->create())->get('/admin/dasbor')->assertOk();

        $stats = collect($response->viewData('stats'))->keyBy('label');
        $this->assertSame(5, $stats['Total Kos']['value']);
        $this->assertSame('+5 bulan ini', $stats['Total Kos']['note']);
        $this->assertSame(3, $stats['Kos Aktif']['value']);
        $this->assertSame('60,0% dari total', $stats['Kos Aktif']['note']);
        $this->assertSame(24, $stats['Data Fasilitas']['value']);
        $this->assertSame(4, $stats['Lokasi Terdaftar']['value']);
        $this->assertSame('1 kos belum terpetakan', $stats['Lokasi Terdaftar']['note']);

        $this->assertCount(4, $response->viewData('kosTerbaru'));
        $this->assertCount(4, $response->viewData('titikKos'));
        $response->assertSee('Kos Belum Terpetakan')->assertDontSee('Kos Putri Melati');
    }

    public function test_dasbor_kosong_tidak_error(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/dasbor')
            ->assertOk()
            ->assertSee('Belum ada data kos');
    }
}
