<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RekamanJalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => trim((string) $this->input('nama')) ?: null,
            'catatan' => trim((string) $this->input('catatan')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            // Ekstensi dicek dari nama file karena GPX sering terbaca sebagai text/xml atau application/octet-stream.
            'berkas' => ['required', 'file', 'extensions:gpx,geojson,json', 'max:'.(int) config('kostin.routing.berkas_survei_maks_kb')],
            'nama' => ['nullable', 'string', 'max:150'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'berkas.required' => 'Pilih file rekaman GPX atau GeoJSON.',
            'berkas.extensions' => 'File harus berformat .gpx, .geojson, atau .json.',
            'berkas.max' => 'Ukuran file maksimal '.((int) config('kostin.routing.berkas_survei_maks_kb') / 1024).' MB.',
            'nama.max' => 'Nama gang maksimal 150 karakter.',
            'catatan.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}