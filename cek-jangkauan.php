<?php
// Cek jangkauan jalan kaki terhadap rute tiap kos.
// Jalankan: php artisan tinker --execute="require 'cek-jangkauan.php';"
use App\Enums\StatusKos;
use App\Models\Kos;
use App\Services\Routing\JangkauanJalanKaki;
use App\Services\Routing\RuteService;
use Illuminate\Support\Facades\DB;

$pita = collect(app(JangkauanJalanKaki::class)->dariKampus()['features'])->sortBy('properties.menit')->values();
$meterPerMenit = config('kostin.routing.kecepatan_jalan_kmh') * 1000 / 60;
$kampus = App\Support\Koordinat::kampus();
$cocok = 0; $total = 0;
foreach (Kos::where('status', StatusKos::Aktif)->whereNotNull('lokasi')->orderBy('nama')->get() as $kos) {
    $r = app(RuteService::class)->untukKos($kos);
    if (! $r['tersedia']) { continue; }
    $p = $kos->lokasi;
    $lurus = DB::scalar('SELECT ST_Distance(ST_MakePoint(?, ?)::geography, ST_MakePoint(?, ?)::geography)', [$p->lng, $p->lat, $kampus->lng, $kampus->lat]);
    $masuk = $pita->first(fn ($f) => DB::scalar('SELECT ST_Contains(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), ST_SetSRID(ST_MakePoint(?, ?), 4326))', [json_encode($f['geometry']), $p->lng, $p->lat]));
    $harapan = $pita->first(fn ($f) => $f['properties']['menit'] * $meterPerMenit >= $r['panjang_m'] - $r['jarak_ke_jalan_m']);
    $mPita = $masuk['properties']['menit'] ?? null;
    $mHarap = $harapan['properties']['menit'] ?? null;
    // Kos yang rutenya dekat batas pita bisa masuk pita sebelumnya karena area jalan dilebarkan 30 m.
    $jarakBatas = $pita->map(fn ($f) => abs($r['panjang_m'] - $r['jarak_ke_jalan_m'] - $f['properties']['menit'] * $meterPerMenit))->min();
    $status = $mPita === $mHarap ? 'OK' : ($jarakBatas <= 2 * config('kostin.peta.jangkauan_lebar_m') ? 'TEPI' : 'CEK');
    $total++; if ($status !== 'CEK') { $cocok++; }
    printf("%-30s lurus %5d m | rute %5d m | di pita %-6s | seharusnya %-6s %s\n", mb_substr($kos->nama, 0, 30), $lurus, $r['panjang_m'],
        $mPita ? "$mPita mnt" : 'luar', $mHarap ? "$mHarap mnt" : 'luar', $status);
}
echo "\nOK atau TEPI: $cocok dari $total kos (TEPI = rute dekat batas pita, wajar)\n";
