<?php

namespace App\Casts;

use App\Support\Koordinat;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cast kolom geography(Point,4326) <-> App\Support\Koordinat.
 *
 * @implements CastsAttributes<Koordinat|null, Koordinat|null>
 */
class GeographyPoint implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Koordinat
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Koordinat) {
            return $value;
        }

        // Setelah set() dan sebelum model di-refresh, atribut masih berupa
        // ekspresi SQL yang kita buat sendiri. Baca ulang titiknya dari EWKT.
        if ($value instanceof Expression) {
            $sql = (string) $value->getValue(DB::connection($model->getConnectionName())->getQueryGrammar());
            if (preg_match('/POINT\((-?[\d.]+) (-?[\d.]+)\)/', $sql, $m)) {
                return new Koordinat(lat: (float) $m[2], lng: (float) $m[1]);
            }

            return null;
        }

        return Koordinat::fromEwkbHex((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Koordinat) {
            throw new InvalidArgumentException("Atribut {$key} harus berupa App\\Support\\Koordinat atau null.");
        }

        // Nilai EWKT dibentuk dari float yang sudah divalidasi Koordinat,
        // jadi aman ditulis sebagai ekspresi SQL.
        return DB::raw("ST_GeogFromText('{$value->toEwkt()}')");
    }
}
