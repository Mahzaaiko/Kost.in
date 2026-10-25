<?php

namespace App\Http\Requests\Admin;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Batas percobaan login gagal per kombinasi login + IP, per menit. */
    private const MAKS_PERCOBAAN = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Email atau username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Coba login dengan email atau username. Gagal berulang akan dikunci sementara.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->pastikanTidakTerkunci();

        $login = trim((string) $this->input('login'));
        $kolom = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$kolom => $login, 'password' => (string) $this->input('password')], $this->boolean('remember'))) {
            RateLimiter::hit($this->kunciThrottle(), 60);

            throw ValidationException::withMessages([
                'login' => 'Email/username atau password salah.',
            ]);
        }

        RateLimiter::clear($this->kunciThrottle());
    }

    private function pastikanTidakTerkunci(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciThrottle(), self::MAKS_PERCOBAAN)) {
            return;
        }

        event(new Lockout($this));

        $detik = RateLimiter::availableIn($this->kunciThrottle());

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.",
        ]);
    }

    private function kunciThrottle(): string
    {
        return Str::transliterate(Str::lower(trim((string) $this->input('login'))).'|'.$this->ip());
    }
}
