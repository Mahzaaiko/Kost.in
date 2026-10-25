<?php

namespace App\Http\Requests\Admin;

use App\Enums\PeriodeSewa;
use App\Enums\TipeKos;
use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi form Tambah dan Edit data kos.
 *
 * Hanya nama yang wajib. Data lain boleh menyusul (kos tetap Draft),
 * tapi kos yang sudah Aktif tidak boleh disimpan dalam keadaan tidak lengkap.
 */
class KosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Hilangkan pemisah ribuan kalau admin mengetik "700.000".
        if (is_string($this->input('harga'))) {
            $this->merge(['harga' => preg_replace('/[^\d]/', '', $this->input('harga')) ?: null]);
        }
    }

    public function rules(): array
    {
        $kos = $this->kosSaatIni();
        $fotoTersimpan = $kos?->foto()->count() ?? 0;
        $maksFotoBaru = max(0, (int) config('kostin.foto.maks_jumlah') - $fotoTersimpan + count((array) $this->input('hapus_foto', [])));

        return [
            'nama_kos' => ['required', 'string', 'max:150'],
            'no_telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s().]{8,20}$/'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'tipe_kos' => ['nullable', Rule::in(TipeKos::labels())],
            'periode_sewa' => ['nullable', Rule::in(PeriodeSewa::labels())],
            'harga' => ['nullable', 'integer', 'between:100000,20000000'],
            'deskripsi' => ['nullable', 'string', 'max:3000'],

            'fasilitas' => ['nullable', 'array'],
            'fasilitas.*' => ['integer', 'distinct', 'exists:fasilitas,id'],

            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],

            'foto' => ['nullable', 'array', 'max:'.$maksFotoBaru],
            'foto.*' => ['file', 'image', 'mimes:jpeg,jpg,png', 'max:'.config('kostin.foto.maks_kb')],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['integer', Rule::exists('foto_kos', 'id')->where('kos_id', $kos?->id ?? 0)],
            'foto_utama' => ['nullable', 'string', 'regex:/^(lama|baru):\d+$/'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['latitude', 'longitude'])) {
                    return;
                }

                $titik = $this->koordinat();
                if ($titik === null) {
                    return;
                }

                $batasKm = (int) config('kostin.radius_validasi_km');
                if ($titik->jarakMeterKe(Koordinat::kampus()) > $batasKm * 1000) {
                    $validator->errors()->add(
                        'latitude',
                        "Titik lokasi lebih dari {$batasKm} km dari kampus. Cek lagi, mungkin latitude dan longitude tertukar.",
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'nama_kos.required' => 'Nama kos wajib diisi.',
            'nama_kos.max' => 'Nama kos maksimal 150 karakter.',
            'no_telepon.regex' => 'No. telepon hanya boleh berisi angka, spasi, +, -, atau tanda kurung (8 sampai 20 karakter).',
            'tipe_kos.in' => 'Tipe kos tidak dikenal.',
            'periode_sewa.in' => 'Periode minimal sewa tidak dikenal.',
            'harga.integer' => 'Harga harus berupa angka.',
            'harga.between' => 'Harga per bulan harus antara Rp100.000 dan Rp20.000.000.',
            'fasilitas.*.exists' => 'Ada fasilitas yang tidak dikenal.',
            'latitude.required_with' => 'Latitude dan longitude harus diisi bersamaan.',
            'longitude.required_with' => 'Latitude dan longitude harus diisi bersamaan.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'foto.max' => 'Jumlah foto maksimal '.config('kostin.foto.maks_jumlah').' per kos.',
            'foto.*.image' => 'File foto harus berupa gambar.',
            'foto.*.mimes' => 'Foto harus berformat JPG atau PNG.',
            'foto.*.max' => 'Ukuran tiap foto maksimal 3 MB.',
            'hapus_foto.*.exists' => 'Foto yang akan dihapus tidak ditemukan.',
            'foto_utama.regex' => 'Pilihan foto utama tidak valid.',
        ];
    }

    /** Data yang sudah divalidasi, dalam bentuk kolom tabel kos. */
    public function atributKos(): array
    {
        return [
            'nama' => trim((string) $this->validated('nama_kos')),
            'no_telepon' => $this->validated('no_telepon') ?: null,
            'alamat' => $this->validated('alamat') ?: null,
            'tipe' => TipeKos::dariLabel($this->validated('tipe_kos')),
            'periode_minimal_bulan' => PeriodeSewa::dariLabel($this->validated('periode_sewa'))?->value,
            'harga_per_bulan' => $this->validated('harga') !== null ? (int) $this->validated('harga') : null,
            'deskripsi' => $this->validated('deskripsi') ?: null,
            'lokasi' => $this->koordinat(),
        ];
    }

    public function koordinat(): ?Koordinat
    {
        $lat = $this->input('latitude');
        $lng = $this->input('longitude');

        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return null;
        }

        return new Koordinat(lat: (float) $lat, lng: (float) $lng);
    }

    private function kosSaatIni(): ?Kos
    {
        $kos = $this->route('kos');

        return $kos instanceof Kos ? $kos : null;
    }
}
