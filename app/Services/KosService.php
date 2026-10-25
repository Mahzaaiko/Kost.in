<?php

namespace App\Services;

use App\Enums\StatusKos;
use App\Models\FotoKos;
use App\Models\Kos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Logika simpan, hapus, dan ubah status data kos dari panel admin. */
class KosService
{
    private const DISK = 'public';

    /**
     * Simpan kos baru atau perubahan kos lama beserta fasilitas dan fotonya.
     *
     * @param  array<string, mixed>  $atribut  hasil KosRequest::atributKos()
     * @param  list<int>  $fasilitasIds
     * @param  list<UploadedFile>  $fotoBaru
     * @param  list<int>  $hapusFotoIds
     * @param  string|null  $fotoUtama  "lama:{id}" atau "baru:{index}"
     *
     * @throws ValidationException kalau kos Aktif jadi tidak lengkap
     */
    public function simpan(
        Kos $kos,
        array $atribut,
        array $fasilitasIds,
        array $fotoBaru,
        array $hapusFotoIds,
        ?string $fotoUtama,
    ): Kos {
        $fileTersimpan = [];
        $fileDihapus = [];

        try {
            DB::transaction(function () use ($kos, $atribut, $fasilitasIds, $fotoBaru, $hapusFotoIds, $fotoUtama, &$fileTersimpan, &$fileDihapus) {
                $kos->fill($atribut)->save();
                $kos->fasilitas()->sync($fasilitasIds);

                // Hapus foto yang ditandai (file fisiknya dihapus setelah transaksi sukses).
                $dihapus = $kos->foto()->whereIn('id', $hapusFotoIds)->get();
                foreach ($dihapus as $foto) {
                    $fileDihapus[] = $foto->path;
                    $foto->delete();
                }

                // Simpan foto baru.
                $urutan = (int) $kos->foto()->max('urutan');
                $idFotoBaru = [];
                foreach (array_values($fotoBaru) as $i => $file) {
                    $path = $file->store("kos/{$kos->id}", self::DISK);
                    $fileTersimpan[] = $path;
                    $foto = $kos->foto()->create(['path' => $path, 'urutan' => ++$urutan]);
                    $idFotoBaru[$i] = $foto->id;
                }

                $this->aturFotoUtama($kos, $fotoUtama, $idFotoBaru);

                $kos->unsetRelation('foto');
                if ($kos->status === StatusKos::Aktif && ($kurang = $kos->kekuranganUntukAktif()) !== []) {
                    throw ValidationException::withMessages([
                        'status' => 'Kos ini berstatus Aktif, jadi datanya harus lengkap. Masih kurang: '.implode(', ', $kurang).'. Ubah ke Draft dulu kalau memang belum lengkap.',
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Transaksi batal: buang file yang terlanjur diupload.
            Storage::disk(self::DISK)->delete($fileTersimpan);
            throw $e;
        }

        Storage::disk(self::DISK)->delete($fileDihapus);

        return $kos->refresh();
    }

    public function hapus(Kos $kos): void
    {
        $id = $kos->id;
        $kos->delete(); // foto_kos dan fasilitas_kos ikut terhapus lewat cascade
        Storage::disk(self::DISK)->deleteDirectory("kos/{$id}");
    }

    /**
     * Ubah status. Kos hanya boleh Aktif kalau datanya lengkap.
     *
     * @throws ValidationException
     */
    public function ubahStatus(Kos $kos, StatusKos $status): void
    {
        if ($status === StatusKos::Aktif && ($kurang = $kos->kekuranganUntukAktif()) !== []) {
            throw ValidationException::withMessages([
                'status' => "{$kos->nama} belum bisa diaktifkan. Lengkapi dulu: ".implode(', ', $kurang).'.',
            ]);
        }

        $kos->update(['status' => $status]);
    }

    /**
     * Tepat satu foto utama per kos (kalau kos punya foto).
     *
     * @param  array<int, int>  $idFotoBaru  index upload => id foto
     */
    private function aturFotoUtama(Kos $kos, ?string $pilihan, array $idFotoBaru): void
    {
        $idUtama = null;

        if ($pilihan !== null && preg_match('/^(lama|baru):(\d+)$/', $pilihan, $m)) {
            $idUtama = $m[1] === 'lama'
                ? $kos->foto()->whereKey((int) $m[2])->value('id')
                : ($idFotoBaru[(int) $m[2]] ?? null);
        }

        // Pilihan kosong atau fotonya sudah dihapus: pertahankan utama lama, atau pakai foto pertama.
        $idUtama ??= $kos->foto()->where('is_utama', true)->value('id')
            ?? $kos->foto()->orderBy('urutan')->value('id');

        if ($idUtama === null) {
            return;
        }

        FotoKos::where('kos_id', $kos->id)->where('id', '!=', $idUtama)->update(['is_utama' => false]);
        FotoKos::whereKey($idUtama)->update(['is_utama' => true]);
    }
}
