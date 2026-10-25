<?php

namespace App\Http\Requests\Admin;

use App\Models\Fasilitas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FasilitasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Rapikan spasi ganda supaya "Wi  Fi" dan "Wi Fi" dianggap sama.
        $this->merge([
            'nama_fasilitas' => preg_replace('/\s+/', ' ', trim((string) $this->input('nama_fasilitas'))),
        ]);
    }

    public function rules(): array
    {
        $fasilitas = $this->route('fasilitas');

        return [
            'nama_fasilitas' => [
                'required', 'string', 'max:60',
                // Unik tanpa membedakan huruf besar/kecil.
                function (string $attribute, mixed $value, \Closure $fail) use ($fasilitas) {
                    $sudahAda = Fasilitas::whereRaw('LOWER(nama) = ?', [mb_strtolower($value)])
                        ->when($fasilitas instanceof Fasilitas, fn ($q) => $q->whereKeyNot($fasilitas->id))
                        ->exists();
                    if ($sudahAda) {
                        $fail("Fasilitas \"{$value}\" sudah ada.");
                    }
                },
            ],
            'ikon' => ['nullable', Rule::in(array_keys(Fasilitas::IKON))],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_fasilitas.required' => 'Nama fasilitas wajib diisi.',
            'nama_fasilitas.max' => 'Nama fasilitas maksimal 60 karakter.',
            'ikon.in' => 'Ikon tidak dikenal.',
        ];
    }
}
