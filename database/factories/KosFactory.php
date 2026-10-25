<?php

namespace Database\Factories;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Data palsu untuk testing saja, tidak dipakai sebagai data aplikasi.
 *
 * @extends Factory<Kos>
 */
class KosFactory extends Factory
{
    protected $model = Kos::class;

    public function definition(): array
    {
        $kampus = Koordinat::kampus();

        return [
            'nama' => 'Kos '.fake()->unique()->lastName(),
            'nama_pemilik' => fake()->name(),
            'no_telepon' => '08'.fake()->numerify('##########'),
            'alamat' => fake()->streetAddress(),
            'deskripsi' => fake()->sentence(),
            'tipe' => fake()->randomElement(TipeKos::cases()),
            'harga_per_bulan' => fake()->numberBetween(5, 30) * 100000,
            'periode_minimal_bulan' => fake()->randomElement([1, 3, 6, 12]),
            // Sebar acak sekitar 2 km dari kampus.
            'lokasi' => new Koordinat(
                lat: $kampus->lat + fake()->randomFloat(5, -0.018, 0.018),
                lng: $kampus->lng + fake()->randomFloat(5, -0.018, 0.018),
            ),
            'status' => StatusKos::Draft,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn () => ['status' => StatusKos::Aktif]);
    }
}
