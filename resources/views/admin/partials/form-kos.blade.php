{{--
    Isi form data kos, dipakai halaman Tambah dan Edit.
    Variabel: $kos (App\Models\Kos, baru atau lama), $semuaFasilitas (koleksi Fasilitas).
    Tanda * = wajib agar kos bisa diaktifkan. Data belum lengkap tetap bisa disimpan sebagai Draft.
--}}
@php
    $tipeAwal = old('tipe_kos', $kos->tipe?->label() ?? '');
    $periodeAwal = old('periode_sewa', $kos->labelPeriode() ?? '1 Bulan');
    $fasilitasAwal = array_map('strval', old('fasilitas', $kos->exists ? $kos->fasilitas->pluck('id')->all() : []));
    $latAwal = old('latitude', $kos->lokasi ? number_format($kos->lokasi->lat, 7, '.', '') : '');
    $lngAwal = old('longitude', $kos->lokasi ? number_format($kos->lokasi->lng, 7, '.', '') : '');
    $fotoTersimpan = $kos->exists
        ? $kos->foto->map(fn ($f) => ['id' => $f->id, 'url' => $f->url(), 'utama' => $f->is_utama])->values()
        : collect();
    $utamaAwal = old('foto_utama', ($u = $fotoTersimpan->firstWhere('utama', true)) ? 'lama:'.$u['id'] : '');
    $kampus = \App\Support\Koordinat::kampus();
    $inputClass = 'block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]';
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <p class="font-bold">Data belum bisa disimpan. Periksa kembali isian berikut:</p>
        <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs">Foto yang tadi dipilih perlu dipilih ulang karena browser tidak menyimpannya.</p>
    </div>
@endif

