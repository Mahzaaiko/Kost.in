@extends('layouts.pengguna')

@section('title', 'Kost.in | Temukan Kos Terbaik di Sekitar SV IPB')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        html { scroll-padding-top: 76px; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .leaflet-container { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Peta ilustrasi wilayah Kota Bogor */
        .kec { fill: #ffffff; stroke: #d1c7bc; stroke-width: 1.2; stroke-linejoin: round; transition: fill 0.2s; }
        .kec:hover { fill: #f5ede6; }
        .kec-tengah { fill: #ead5c9; }
        .pin-kos { fill: #fff; stroke: #B46C54; stroke-width: 4; }
        .pin-kampus { fill: #fff; stroke: #1c1917; stroke-width: 4; }
        .lbl { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 10px; fill: #1c1917; paint-order: stroke; stroke: #fff; stroke-width: 2.5px; stroke-linejoin: round; }
    </style>
@endpush

@section('content')
    @php
        // Titik pusat: Kampus SV IPB
        $kampus = ['nama' => 'Kampus SV IPB', 'lat' => -6.5886, 'lng' => 106.8032];

        // Jarak garis lurus (meter) dengan rumus haversine
        $hitungJarak = function ($lat1, $lng1, $lat2, $lng2) {
            $r = 6371000;
            $dLat = deg2rad($lat2 - $lat1);
            $dLng = deg2rad($lng2 - $lng1);
            $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
            return 2 * $r * asin(sqrt($a));
        };
        $formatJarak = fn ($m) => $m < 1000 ? round($m / 10) * 10 . ' m' : number_format($m / 1000, 1, ',', '.') . ' km';

        $kosList = collect([
            ['nama' => 'Kos Putri Melati',    'tipe' => 'Putri',  'harga' => 700000, 'fasilitas' => ['Wi-Fi', 'AC', 'Kamar mandi dalam'], 'foto' => 'photo-1554995207-c18c203602cb', 'lat' => -6.5990, 'lng' => 106.8085],
            ['nama' => 'Kos Putra Mandiri',   'tipe' => 'Putra',  'harga' => 550000, 'fasilitas' => ['Wi-Fi', 'Kamar mandi luar'],       'foto' => 'photo-1505691938895-1758d7feb511', 'lat' => -6.5850, 'lng' => 106.8000],
            ['nama' => 'Kos Griya Sejahtera', 'tipe' => 'Campur', 'harga' => 950000, 'fasilitas' => ['Wi-Fi', 'AC', 'Parkir motor'],    'foto' => 'photo-1598928506311-c55ded91a20c', 'lat' => -6.5800, 'lng' => 106.8150],
            ['nama' => 'Kos Putri Anggrek',   'tipe' => 'Putri',  'harga' => 800000, 'fasilitas' => ['Wi-Fi', 'Laundry'],                'foto' => null, 'lat' => -6.6050, 'lng' => 106.7950],
            ['nama' => 'Kos Putra Harapan',   'tipe' => 'Putra',  'harga' => 600000, 'fasilitas' => ['Wi-Fi', 'Parkir'],                 'foto' => null, 'lat' => -6.5700, 'lng' => 106.8030],
            ['nama' => 'Kos Campur Bersama',  'tipe' => 'Campur', 'harga' => 650000, 'fasilitas' => ['Wi-Fi', 'Dapur'],                  'foto' => null, 'lat' => -6.6150, 'lng' => 106.8100],
        ])->map(function ($k) use ($kampus, $hitungJarak, $formatJarak) {
            $k['jarak_m'] = (int) round($hitungJarak($kampus['lat'], $kampus['lng'], $k['lat'], $k['lng']));
            $k['jarak'] = $formatJarak($k['jarak_m']);
            $k['harga_fmt'] = 'Rp' . number_format($k['harga'], 0, ',', '.');
            return $k;
        });
        $kosKartu = $kosList->filter(fn ($k) => $k['foto'])->take(3);
        $warnaBadge = ['Putri' => 'text-pink-700', 'Putra' => 'text-blue-700', 'Campur' => 'text-purple-700'];
    @endphp

    {{-- ================= HERO SECTION (Bersih & Profesional) ================= --}}
    <section class="bg-white border-b border-stone-200 py-16 lg:py-24">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-5 sm:px-6 lg:grid-cols-12 lg:gap-8">
            {{-- Teks Kiri --}}
            <div class="lg:col-span-7">
                <span class="inline-block rounded-full bg-[#fbf5f3] px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-[#B46C54]">
                    Platform Pencarian Kos SV IPB
                </span>

                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-stone-900 sm:text-5xl lg:text-6xl leading-[1.15]">
                    Temukan Kos Terbaik di Sekitar <span class="text-[#B46C54]">Sekolah Vokasi IPB</span>
                </h1>

                <p class="mt-6 text-base leading-relaxed text-stone-600 max-w-xl">
                    Cari hunian yang terasa seperti rumah—dekat kampus, sesuai budget, dan punya fasilitas lengkap. Kost.in membantu mahasiswa merantau tanpa ribet dan tanpa biaya admin.
                </p>

                {{-- Kolom Pencarian Cepat --}}
                <form action="{{ route('pengguna.jelajahKos') }}" method="GET" class="mt-8 flex max-w-xl overflow-hidden rounded-xl border border-stone-300 bg-white shadow-sm focus-within:border-[#B46C54]">
                    <input type="text" name="q" placeholder="Cari nama kos, fasilitas, atau area..."
                           class="min-w-0 flex-1 bg-transparent px-4 py-3.5 text-sm text-stone-800 placeholder-stone-400 focus:outline-none">
                    <button type="submit" aria-label="Cari kos" class="flex items-center gap-2 bg-[#B46C54] px-6 text-sm font-bold text-white transition hover:bg-[#955844]">
                        Cari
                    </button>
                </form>

                <div class="mt-6 flex flex-wrap gap-6 text-sm text-stone-500">
                    <span class="flex items-center gap-1.5 font-medium text-stone-700">✓ 128+ Kos Terdaftar</span>
                    <span class="flex items-center gap-1.5 font-medium text-stone-700">✓ 100% Gratis Tanpa Biaya Admin</span>
                </div>
            </div>

            {{-- Gambar Hero Kanan (Clean Card) --}}
            <div class="lg:col-span-5">
                <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white p-2 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=80" alt="Suasana Kos" class="aspect-[4/3] w-full rounded-xl object-cover">
                    <div class="p-4">
                        <p class="text-xs font-bold text-[#B46C54] uppercase tracking-wider">Rekomendasi Unggulan</p>
                        <h3 class="mt-1 font-bold text-stone-900">Kos Nyaman Dekat Kampus</h3>
                        <p class="mt-1 text-xs text-stone-500">Akses berjalan kaki ke area kuliah Sekolah Vokasi IPB.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= SECTION: PETA WILAYAH KOTA BOGOR (TERITORIAL) ================= --}}
    @php
        // Proyeksi peta ilustrasi Kota Bogor
        $pr = ['minLng' => 106.73499200028164, 'maxLat' => -6.51080200031123, 'k' => 0.9933824040959512, 's' => 3080.8790014777005, 'pad' => 20];
        $px = fn ($lng) => ($lng - $pr['minLng']) * $pr['k'] * $pr['s'] + $pr['pad'];
        $py = fn ($lat) => ($pr['maxLat'] - $lat) * $pr['s'] + $pr['pad'];
        $mPx = fn ($m) => $m / 111320 * $pr['s'];
    @endphp
    <section class="bg-[#F9F7F4] py-16 lg:py-24 border-b border-stone-200">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            <div class="grid items-center gap-12 lg:grid-cols-12">
                {{-- Kiri: Penjelasan Wilayah --}}
                <div class="lg:col-span-6">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#B46C54]">Wilayah Cakupan</span>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-stone-900 sm:text-4xl leading-tight">
                        Eksplorasi Wilayah &amp; Zona Kampus SV IPB
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-stone-600">
                        Peta teritorial Kota Bogor memetakan wilayah kecamatan di sekitar pusat pendidikan. Titik pusat berada di Sekolah Vokasi IPB, memudahkan kamu menghitung jarak hunian secara presisi.
                    </p>
                    
                    <div class="mt-8 space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#B46C54] text-white text-xs font-bold">1</div>
                            <div>
                                <h4 class="text-sm font-bold text-stone-900">Kecamatan Bogor Tengah</h4>
                                <p class="mt-0.5 text-xs text-stone-500">Area utama tempat berdirinya kampus dan fasilitas penunjang.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#B46C54] text-white text-xs font-bold">2</div>
                            <div>
                                <h4 class="text-sm font-bold text-stone-900">Radius Jarak Terjangkau</h4>
                                <p class="mt-0.5 text-xs text-stone-500">Pilihan kos mulai dari radius 500 meter hingga 3 kilometer.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('pengguna.jelajahKos') }}" class="inline-flex items-center gap-2 rounded-xl bg-[#1c1917] px-6 py-3 text-sm font-semibold text-white transition hover:bg-stone-800">
                            Buka Peta Interaktif Lengkap
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Kanan: Ilustrasi Peta SVG Kota Bogor --}}
                <div class="lg:col-span-6 flex justify-center">
                    <div class="w-full max-w-[380px] rounded-2xl bg-white p-6 border border-stone-200 shadow-sm">
                        <p class="text-xs font-bold text-stone-500 mb-4 text-center">Peta Teritorial Kecamatan Bogor</p>
                        <svg viewBox="-10 0 408 560" class="mx-auto h-auto w-full" role="img" aria-label="Peta wilayah Kota Bogor">
                            <path class="kec" d="M235.3,328.2 L247.5,342.7 L276.5,366.8 L287.3,377.7 L292.1,384.6 L297.8,397.8 L300.7,401.3 L314.3,410.9 L315.1,410.0 L318.7,412.9 L324.6,414.2 L334.7,422.7 L337.9,426.8 L337.0,428.0 L341.2,431.6 L335.9,436.0 L337.2,438.0 L340.2,440.6 L347.9,443.7 L349.6,448.9 L360.2,461.5 L365.1,462.6 L357.2,484.2 L362.3,505.7 L366.2,529.7 L357.7,532.3 L355.9,535.5 L351.7,537.6 L342.5,539.8 L340.7,540.0 L339.6,537.7 L327.7,536.9 L323.7,534.1 L319.4,534.8 L316.9,537.6 L312.1,538.8 L304.6,535.3 L302.0,532.6 L299.9,532.6 L299.2,529.7 L292.6,525.7 L287.2,525.2 L286.1,520.6 L277.2,516.2 L277.3,510.8 L275.2,503.2 L275.6,499.3 L270.3,498.4 L268.9,496.8 L268.5,493.6 L269.7,489.9 L267.0,486.4 L266.8,483.7 L259.9,480.4 L258.5,476.7 L254.0,475.0 L250.2,470.0 L245.9,468.3 L244.7,456.3 L249.1,451.3 L251.3,446.0 L250.6,438.0 L248.7,437.2 L245.4,444.8 L242.1,447.6 L239.6,452.0 L238.1,452.0 L237.5,454.6 L230.9,454.2 L232.6,452.6 L235.0,445.7 L229.4,442.1 L229.0,443.3 L226.9,442.2 L223.1,443.8 L217.1,435.5 L209.9,442.5 L203.3,446.8 L200.8,453.7 L197.5,456.0 L196.0,459.9 L190.1,458.5 L189.4,464.7 L186.2,470.5 L179.0,477.3 L174.7,478.0 L167.0,476.3 L161.8,471.7 L156.5,469.1 L142.3,467.1 L132.2,460.2 L141.9,451.9 L151.9,447.3 L161.4,436.2 L166.8,433.4 L174.4,414.8 L173.8,409.0 L174.2,406.4 L176.5,404.3 L176.7,399.5 L179.3,395.2 L183.5,392.5 L184.7,386.6 L189.2,380.4 L184.6,372.5 L180.5,371.9 L181.2,370.4 L180.3,369.8 L178.9,371.5 L177.2,371.1 L177.5,368.2 L176.7,365.1 L174.9,363.6 L175.0,361.2 L171.0,359.8 L170.9,352.9 L168.5,351.9 L169.1,344.2 L166.9,341.9 L166.3,339.1 L162.8,336.0 L163.0,332.5 L174.2,326.0 L178.6,321.3 L181.2,320.8 L182.3,322.6 L186.4,324.0 L187.3,326.2 L185.8,329.8 L189.1,329.9 L187.9,332.8 L188.8,334.3 L186.8,335.3 L186.3,339.6 L185.1,340.8 L186.8,343.1 L183.9,347.9 L179.5,352.1 L181.5,351.1 L181.9,355.2 L183.7,351.7 L184.0,355.2 L191.7,355.0 L203.2,352.8 L203.2,350.5 L208.2,346.4 L203.8,341.8 L205.7,340.7 L202.5,336.5 L201.9,328.6 L197.8,321.9 L197.4,317.5 L201.2,312.4 L208.0,316.6 L211.7,316.5 L212.4,318.2 L215.9,319.8 L216.6,322.1 L217.9,321.4 L219.9,322.9 L221.2,321.6 L222.4,324.3 L225.4,325.1 L227.4,330.3 L230.7,331.0 L232.2,328.5 L234.2,329.5 L235.3,328.2Z"><title>Kec. Bogor Selatan</title></path>
                            <path class="kec" d="M316.2,290.3 L315.0,303.1 L317.4,338.4 L337.9,390.1 L345.5,402.4 L360.2,421.3 L365.6,430.8 L367.8,438.6 L368.3,446.2 L365.1,462.6 L360.2,461.5 L349.6,448.9 L347.9,443.7 L340.2,440.6 L337.2,438.0 L335.9,436.0 L341.2,431.6 L337.0,428.0 L337.9,426.8 L334.7,422.7 L324.6,414.2 L318.7,412.9 L315.1,410.0 L314.3,410.9 L300.7,401.3 L297.8,397.8 L292.1,384.6 L287.3,377.7 L276.5,366.8 L247.5,342.7 L240.0,332.7 L232.2,325.4 L238.7,323.4 L240.6,320.2 L231.6,311.4 L225.8,301.8 L234.2,299.1 L240.2,308.6 L273.1,286.9 L275.5,307.9 L287.2,310.4 L293.8,309.7 L296.2,311.1 L302.5,307.7 L303.0,305.9 L299.8,303.5 L297.6,297.2 L295.4,296.1 L293.8,292.1 L295.1,283.5 L308.2,283.1 L311.9,284.1 L316.2,290.3Z"><title>Kec. Bogor Timur</title></path>
                            <path class="kec kec-tengah" d="M216.4,233.0 L212.1,237.1 L213.2,241.9 L215.9,242.2 L216.8,244.7 L218.8,243.9 L218.2,247.1 L220.8,247.5 L223.7,243.7 L243.1,248.8 L244.5,250.1 L253.7,250.6 L253.3,256.3 L246.9,263.9 L246.4,269.2 L263.6,269.6 L266.7,268.3 L266.2,273.4 L268.6,276.7 L266.5,290.5 L240.2,308.6 L234.2,299.1 L225.8,301.8 L231.6,311.4 L240.6,320.2 L238.7,323.4 L232.2,325.4 L235.3,328.2 L234.2,329.5 L232.2,328.5 L230.7,331.0 L227.4,330.3 L225.4,325.1 L222.4,324.3 L221.2,321.6 L219.9,322.9 L217.9,321.4 L216.6,322.1 L215.9,319.8 L212.4,318.2 L211.7,316.5 L208.0,316.6 L201.2,312.4 L197.4,317.5 L197.8,321.9 L194.7,317.6 L196.0,314.5 L195.9,308.0 L190.6,308.3 L193.3,301.6 L182.9,298.3 L181.0,294.9 L183.9,293.5 L181.7,290.2 L174.9,289.3 L169.5,293.0 L165.2,290.9 L164.6,288.9 L166.9,282.8 L165.1,280.1 L171.5,272.3 L171.2,270.6 L167.5,270.2 L160.9,273.2 L157.5,270.1 L156.1,266.2 L157.2,262.6 L167.0,253.8 L168.3,261.6 L169.5,260.2 L173.6,259.5 L176.6,264.8 L180.9,262.3 L181.3,212.5 L184.8,214.1 L184.8,217.9 L194.0,217.4 L194.4,222.2 L191.3,229.0 L193.5,232.0 L195.3,238.3 L192.8,243.0 L196.4,242.2 L197.9,239.4 L204.1,237.6 L203.6,235.8 L206.3,234.8 L206.6,231.9 L208.5,229.6 L216.4,233.0Z"><title>Kec. Bogor Tengah (Pusat Kampus)</title></path>
                            <path class="kec" d="M181.3,212.5 L180.9,262.3 L176.6,264.8 L173.6,259.5 L169.5,260.2 L168.3,261.6 L167.0,253.8 L157.2,262.6 L156.4,267.4 L157.7,270.4 L160.9,273.2 L167.5,270.2 L171.2,270.6 L171.5,272.3 L165.1,280.1 L166.9,282.8 L164.6,288.9 L165.6,291.5 L170.4,293.0 L174.9,289.3 L180.6,289.7 L183.9,293.5 L181.0,294.9 L182.9,298.3 L193.3,301.6 L190.7,308.6 L196.0,308.2 L196.0,314.5 L194.7,317.6 L201.9,328.6 L202.5,336.5 L205.7,340.7 L203.8,341.8 L208.2,346.4 L203.2,350.5 L203.2,352.8 L185.8,355.3 L184.0,355.2 L183.4,351.8 L181.9,355.2 L181.5,351.1 L180.0,352.3 L179.6,351.4 L183.9,347.9 L186.8,343.1 L185.1,340.8 L186.3,339.6 L186.8,335.3 L188.8,334.3 L187.9,332.8 L188.9,329.6 L185.8,329.5 L187.3,326.4 L186.8,324.4 L181.3,322.0 L181.2,320.8 L178.6,321.3 L174.0,326.2 L164.0,332.1 L157.4,334.4 L158.0,330.1 L156.2,327.7 L155.1,317.8 L143.8,315.4 L142.9,309.5 L144.1,307.2 L141.4,303.8 L141.5,299.8 L139.6,298.5 L141.2,296.2 L139.0,296.2 L138.6,293.0 L136.4,293.1 L135.0,291.1 L131.7,290.9 L129.9,285.2 L129.0,284.4 L127.9,285.7 L127.2,284.5 L127.7,281.8 L124.2,280.4 L119.7,281.3 L116.7,278.7 L116.1,269.8 L114.8,266.6 L112.7,265.2 L112.9,261.2 L108.2,255.7 L111.0,255.2 L105.5,247.4 L104.9,244.0 L106.1,242.8 L103.0,242.3 L101.4,240.1 L98.8,240.4 L97.6,237.2 L93.7,235.9 L91.9,234.0 L90.8,230.2 L86.5,229.1 L85.5,224.8 L82.8,224.2 L81.7,222.4 L75.9,220.8 L70.8,213.3 L65.4,209.6 L56.7,210.9 L56.4,213.1 L53.8,213.5 L52.6,215.9 L50.4,215.7 L49.2,213.7 L48.1,218.4 L50.1,220.6 L48.1,221.9 L42.9,221.3 L38.5,218.9 L37.1,215.5 L37.3,212.2 L32.3,209.7 L30.6,205.6 L40.2,202.6 L42.3,197.1 L36.6,196.9 L37.8,193.0 L35.0,193.8 L35.0,190.4 L24.7,191.3 L21.3,188.0 L20.0,171.0 L22.0,170.2 L22.2,154.8 L23.5,153.2 L29.2,155.7 L28.4,153.4 L26.2,152.6 L25.4,149.2 L27.3,148.6 L29.5,144.4 L30.2,142.4 L28.1,143.3 L27.8,139.7 L32.6,136.1 L34.0,128.0 L36.3,122.1 L37.6,121.5 L37.9,118.7 L48.1,121.6 L56.1,121.2 L57.1,125.6 L60.5,129.0 L59.9,134.0 L61.6,134.4 L65.2,132.3 L67.6,140.2 L69.8,142.7 L72.7,144.1 L76.2,143.4 L80.4,135.4 L82.5,136.8 L84.5,145.4 L87.4,146.4 L89.3,145.1 L89.9,141.9 L94.3,139.1 L93.0,134.8 L96.2,137.5 L95.4,138.5 L98.8,140.3 L97.9,131.9 L104.0,132.1 L110.7,128.0 L112.3,121.2 L109.1,115.4 L109.8,112.0 L108.6,107.1 L109.9,92.7 L110.8,90.3 L111.7,90.8 L113.2,92.3 L112.5,95.8 L113.9,98.1 L112.6,99.9 L113.0,101.9 L109.8,104.3 L109.9,106.4 L111.5,109.4 L115.6,111.1 L118.5,110.9 L120.8,113.1 L125.7,125.6 L125.4,128.8 L129.2,131.3 L132.7,140.9 L134.4,143.5 L138.1,145.8 L137.5,147.0 L139.2,147.7 L138.9,152.0 L143.7,160.3 L143.4,166.2 L145.8,173.6 L144.9,179.8 L146.7,180.9 L148.2,184.7 L154.0,187.7 L156.8,200.3 L161.6,206.2 L170.7,222.9 L173.1,212.3 L181.3,212.5Z"><title>Kec. Bogor Barat</title></path>
                            <path class="kec" d="M316.2,290.3 L311.9,284.1 L308.2,283.1 L295.1,283.5 L293.8,292.1 L295.4,296.1 L297.6,297.2 L299.8,303.5 L302.7,304.9 L302.5,307.7 L296.2,311.1 L293.8,309.7 L287.2,310.4 L275.5,307.9 L273.1,286.9 L266.5,290.5 L268.6,277.2 L266.2,273.4 L266.7,268.3 L263.6,269.6 L246.4,269.2 L246.9,263.9 L253.3,256.3 L253.7,250.6 L244.5,250.1 L243.1,248.8 L223.7,243.7 L220.8,247.5 L218.2,247.1 L218.8,243.9 L216.8,244.7 L215.9,242.2 L213.2,241.9 L212.5,236.5 L217.0,232.4 L220.8,230.9 L221.2,227.4 L225.1,220.5 L231.1,218.3 L232.1,215.2 L241.1,204.6 L239.8,198.8 L242.6,193.0 L240.9,181.2 L248.0,171.3 L243.4,161.1 L236.1,156.7 L233.9,145.8 L231.2,145.9 L223.4,140.9 L221.0,131.2 L221.5,127.7 L225.0,121.4 L224.5,119.8 L221.4,118.1 L235.7,109.8 L235.6,126.7 L241.4,126.5 L246.2,128.6 L246.4,127.0 L248.0,127.3 L248.0,124.1 L251.2,119.3 L251.5,114.4 L253.8,107.9 L262.1,105.7 L271.4,105.9 L275.2,104.2 L273.2,114.8 L274.6,118.0 L287.7,118.4 L289.0,121.1 L289.5,118.0 L291.0,117.6 L292.5,119.6 L292.9,126.7 L293.8,127.1 L297.6,122.2 L299.1,126.2 L298.3,132.7 L301.3,136.9 L307.9,141.2 L310.9,154.1 L309.3,157.7 L309.3,160.8 L311.5,164.7 L310.5,173.3 L318.0,180.8 L320.0,188.5 L322.9,189.8 L325.6,196.4 L328.8,217.6 L330.9,219.6 L332.7,226.6 L335.6,221.5 L335.9,218.0 L340.1,217.0 L316.2,290.3Z"><title>Kec. Bogor Utara</title></path>
                            
                            {{-- Titik Kampus SV IPB di Peta --}}
                            <circle class="pin-kampus" cx="{{ round($px(106.8032), 1) }}" cy="{{ round($py(-6.5886), 1) }}" r="6"/>
                            <text class="lbl" x="{{ round($px(106.8032) + 10, 1) }}" y="{{ round($py(-6.5886) + 4, 1) }}">SV IPB</text>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= DAFTAR KOS REKOMENDASI ================= --}}
    <section id="kos" class="mx-auto max-w-6xl px-5 py-16 sm:px-6 lg:py-24">
        <div class="flex items-end justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-[#B46C54]">Pilihan Populer</span>
                <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-stone-900 sm:text-3xl">Kos yang sering dilihat mahasiswa</h2>
                <p class="mt-2 text-sm text-stone-600">Pilihan kos dekat kampus dengan fasilitas lengkap dan harga terjangkau.</p>
            </div>
            <a href="{{ route('pengguna.jelajahKos') }}" class="hidden shrink-0 items-center gap-1 text-sm font-semibold text-[#B46C54] hover:underline sm:flex">
                Lihat semua kos &rarr;
            </a>
        </div>

        <div class="no-scrollbar -mx-5 mt-10 flex snap-x snap-mandatory gap-5 overflow-x-auto px-5 pb-2 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 lg:grid-cols-3">
            @foreach ($kosKartu as $kos)
                <article class="group flex w-[85%] shrink-0 snap-start flex-col overflow-hidden rounded-xl border border-stone-200 bg-white transition hover:border-stone-300 sm:w-auto shadow-sm">
                    <div class="aspect-[4/3] overflow-hidden bg-stone-100">
                        <img src="https://images.unsplash.com/{{ $kos['foto'] }}?auto=format&fit=crop&w=800&q=80" alt="{{ $kos['nama'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="font-bold text-stone-900">{{ $kos['nama'] }}</h3>
                            <span class="shrink-0 text-xs font-bold uppercase tracking-wide {{ $warnaBadge[$kos['tipe']] ?? 'text-stone-600' }}">{{ $kos['tipe'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-stone-500">{{ $kos['jarak'] }} dari kampus</p>
                        <p class="mt-3 text-xs text-stone-500">{{ implode(' · ', $kos['fasilitas']) }}</p>

                        <div class="mt-5 flex items-center justify-between border-t border-stone-100 pt-4">
                            <p class="font-bold text-stone-900 text-sm">{{ $kos['harga_fmt'] }} <span class="text-xs font-normal text-stone-500">/ bln</span></p>
                            <a href="{{ route('pengguna.jelajahKos') }}" class="text-xs font-bold text-[#B46C54] hover:underline">Lihat Detail</a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ================= PETA INTERAKTIF SECTION ================= --}}
    <section id="peta" class="border-y border-stone-200 bg-white py-16 lg:py-24">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-12 items-center">
                <div class="lg:col-span-5">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#B46C54]">Radius Jarak</span>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl">Seberapa dekat kos pilihanmu?</h2>
                    <p class="mt-3 text-sm text-stone-600 leading-relaxed">
                        Gunakan filter radius pada peta interaktif di samping untuk melihat persebaran kos mulai dari radius 500 meter hingga 3 kilometer dari Kampus Sekolah Vokasi IPB.
                    </p>

                    <div class="mt-8">
                        <a href="{{ route('pengguna.jelajahKos') }}" class="inline-block rounded-xl bg-[#B46C54] px-6 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#955844]">
                            Buka Peta &amp; Filter Kos
                        </a>
                    </div>
                </div>

                {{-- Peta Leaflet + Radius --}}
                <div class="min-w-0 lg:col-span-7">
                    <div class="mb-3 flex flex-wrap items-center gap-2" id="radius-filter">
                        <span class="mr-1 text-xs font-bold text-stone-600">RADIUS:</span>
                        <button type="button" data-radius="500" class="radius-btn rounded-lg border border-stone-300 bg-white px-3 py-1 text-xs font-semibold text-stone-700 transition">500 m</button>
                        <button type="button" data-radius="1000" class="radius-btn rounded-lg border border-stone-300 bg-white px-3 py-1 text-xs font-semibold text-stone-700 transition">1 km</button>
                        <button type="button" data-radius="2000" class="radius-btn rounded-lg border border-stone-300 bg-white px-3 py-1 text-xs font-semibold text-stone-700 transition">2 km</button>
                        <button type="button" data-radius="3000" class="radius-btn rounded-lg border border-stone-300 bg-white px-3 py-1 text-xs font-semibold text-stone-700 transition">3 km</button>
                    </div>

                    <div id="peta-beranda" class="relative isolate z-0 h-[320px] overflow-hidden rounded-xl border border-stone-200 sm:h-[400px]"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= TENTANG / ABOUT ================= --}}
    <section id="tentang" class="bg-[#F9F7F4] py-16 lg:py-24">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1000&q=80" alt="Tentang Kost.in" class="aspect-[4/3] w-full object-cover">
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-[#B46C54]">TENTANG KOST.IN</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-stone-900 sm:text-4xl leading-tight">
                        Pencarian kos yang transparan dan dapat diandalkan.
                    </h2>
                    <p class="mt-4 text-sm leading-relaxed text-stone-600">
                        Kost.in dikembangkan khusus untuk membantu mahasiswa Sekolah Vokasi IPB menemukan hunian terdekat tanpa hambatan. Seluruh data dikurasi agar kamu mendapatkan informasi akurat langsung dari pemilik kos.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FOOTER ================= --}}
    <footer class="bg-[#1c1917] text-stone-300">
        <div class="mx-auto max-w-6xl px-5 sm:px-6 py-12">
            <div class="grid gap-10 sm:grid-cols-2 md:grid-cols-12">
                <div class="sm:col-span-2 md:col-span-6">
                    <a href="{{ route('pengguna.beranda') }}" class="inline-flex items-center gap-2.5">
                        <img src="{{ asset('images/logo kost.in.png') }}" alt="Kost.in" class="h-7 w-auto object-contain">
                        <span class="text-lg font-extrabold tracking-tight text-white">Kost<span class="text-[#B46C54]">.in</span></span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-stone-400">
                        Platform pencarian dan pemetaan informasi kos untuk mahasiswa Sekolah Vokasi IPB University.
                    </p>
                </div>

                <div class="md:col-span-3">
                    <h3 class="text-sm font-bold text-white">Menu</h3>
                    <ul class="mt-4 space-y-2 text-sm text-stone-400">
                        <li><a href="{{ route('pengguna.beranda') }}" class="transition hover:text-white">Beranda</a></li>
                        <li><a href="#peta" class="transition hover:text-white">Peta Kos</a></li>
                        <li><a href="#tentang" class="transition hover:text-white">Tentang</a></li>
                        <li><a href="{{ route('pengguna.jelajahKos') }}" class="transition hover:text-white">Jelajah Kos</a></li>
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <h3 class="text-sm font-bold text-white">Kontak</h3>
                    <ul class="mt-4 space-y-2 text-sm text-stone-400">
                        <li>halo@kostin.id</li>
                        <li>Kampus SV IPB, Bogor</li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-3 border-t border-stone-800 pt-6 text-xs text-stone-500 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {{ date('Y') }} Kost.in. Hak cipta dilindungi.</p>
                <div class="flex gap-5">
                    <a href="#" class="transition hover:text-stone-300">Syarat &amp; Ketentuan</a>
                    <a href="#" class="transition hover:text-stone-300">Kebijakan Privasi</a>
                </div>
            </div>
        </div>
    </footer>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const kampus = @json($kampus);
        const daftarKos = @json($kosList->values());
        const warnaTipe = { 'Putri': '#ec4899', 'Putra': '#3b82f6', 'Campur': '#a855f7' };
        const WARNA_UTAMA = '#B46C54';
        const radiusBuffer = [500, 1000, 2000, 3000];

        const map = L.map('peta-beranda', { scrollWheelZoom: false }).setView([kampus.lat, kampus.lng], 14);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        map.on('focus', () => map.scrollWheelZoom.enable());
        map.on('blur', () => map.scrollWheelZoom.disable());

        L.marker([kampus.lat, kampus.lng], {
            icon: L.divIcon({
                className: '',
                html: '<div style="background:#1c1917;color:#fff;font:600 11px/1 \'Plus Jakarta Sans\',sans-serif;padding:6px 10px;border-radius:6px;white-space:nowrap;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35);transform:translate(-50%,-50%)">' + kampus.nama + '</div>',
                iconSize: [0, 0]
            }),
            zIndexOffset: 1000
        }).addTo(map);

        const lingkaran = {};
        radiusBuffer.forEach(r => {
            lingkaran[r] = L.circle([kampus.lat, kampus.lng], {
                radius: r, color: WARNA_UTAMA, weight: 1.5, dashArray: '6 6',
                fillColor: WARNA_UTAMA, fillOpacity: 0.04
            }).addTo(map);
        });

        const formatJarak = m => m < 1000 ? (Math.round(m / 10) * 10) + ' m' : (m / 1000).toFixed(1).replace('.', ',') + ' km';
        const items = daftarKos.map(k => {
            const warna = warnaTipe[k.tipe] || WARNA_UTAMA;
            const marker = L.marker([k.lat, k.lng], {
                icon: L.divIcon({
                    className: '',
                    html: '<span style="display:block;width:14px;height:14px;border-radius:50%;background:' + warna + ';border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,.4);transform:translate(-50%,-50%)"></span>',
                    iconSize: [0, 0]
                })
            });
            marker.bindPopup('<div style="font-family:\'Plus Jakarta Sans\',sans-serif"><strong>' + k.nama + '</strong><br>' + k.jarak + ' dari kampus</div>');

            const garis = L.polyline([[kampus.lat, kampus.lng], [k.lat, k.lng]], {
                color: warna, weight: 1, opacity: 0.5, dashArray: '3 4'
            });
            const grup = L.layerGroup([garis, marker]);
            return { data: k, grup: grup };
        });

        const tombol = document.querySelectorAll('.radius-btn');
        let radiusAktif = 3000;

        function terapkanRadius(r) {
            radiusAktif = r;
            items.forEach(it => {
                if (it.data.jarak_m <= r) { it.grup.addTo(map); } else { map.removeLayer(it.grup); }
            });
            radiusBuffer.forEach(rb => {
                lingkaran[rb].setStyle({
                    weight: rb === r ? 2 : 1,
                    fillOpacity: rb === r ? 0.08 : 0.02
                });
            });
            tombol.forEach(b => {
                const aktif = parseInt(b.dataset.radius, 10) === r;
                b.classList.toggle('bg-[#B46C54]', aktif);
                b.classList.toggle('text-white', aktif);
                b.classList.toggle('border-[#B46C54]', aktif);
                b.classList.toggle('bg-white', !aktif);
                b.classList.toggle('text-stone-700', !aktif);
            });
            map.fitBounds(lingkaran[r].getBounds(), { padding: [20, 20] });
        }

        tombol.forEach(b => b.addEventListener('click', () => terapkanRadius(parseInt(b.dataset.radius, 10))));
        terapkanRadius(radiusAktif);

        setTimeout(() => map.invalidateSize(), 200);
    });
    </script>
@endpush