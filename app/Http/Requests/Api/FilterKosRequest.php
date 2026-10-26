<?php

namespace App\Http\Requests\Api;

use App\Enums\TipeKos;
use App\Services\PencarianKosService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterKosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('tipe') === 'semua' || $this->input('tipe') === '') {
            $this->merge(['tipe' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'harga_max' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'jarak_max_km' => ['nullable', 'numeric', 'min:0.1', 'max:'.config('kostin.jarak_maks_km')],
            'tipe' => ['nullable', Rule::enum(TipeKos::class)],
            'fasilitas' => ['nullable', 'array', 'max:30'],
            'fasilitas.*' => ['integer', 'distinct'],
            'q' => ['nullable', 'string', 'max:100'],
            'urut' => ['nullable', Rule::in(PencarianKosService::URUTAN)],
        ];
    }

    public function messages(): array
    {
        return [
            'harga_max.integer' => 'Harga maksimal harus berupa angka.',
            'jarak_max_km.max' => 'Jarak maksimal '.config('kostin.jarak_maks_km').' km.',
            'jarak_max_km.min' => 'Jarak minimal 0,1 km.',
            'tipe' => 'Tipe kos harus putri, putra, atau campur.',
            'urut.in' => 'Urutan harus salah satu dari: '.implode(', ', PencarianKosService::URUTAN).'.',
        ];
    }
}