<p class="mb-4 text-xs text-slate-500">Kolom bertanda <span class="text-red-500">*</span> wajib diisi agar kos bisa diaktifkan. Data yang belum lengkap tetap bisa disimpan sebagai Draft.</p>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- KOLOM KIRI (Informasi, Fasilitas, Foto) --}}
    <div class="space-y-6 lg:col-span-2">

        {{-- 1. INFORMASI KOS --}}
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">01</span>
                <div>
                    <h3 class="text-base font-bold text-ink">Informasi Kos</h3>
                    <p class="text-xs text-slate-500">Informasi utama yang akan dilihat mahasiswa.</p>
                </div>
            </div>

            <div class="space-y-5">
                {{-- Nama Kos --}}
                <div>
                    <label for="nama_kos" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Kos <span class="text-red-500">*</span></label>
                    <input type="text" id="nama_kos" name="nama_kos" required maxlength="150" value="{{ old('nama_kos', $kos->nama) }}" class="{{ $inputClass }} @error('nama_kos') border-red-400 @else border-stone-200 @enderror" placeholder="Contoh: Kos Putri Melati">
                    @error('nama_kos') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- No Telepon --}}
                <div>
                    <label for="no_telepon" class="mb-1.5 block text-sm font-semibold text-slate-700">No. Telepon</label>
                    <input type="text" id="no_telepon" name="no_telepon" maxlength="20" value="{{ old('no_telepon', $kos->no_telepon) }}" class="{{ $inputClass }} @error('no_telepon') border-red-400 @else border-stone-200 @enderror" placeholder="Contoh: 0812 3456 7890">
                    @error('no_telepon') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Alamat --}}
                <div>
                    <label for="alamat" class="mb-1.5 block text-sm font-semibold text-slate-700">Alamat <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="alamat" name="alamat" maxlength="500" value="{{ old('alamat', $kos->alamat) }}" class="{{ $inputClass }} pr-32 @error('alamat') border-red-400 @else border-stone-200 @enderror" placeholder="Masukkan alamat lengkap">
                        <button type="button" id="btn-cari-peta" class="absolute right-1.5 top-1.5 flex items-center gap-1.5 rounded-lg bg-[#fbf5f3] px-3 py-1.5 text-[11px] font-bold text-[#B46C54] transition hover:bg-[#B46C54] hover:text-white">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                            Cari di Peta
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">Tulis alamat selengkapnya untuk ditampilkan ke mahasiswa. Klik "Cari di Peta" untuk perkiraan lokasi, lalu geser pin ke titik kos yang tepat.</p>
                    @error('alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Custom Dropdown Tipe Kos --}}
                    <div x-data="{ openTipe: false, selectedTipe: @js($tipeAwal) }">
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Tipe Kos <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <button
                                @click="openTipe = !openTipe"
                                @click.outside="openTipe = false"
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border bg-white px-4 py-2.5 text-sm transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54] @error('tipe_kos') border-red-400 @else border-stone-200 @enderror"
                            >
                                <span x-text="selectedTipe === '' ? 'Pilih tipe kos' : selectedTipe" :class="selectedTipe === '' ? 'text-slate-400' : 'text-ink'"></span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openTipe ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div
                                x-show="openTipe"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                style="display: none;"
                                class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg"
                            >
                                <ul class="flex flex-col py-1">
                                    @foreach (\App\Enums\TipeKos::labels() as $tipe)
                                        <li @click="selectedTipe = @js($tipe); openTipe = false" :class="selectedTipe === @js($tipe) ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">{{ $tipe }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <input type="hidden" name="tipe_kos" :value="selectedTipe">
                        @error('tipe_kos') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Custom Dropdown Periode Minimal Sewa --}}
                    <div x-data="{ openPeriode: false, selectedPeriode: @js($periodeAwal) }">
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Periode Minimal Sewa <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <button
                                @click="openPeriode = !openPeriode"
                                @click.outside="openPeriode = false"
                                type="button"
                                class="flex w-full items-center justify-between rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink transition focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]"
                            >
                                <span x-text="selectedPeriode"></span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="openPeriode ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div
                                x-show="openPeriode"
                                x-cloak
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                style="display: none;"
                                class="absolute z-50 mt-1 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg"
                            >
                                <ul class="flex flex-col py-1">
                                    @foreach (\App\Enums\PeriodeSewa::labels() as $periode)
                                        <li @click="selectedPeriode = @js($periode); openPeriode = false" :class="selectedPeriode === @js($periode) ? 'bg-[#fbf5f3] font-bold text-[#B46C54]' : 'text-slate-600 hover:bg-stone-50'" class="cursor-pointer px-4 py-2.5 text-sm transition-colors">{{ $periode }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <input type="hidden" name="periode_sewa" :value="selectedPeriode">
                        @error('periode_sewa') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Harga Sewa per Bulan --}}
                <div>
                    <label for="harga" class="mb-1.5 block text-sm font-semibold text-slate-700">Harga Sewa per Bulan <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-medium text-slate-500">Rp</span>
                        <input type="number" id="harga" name="harga" min="100000" max="20000000" step="1000" value="{{ old('harga', $kos->harga_per_bulan) }}" class="{{ $inputClass }} pl-11 @error('harga') border-red-400 @else border-stone-200 @enderror" placeholder="0">
                    </div>
                    <p class="mt-1.5 text-[11px] text-slate-500">Isi harga per bulan. Kalau pemilik menyebut harga per tahun, bagi 12. Rekomendasi dan filter memakai angka ini.</p>
                    @error('harga') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label for="deskripsi" class="mb-1.5 block text-sm font-semibold text-slate-700">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="4" maxlength="3000" class="{{ $inputClass }} py-3 @error('deskripsi') border-red-400 @else border-stone-200 @enderror" placeholder="Jelaskan suasana, akses, dan keunggulan kos...">{{ old('deskripsi', $kos->deskripsi) }}</textarea>
                    @error('deskripsi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 2. FASILITAS --}}
        <div x-data="{ selectedFasilitas: @js($fasilitasAwal) }" class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">02</span>
                <div>
                    <h3 class="text-base font-bold text-ink">Fasilitas</h3>
                    <p class="text-xs text-slate-500">Pilih fasilitas yang tersedia di kos.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                @foreach ($semuaFasilitas as $fas)
                    @php $idFas = (string) $fas->id; @endphp
                    <label
                        class="flex cursor-pointer items-center gap-2.5 rounded-xl border py-2 pl-3 pr-4 text-sm font-medium transition-colors"
                        :class="selectedFasilitas.includes(@js($idFas)) ? 'border-[#B46C54] bg-[#fbf5f3] text-[#B46C54]' : 'border-stone-200 bg-white text-slate-600 hover:bg-stone-50'"
                    >
                        <input type="checkbox" name="fasilitas[]" value="{{ $idFas }}" x-model="selectedFasilitas" class="hidden">

                        <div class="flex h-4 w-4 items-center justify-center rounded-[4px] border transition-colors"
                             :class="selectedFasilitas.includes(@js($idFas)) ? 'border-[#B46C54] bg-[#B46C54] text-white' : 'border-stone-300 bg-white'">
                            <svg x-show="selectedFasilitas.includes(@js($idFas))" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        {{ $fas->nama }}
                    </label>
                @endforeach
            </div>
            @error('fasilitas.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- 3. FOTO KOS (multi-upload + foto utama + validasi 3 MB) --}}
        <div
            x-data="{
                tersimpan: @js($fotoTersimpan),
                hapus: [],
                baru: [],
                utama: @js($utamaAwal),
                maksBytes: {{ (int) config('kostin.foto.maks_kb') * 1024 }},
                maksJumlah: {{ (int) config('kostin.foto.maks_jumlah') }},
                pesan: '',
                get jumlah() { return this.tersimpan.length - this.hapus.length + this.baru.length },
                init() { this.pastikanUtama() },
                pilihFile(event) {
                    this.pesan = '';
                    for (const file of Array.from(event.target.files)) {
                        if (!['image/jpeg', 'image/png'].includes(file.type)) { this.pesan = `'${file.name}' bukan JPG/PNG, dilewati.`; continue; }
                        if (file.size > this.maksBytes) { this.pesan = `Ukuran '${file.name}' lebih dari 3 MB, dilewati.`; continue; }
                        if (this.jumlah >= this.maksJumlah) { this.pesan = `Maksimal ${this.maksJumlah} foto per kos.`; break; }
                        this.baru.push({ file, url: URL.createObjectURL(file) });
                    }
                    event.target.value = '';
                    this.sinkron();
                },
                hapusBaru(i) {
                    URL.revokeObjectURL(this.baru[i].url);
                    this.baru.splice(i, 1);
                    if (this.utama === 'baru:' + i) this.utama = '';
                    else if (this.utama.startsWith('baru:') && +this.utama.slice(5) > i) this.utama = 'baru:' + (+this.utama.slice(5) - 1);
                    this.sinkron();
                },
                hapusLama(id) {
                    this.hapus.push(id);
                    if (this.utama === 'lama:' + id) this.utama = '';
                    this.pastikanUtama();
                },
                batalHapus(id) { this.hapus = this.hapus.filter(x => x !== id); this.pastikanUtama(); },
                pastikanUtama() {
                    if (this.utama !== '') return;
                    const lama = this.tersimpan.find(f => !this.hapus.includes(f.id));
                    if (lama) this.utama = 'lama:' + lama.id;
                    else if (this.baru.length) this.utama = 'baru:0';
                },
                sinkron() {
                    // Salin daftar foto baru ke input file yang ikut dikirim bersama form.
                    const dt = new DataTransfer();
                    this.baru.forEach(b => dt.items.add(b.file));
                    this.$refs.fileKirim.files = dt.files;
                    this.pastikanUtama();
                }
            }"
            class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm"
        >
            <div class="mb-6 flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">03</span>
                <div>
                    <h3 class="text-base font-bold text-ink">Foto Kos <span class="text-red-500">*</span></h3>
                    <p class="text-xs text-slate-500">Pilih beberapa foto, lalu tentukan satu sebagai Foto Utama (cuplikan). Minimal 1 foto agar kos bisa diaktifkan.</p>
                </div>
            </div>

            {{-- Nilai yang dikirim ke backend --}}
            <input type="hidden" name="foto_utama" :value="utama">
            <template x-for="id in hapus" :key="'h' + id"><input type="hidden" name="hapus_foto[]" :value="id"></template>
            <input type="file" name="foto[]" multiple class="hidden" x-ref="fileKirim">

            {{-- Input pemilih file (tidak dikirim langsung) --}}
            <input type="file" multiple accept="image/png, image/jpeg" class="hidden" x-ref="filePilih" @change="pilihFile">

            {{-- Dropzone Area --}}
            <div
                @click="$refs.filePilih.click()"
                class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-stone-200 bg-stone-50 py-10 transition hover:border-[#fbf5f3] hover:bg-stone-100"
            >
                <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#fbf5f3] text-[#B46C54]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </span>
                <span class="text-sm font-bold text-ink">Upload Foto Kos</span>
                <span class="mt-1 text-xs text-slate-500">PNG atau JPG, maksimal 3 MB per foto, maksimal {{ config('kostin.foto.maks_jumlah') }} foto</span>
            </div>
            <p x-show="pesan" x-text="pesan" x-cloak class="mt-2 text-xs font-semibold text-red-600"></p>
            @error('foto') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('foto.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            {{-- Galeri: foto tersimpan + foto baru --}}
            <div x-show="tersimpan.length + baru.length > 0" x-cloak class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                <template x-for="foto in tersimpan" :key="'l' + foto.id">
                    <div
                        class="group relative aspect-[4/3] overflow-hidden rounded-xl border bg-stone-100 transition-all"
                        :class="hapus.includes(foto.id) ? 'border-stone-200 opacity-40' : (utama === 'lama:' + foto.id ? 'border-[#B46C54] ring-2 ring-[#B46C54] ring-offset-1' : 'border-stone-200')"
                    >
                        <img :src="foto.url" class="h-full w-full object-cover" alt="">
                        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>

                        <div x-show="utama === 'lama:' + foto.id && !hapus.includes(foto.id)" class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-[#B46C54] px-2 py-1 text-[10px] font-bold text-white shadow-sm">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            Foto Utama
                        </div>
                        <button type="button" x-show="utama !== 'lama:' + foto.id && !hapus.includes(foto.id)" @click="utama = 'lama:' + foto.id"
                            class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-white/95 px-2 py-1 text-[10px] font-bold text-slate-700 opacity-0 shadow-sm backdrop-blur-sm transition-all hover:text-[#B46C54] group-hover:opacity-100">
                            Jadikan Utama
                        </button>

                        <button type="button" x-show="!hapus.includes(foto.id)" @click="hapusLama(foto.id)" aria-label="Hapus foto"
                            class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-white/95 text-slate-500 opacity-0 shadow-sm backdrop-blur-sm transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <button type="button" x-show="hapus.includes(foto.id)" @click="batalHapus(foto.id)"
                            class="absolute inset-x-2 bottom-2 rounded-md bg-white px-2 py-1 text-[11px] font-bold text-slate-700 shadow-sm">
                            Batal hapus
                        </button>
                    </div>
                </template>

                <template x-for="(foto, index) in baru" :key="'b' + index + foto.url">
                    <div
                        class="group relative aspect-[4/3] overflow-hidden rounded-xl border bg-stone-100 transition-all"
                        :class="utama === 'baru:' + index ? 'border-[#B46C54] ring-2 ring-[#B46C54] ring-offset-1' : 'border-stone-200'"
                    >
                        <img :src="foto.url" class="h-full w-full object-cover" alt="">
                        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
                        <span class="absolute bottom-2 left-2 rounded bg-white/90 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">Baru</span>

                        <div x-show="utama === 'baru:' + index" class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-[#B46C54] px-2 py-1 text-[10px] font-bold text-white shadow-sm">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            Foto Utama
                        </div>
                        <button type="button" x-show="utama !== 'baru:' + index" @click="utama = 'baru:' + index"
                            class="absolute left-2 top-2 flex items-center gap-1 rounded-md bg-white/95 px-2 py-1 text-[10px] font-bold text-slate-700 opacity-0 shadow-sm backdrop-blur-sm transition-all hover:text-[#B46C54] group-hover:opacity-100">
                            Jadikan Utama
                        </button>
                        <button type="button" @click="hapusBaru(index)" aria-label="Hapus foto"
                            class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-white/95 text-slate-500 opacity-0 shadow-sm backdrop-blur-sm transition-all hover:bg-red-50 hover:text-red-500 group-hover:opacity-100">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN (Lokasi Map) --}}
    <div class="lg:col-span-1">
        <div class="sticky top-24 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fbf5f3] text-sm font-extrabold text-[#B46C54]">04</span>
                <div>
                    <h3 class="text-base font-bold text-ink">Lokasi <span class="text-red-500">*</span></h3>
                    <p class="text-xs text-slate-500">Tentukan titik kos pada peta.</p>
                </div>
            </div>

            <button type="button" id="btn-lokasi-saya" class="mb-3 flex w-full items-center justify-center gap-2 rounded-xl border border-[#B46C54] bg-white px-3 py-2 text-xs font-bold text-[#B46C54] transition hover:bg-[#B46C54] hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8a4 4 0 100 8 4 4 0 000-8zm0-6v3m0 14v3M2 12h3m14 0h3"/></svg>
                <span id="btn-lokasi-saya-teks">Gunakan lokasi saya</span>
            </button>

            <div class="relative z-0 mb-3 overflow-hidden rounded-xl border border-stone-200">
                <div id="map-kos" class="h-64 w-full"></div>
            </div>
            <p id="map-status" class="mb-4 hidden text-[11px] font-semibold leading-relaxed"></p>

            <div class="mb-4">
                <label for="paste-koordinat" class="mb-1.5 block text-xs font-semibold text-slate-700">Tempel koordinat dari Google Maps</label>
                <div class="flex gap-2">
                    <input type="text" id="paste-koordinat" class="block w-full rounded-xl border border-stone-200 bg-white px-3 py-2 text-sm text-ink transition placeholder:text-slate-400 focus:border-[#B46C54] focus:outline-none focus:ring-1 focus:ring-[#B46C54]" placeholder="-6.5975, 106.7995">
                    <button type="button" id="btn-pakai-koordinat" class="shrink-0 rounded-xl bg-[#fbf5f3] px-3 text-xs font-bold text-[#B46C54] transition hover:bg-[#B46C54] hover:text-white">Pakai</button>
                </div>
                <p class="mt-1.5 text-[11px] leading-relaxed text-slate-500">Klik kanan titik kos di Google Maps, klik angka koordinat untuk menyalin, lalu tempel di sini.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="latitude" class="mb-1.5 block text-xs font-semibold text-slate-700">Latitude</label>
                    <input type="text" id="latitude" name="latitude" value="{{ $latAwal }}" class="block w-full rounded-xl border bg-stone-50 px-3 py-2 text-sm text-slate-500 focus:outline-none @error('latitude') border-red-400 @else border-stone-200 @enderror" readonly placeholder="Belum ditentukan">
                </div>
                <div>
                    <label for="longitude" class="mb-1.5 block text-xs font-semibold text-slate-700">Longitude</label>
                    <input type="text" id="longitude" name="longitude" value="{{ $lngAwal }}" class="block w-full rounded-xl border bg-stone-50 px-3 py-2 text-sm text-slate-500 focus:outline-none @error('longitude') border-red-400 @else border-stone-200 @enderror" readonly placeholder="Belum ditentukan">
                </div>
                @error('latitude') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @error('longitude') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="button" id="btn-hapus-titik" class="hidden text-[11px] font-bold text-slate-500 underline hover:text-red-600">Kosongkan titik lokasi</button>
            </div>

            <div class="mt-5 flex gap-3 rounded-xl border border-[#fbf5f3] bg-[#fbf5f3]/50 p-4">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#B46C54]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-[11px] leading-relaxed text-[#B46C54]">Isi kolom Alamat lalu klik "Cari di Peta", atau klik langsung di peta. Geser pin kalau lokasinya belum tepat. Kotak hitam adalah titik kampus.</p>
            </div>
        </div>
    </div>

</div>

{{-- ================= LEAFLET + OPENSTREETMAP ================= --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const KAMPUS = @js(['lat' => $kampus->lat, 'lng' => $kampus->lng, 'nama' => config('kostin.kampus.nama')]);

    const layerPeta = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    });
    const layerSatelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        maxNativeZoom: 18,
        attribution: 'Tiles &copy; Esri'
    });

    const inputLat = document.getElementById('latitude');
    const inputLng = document.getElementById('longitude');
    const statusEl = document.getElementById('map-status');
    const btnHapusTitik = document.getElementById('btn-hapus-titik');

    const adaTitik = inputLat.value !== '' && inputLng.value !== '';
    const titikAwal = adaTitik ? [parseFloat(inputLat.value), parseFloat(inputLng.value)] : [KAMPUS.lat, KAMPUS.lng];

    const mapKos = L.map('map-kos', { layers: [layerPeta] }).setView(titikAwal, adaTitik ? 17 : 15);
    L.control.layers({ 'Peta': layerPeta, 'Satelit': layerSatelit }, null, { position: 'topright' }).addTo(mapKos);

    // Penanda kampus sebagai acuan.
    L.marker([KAMPUS.lat, KAMPUS.lng], {
        icon: L.divIcon({
            className: '',
            html: '<span style="display:flex;width:22px;height:22px;border-radius:6px;background:#1c1917;transform:translate(-50%,-50%);box-shadow:0 1px 4px rgba(0,0,0,.4)"></span>',
            iconSize: [0, 0]
        }),
        interactive: false, keyboard: false
    }).addTo(mapKos).bindTooltip(KAMPUS.nama, { direction: 'top', offset: [0, -12] });

    // Pin kos baru dibuat setelah admin menentukan lokasi, supaya titik tidak tersimpan di posisi default.
    let markerKos = null;

    function updateKoordinat(latlng) {
        inputLat.value = latlng.lat.toFixed(7);
        inputLng.value = latlng.lng.toFixed(7);
        btnHapusTitik.classList.remove('hidden');
    }
    function showStatus(msg, tone) {
        statusEl.textContent = msg || '';
        statusEl.classList.toggle('hidden', !msg);
        statusEl.classList.toggle('text-red-500', tone === 'error');
        statusEl.classList.toggle('text-[#B46C54]', tone !== 'error');
    }
    function pasangPin(latlng) {
        if (!markerKos) {
            markerKos = L.marker(latlng, { draggable: true }).addTo(mapKos);
            markerKos.on('dragend', () => updateKoordinat(markerKos.getLatLng()));
        } else {
            markerKos.setLatLng(latlng);
        }
        updateKoordinat(latlng);
    }
    function setPosisi(latlng, zoom) {
        pasangPin(latlng);
        mapKos.setView(latlng, zoom || Math.max(mapKos.getZoom(), 17));
    }

    if (adaTitik) {
        pasangPin(L.latLng(titikAwal[0], titikAwal[1]));
    } else {
        showStatus('Titik kos belum ditentukan. Klik peta, cari alamat, atau tempel koordinat.', 'info');
    }

    mapKos.on('click', e => { pasangPin(e.latlng); showStatus(''); });

    btnHapusTitik.addEventListener('click', () => {
        if (markerKos) { mapKos.removeLayer(markerKos); markerKos = null; }
        inputLat.value = ''; inputLng.value = '';
        btnHapusTitik.classList.add('hidden');
        showStatus('Titik lokasi dikosongkan. Kos tidak bisa diaktifkan tanpa titik lokasi.', 'info');
    });

    // ---------- 1. Gunakan lokasi saya (GPS) ----------
    const btnLokasi = document.getElementById('btn-lokasi-saya');
    const btnLokasiTeks = document.getElementById('btn-lokasi-saya-teks');
    btnLokasi.addEventListener('click', function () {
        if (!navigator.geolocation) { showStatus('Browser tidak mendukung deteksi lokasi.', 'error'); return; }
        showStatus('');
        btnLokasi.disabled = true;
        btnLokasiTeks.textContent = 'Mendeteksi lokasi...';
        navigator.geolocation.getCurrentPosition(
            pos => {
                setPosisi(L.latLng(pos.coords.latitude, pos.coords.longitude), 18);
                showStatus('Lokasi perangkat terdeteksi. Geser pin jika masih kurang tepat.', 'info');
                btnLokasi.disabled = false; btnLokasiTeks.textContent = 'Gunakan lokasi saya';
            },
            () => {
                showStatus('Lokasi tidak bisa diambil. Pastikan izin lokasi diaktifkan (butuh HTTPS atau localhost).', 'error');
                btnLokasi.disabled = false; btnLokasiTeks.textContent = 'Gunakan lokasi saya';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });

    // ---------- 2. Tempel koordinat / link dari Google Maps ----------
    function parseKoordinat(teks) {
        const pola = [
            /@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/,
            /!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/,
            /(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)/
        ];
        for (const p of pola) {
            const m = teks.match(p);
            if (m) {
                const lat = parseFloat(m[1]), lng = parseFloat(m[2]);
                if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) return L.latLng(lat, lng);
            }
        }
        return null;
    }
    const inputPaste = document.getElementById('paste-koordinat');
    function pakaiKoordinat() {
        const ll = parseKoordinat(inputPaste.value.trim());
        if (!ll) { showStatus('Format koordinat tidak dikenali. Contoh: -6.5975, 106.7995', 'error'); return; }
        setPosisi(ll, 18);
        showStatus('Pin dipindahkan ke koordinat yang ditempel.', 'info');
    }
    document.getElementById('btn-pakai-koordinat').addEventListener('click', pakaiKoordinat);
    inputPaste.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); pakaiKoordinat(); } });
    inputPaste.addEventListener('paste', () => setTimeout(pakaiKoordinat, 0));

    // ---------- 3. Cari alamat (Nominatim) dengan fallback ----------
    const inputAlamat = document.getElementById('alamat');
    const btnCari = document.getElementById('btn-cari-peta');
    const tunggu = ms => new Promise(r => setTimeout(r, ms));

    function buatKandidat(alamat) {
        let s = alamat
            .replace(/\bRT\.?\s*\d+/gi, '')
            .replace(/\bRW\.?\s*\d+/gi, '')
            .replace(/\bNo\.?\s*[\w\-\/]+/gi, '')
            .replace(/\bBlok\.?\s*[\w\-]+/gi, '')
            .replace(/\b(komplek|komp|perumahan|perum)\.?\s*[^,]*/gi, '')
            .replace(/\b\d{5}\b/g, '');
        const bagian = s.split(',')
            .map(p => p.replace(/[\/\s]+$/g, '').replace(/^[\/\s]+/g, '').trim())
            .filter(Boolean);
        const daftar = [alamat.trim(), bagian.join(', ')];
        for (let i = 1; i < bagian.length - 1; i++) daftar.push(bagian.slice(i).join(', '));
        return [...new Set(daftar.filter(Boolean))].slice(0, 5);
    }

    async function nominatim(q) {
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&accept-language=id'
            + '&viewbox=106.70,-6.50,106.90,-6.70&q=' + encodeURIComponent(q);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    async function cariAlamat() {
        const alamat = inputAlamat.value.trim();
        if (!alamat) { showStatus('Isi alamat terlebih dahulu.', 'error'); return; }
        showStatus('');
        btnCari.disabled = true;
        try {
            const kandidat = buatKandidat(alamat);
            for (let i = 0; i < kandidat.length; i++) {
                if (i > 0) await tunggu(1100); // batas Nominatim: 1 request/detik
                const data = await nominatim(kandidat[i]);
                if (data.length) {
                    setPosisi(L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)), i === 0 ? 17 : 16);
                    showStatus(i === 0
                        ? 'Lokasi ditemukan. Zoom lalu geser pin ke titik kos yang tepat.'
                        : 'Alamat persis tidak ada di peta, ini perkiraan lokasi (' + kandidat[i] + '). Geser pin ke titik kos yang tepat.', 'info');
                    return;
                }
            }
            showStatus('Alamat tidak ditemukan. Coba tulis nama jalan + kelurahan + kota, atau klik langsung di peta.', 'error');
        } catch (err) {
            showStatus('Gagal mencari alamat. Cek koneksi internet, atau klik langsung di peta.', 'error');
        } finally {
            btnCari.disabled = false;
        }
    }
    btnCari.addEventListener('click', cariAlamat);
    inputAlamat.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); cariAlamat(); } });

    setTimeout(() => mapKos.invalidateSize(), 200);
});
</script>
