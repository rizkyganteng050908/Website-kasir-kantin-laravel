<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Kasir Kantin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #area-struk, #area-struk * {
                visibility: visible;
            }
            #area-struk {
                position: fixed;
                left: 0;
                top: 0;
                width: 80mm;
                padding: 10px;
                margin: 0;
                box-shadow: none;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans">

    <!-- Navbar -->
    <nav class="bg-emerald-600 text-white px-6 py-4 shadow-md flex flex-wrap justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="text-2xl font-bold">🍽️ Kasir Kantin Digital</span>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="bukaModalMenu()" class="bg-white text-emerald-700 hover:bg-emerald-50 font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition cursor-pointer">
                + Tambah Menu
            </button>
            <div class="bg-emerald-700 px-4 py-2 rounded-lg text-sm">
                Omzet Hari Ini: <strong class="text-lg">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</strong>
            </div>
        </div>
    </nav>

    <!-- Notifikasi Sukses -->
    @if(session('success'))
    <div class="max-w-7xl mx-auto px-6 pt-4">
        <div class="bg-emerald-100 border border-emerald-400 text-emerald-800 px-4 py-3 rounded-xl flex justify-between items-center">
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="font-bold text-lg">&times;</button>
        </div>
    </div>
    @endif

    <div class="max-w-7xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Kolom Kiri: Daftar Menu -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-5 rounded-xl shadow-sm">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold">Daftar Menu Kantin</h2>
                    <span class="text-xs text-slate-500">Klik menu untuk masuk ke keranjang</span>
                </div>

                @if($menus->isEmpty())
                    <div class="text-center py-12">
                        <p class="text-slate-400 mb-3">Belum ada menu tersedia di database.</p>
                        <button onclick="bukaModalMenu()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-emerald-700">
                            + Buat Menu Pertama
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($menus as $m)
                            <div class="relative rounded-xl border transition flex flex-col justify-between {{ $m->stok > 0 ? 'border-slate-200 hover:border-emerald-500 hover:bg-emerald-50' : 'bg-slate-100 opacity-60' }}">
                                <button 
                                    onclick="tambahKeranjang({{ $m->id }}, '{{ addslashes($m->nama_menu) }}', {{ $m->harga }}, {{ $m->stok }})"
                                    {{ $m->stok <= 0 ? 'disabled' : '' }}
                                    class="text-left p-4 w-full h-full {{ $m->stok > 0 ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-200 text-slate-600">{{ $m->kategori }}</span>
                                    <h3 class="font-bold text-slate-800 mt-2">{{ $m->nama_menu }}</h3>
                                    <p class="text-emerald-600 font-bold mt-1">Rp {{ number_format($m->harga, 0, ',', '.') }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Stok: <strong>{{ $m->stok }}</strong></p>
                                </button>
                                
                                <button onclick="bukaModalStok({{ $m->id }}, '{{ addslashes($m->nama_menu) }}')" 
                                    class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium py-1.5 border-t rounded-b-xl transition cursor-pointer">
                                    + Restok
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Riwayat Transaksi Singkat -->
            <div class="bg-white p-5 rounded-xl shadow-sm">
                <h2 class="text-lg font-bold mb-3">10 Transaksi Terakhir</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="p-2">No. Struk</th>
                                <th class="p-2">Waktu</th>
                                <th class="p-2">Total</th>
                                <th class="p-2">Kembalian</th>
                                <th class="p-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($laporan as $t)
                            @php
                                $strukData = [
                                    'no_struk' => '#TRX-' . str_pad($t->id, 4, '0', STR_PAD_LEFT),
                                    'waktu' => $t->created_at->format('d/m/Y H:i'),
                                    'total_bayar' => $t->total_bayar,
                                    'uang_diterima' => $t->uang_diterima,
                                    'kembalian' => $t->kembalian,
                                    'items' => $t->details->map(function($d) {
                                        return [
                                            'nama' => $d->menu->nama_menu ?? 'Item Dihapus',
                                            'qty' => $d->jumlah,
                                            'harga' => $d->jumlah > 0 ? ($d->subtotal / $d->jumlah) : 0,
                                        ];
                                    })
                                ];
                            @endphp
                            <tr class="border-t">
                                <td class="p-2 font-mono">{{ $strukData['no_struk'] }}</td>
                                <td class="p-2">{{ $t->created_at->format('H:i') }}</td>
                                <td class="p-2 font-semibold">Rp {{ number_format($t->total_bayar, 0, ',', '.') }}</td>
                                <td class="p-2 text-emerald-600">Rp {{ number_format($t->kembalian, 0, ',', '.') }}</td>
                                <td class="p-2 text-center">
                                    <button onclick='tampilkanStruk(@json($strukData), false)' 
                                        class="text-xs bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-700 px-2.5 py-1 rounded border font-medium transition cursor-pointer">
                                        🖨️ Struk
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-4 text-center text-slate-400">Belum ada transaksi hari ini</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Keranjang & Pembayaran -->
        <div class="bg-white p-5 rounded-xl shadow-sm h-fit sticky top-6">
            <h2 class="text-lg font-bold mb-4 border-b pb-2">Pesanan Saat Ini</h2>
            
            <div id="list-keranjang" class="space-y-3 max-h-80 overflow-y-auto mb-4">
                <p class="text-slate-400 text-sm text-center py-8">Belum ada item dipilih</p>
            </div>

            <div class="border-t pt-4 space-y-3">
                <div class="flex justify-between text-lg font-bold">
                    <span>Total Tagihan</span>
                    <span id="total-harga" class="text-emerald-600">Rp 0</span>
                </div>

                <div>
                    <input type="number" id="uang-diterima" oninput="hitungKembalian()" placeholder="Masukkan uang diterima (Tunai)" 
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:border-emerald-500">
                </div>

                <div class="flex justify-between text-sm font-semibold bg-slate-50 p-3 rounded-lg">
                    <span>Kembalian</span>
                    <span id="teks-kembalian" class="text-slate-800">Rp 0</span>
                </div>

                <button onclick="prosesBayar()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition cursor-pointer">
                    Bayar & Cetak Struk
                </button>
            </div>
        </div>

    </div>

    <!-- Modal Struk Pembayaran -->
    <div id="modal-struk" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl">
            
            <!-- Area Kertas Struk -->
            <div id="area-struk" class="font-mono text-xs text-slate-800 bg-white p-4 border border-dashed border-slate-300 rounded-lg">
                <div class="text-center border-b border-dashed border-slate-400 pb-3 mb-3">
                    <h3 class="font-bold text-base uppercase">KANTIN DIGITAL</h3>
                    <p class="text-[11px] text-slate-500">Struk Bukti Pembayaran</p>
                </div>

                <div class="flex justify-between text-[11px] mb-2">
                    <span id="struk-nomor">#TRX-0001</span>
                    <span id="struk-waktu">20/11/2026 21:00</span>
                </div>

                <div id="struk-items" class="border-t border-b border-dashed border-slate-400 py-2 my-2 space-y-1.5">
                    <!-- Item pesanan masuk ke sini -->
                </div>

                <div class="space-y-1 pt-1">
                    <div class="flex justify-between font-bold text-sm">
                        <span>TOTAL</span>
                        <span id="struk-total">Rp 0</span>
                    </div>
                    <div class="flex justify-between">
                        <span>TUNAI</span>
                        <span id="struk-tunai">Rp 0</span>
                    </div>
                    <div class="flex justify-between font-bold">
                        <span>KEMBALI</span>
                        <span id="struk-kembali">Rp 0</span>
                    </div>
                </div>

                <div class="text-center border-t border-dashed border-slate-400 pt-3 mt-3 text-[11px] text-slate-500">
                    <p>Terima Kasih Atas Kunjungan Anda!</p>
                    <p>Selamat Menikmati</p>
                </div>
            </div>

            <!-- Tombol Aksi Modal Struk -->
            <div class="flex gap-2 mt-4 no-print">
                <button onclick="window.print()" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl transition cursor-pointer">
                    🖨️ Print Struk
                </button>
                <button onclick="tutupModalStruk()" class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 font-semibold text-slate-700 cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Menu Baru -->
    <div id="modal-menu" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-bold">Tambah Menu Kantin</h3>
                <button onclick="tutupModalMenu()" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('menu.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <input type="text" name="nama_menu" required placeholder="Nama Menu (misal: Nasi Goreng)" 
                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <select name="kategori" required class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-emerald-500 bg-white">
                        <option value="" disabled selected>Pilih Kategori</option>
                        <option value="Makanan">Makanan</option>
                        <option value="Minuman">Minuman</option>
                        <option value="Snack">Snack</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" name="harga" required min="100" placeholder="Harga (Rp)" 
                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-emerald-500">
                    <input type="number" name="stok" required min="0" placeholder="Stok Awal" 
                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-emerald-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="tutupModalMenu()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Simpan Menu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Restok Menu -->
    <div id="modal-stok" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-bold">Tambah Stok: <span id="nama-menu-stok" class="text-emerald-600"></span></h3>
                <button onclick="tutupModalStok()" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>
            <form id="form-stok" method="POST" class="space-y-4">
                @csrf
                <div>
                    <input type="number" name="tambah_stok" required min="1" placeholder="Jumlah stok yang ditambahkan" 
                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:border-emerald-500">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="tutupModalStok()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 font-medium">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Update Stok</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let keranjang = [];
        let totalTagihan = 0;
        let perluReloadSetelahStruk = false;

        function bukaModalMenu() {
            document.getElementById('modal-menu').classList.replace('hidden', 'flex');
        }
        function tutupModalMenu() {
            document.getElementById('modal-menu').classList.replace('flex', 'hidden');
        }
        function bukaModalStok(id, nama) {
            document.getElementById('nama-menu-stok').innerText = nama;
            document.getElementById('form-stok').action = `/menu/${id}/stok`;
            document.getElementById('modal-stok').classList.replace('hidden', 'flex');
        }
        function tutupModalStok() {
            document.getElementById('modal-stok').classList.replace('flex', 'hidden');
        }

        function tampilkanStruk(data, reloadSaatTutup = true) {
            perluReloadSetelahStruk = reloadSaatTutup;
            document.getElementById('struk-nomor').innerText = data.no_struk;
            document.getElementById('struk-waktu').innerText = data.waktu;
            document.getElementById('struk-total').innerText = formatRupiah(data.total_bayar);
            document.getElementById('struk-tunai').innerText = formatRupiah(data.uang_diterima);
            document.getElementById('struk-kembali').innerText = formatRupiah(data.kembalian);

            const itemsContainer = document.getElementById('struk-items');
            itemsContainer.innerHTML = '';

            data.items.forEach(item => {
                const subtotal = item.harga * item.qty;
                itemsContainer.innerHTML += `
                    <div>
                        <div class="font-semibold">${item.nama}</div>
                        <div class="flex justify-between text-slate-600">
                            <span>${item.qty} x ${formatRupiah(item.harga)}</span>
                            <span>${formatRupiah(subtotal)}</span>
                        </div>
                    </div>
                `;
            });

            document.getElementById('modal-struk').classList.replace('hidden', 'flex');
        }

        function tutupModalStruk() {
            document.getElementById('modal-struk').classList.replace('flex', 'hidden');
            if (perluReloadSetelahStruk) {
                location.reload();
            }
        }

        function formatRupiah(angka) {
            return 'Rp ' + Number(angka).toLocaleString('id-ID');
        }

        function tambahKeranjang(id, nama, harga, stokMax) {
            let item = keranjang.find(i => i.id === id);
            if (item) {
                if (item.qty < stokMax) {
                    item.qty++;
                } else {
                    alert('Stok maksimal tercapai!');
                }
            } else {
                keranjang.push({ id, nama, harga, qty: 1, stokMax });
            }
            renderKeranjang();
        }

        function ubahQty(id, delta) {
            let index = keranjang.findIndex(i => i.id === id);
            if (index !== -1) {
                keranjang[index].qty += delta;
                if (keranjang[index].qty <= 0) {
                    keranjang.splice(index, 1);
                } else if (keranjang[index].qty > keranjang[index].stokMax) {
                    keranjang[index].qty = keranjang[index].stokMax;
                }
            }
            renderKeranjang();
        }

        function renderKeranjang() {
            const container = document.getElementById('list-keranjang');
            container.innerHTML = '';
            totalTagihan = 0;

            if (keranjang.length === 0) {
                container.innerHTML = '<p class="text-slate-400 text-sm text-center py-8">Belum ada item dipilih</p>';
            } else {
                keranjang.forEach(item => {
                    let subtotal = item.harga * item.qty;
                    totalTagihan += subtotal;

                    container.innerHTML += `
                        <div class="flex justify-between items-center bg-slate-50 p-2.5 rounded-lg">
                            <div>
                                <div class="font-semibold text-sm">${item.nama}</div>
                                <div class="text-xs text-slate-500">${formatRupiah(item.harga)} x ${item.qty}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="ubahQty(${item.id}, -1)" class="w-6 h-6 bg-slate-200 rounded font-bold">-</button>
                                <span class="text-sm font-semibold w-4 text-center">${item.qty}</span>
                                <button onclick="ubahQty(${item.id}, 1)" class="w-6 h-6 bg-emerald-200 text-emerald-800 rounded font-bold">+</button>
                            </div>
                        </div>
                    `;
                });
            }

            document.getElementById('total-harga').innerText = formatRupiah(totalTagihan);
            hitungKembalian();
        }

        function hitungKembalian() {
            const uang = parseInt(document.getElementById('uang-diterima').value) || 0;
            const kembalian = uang - totalTagihan;
            const teks = document.getElementById('teks-kembalian');

            if (uang === 0) {
                teks.innerText = 'Rp 0';
                teks.className = 'text-slate-800';
            } else if (kembalian < 0) {
                teks.innerText = 'Kurang ' + formatRupiah(Math.abs(kembalian));
                teks.className = 'text-red-600';
            } else {
                teks.innerText = formatRupiah(kembalian);
                teks.className = 'text-emerald-600 font-bold';
            }
        }

        async function prosesBayar() {
            if (keranjang.length === 0) {
                alert('Pilih menu terlebih dahulu!');
                return;
            }

            const uangDiterima = parseInt(document.getElementById('uang-diterima').value) || 0;
            if (uangDiterima < totalTagihan) {
                alert('Uang pembayaran masih kurang!');
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const response = await fetch("{{ route('kasir.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    keranjang: keranjang,
                    total_bayar: totalTagihan,
                    uang_diterima: uangDiterima
                })
            });

            const result = await response.json();
            if (result.status === 'success') {
                tampilkanStruk(result.transaksi, true);
            } else {
                alert('Gagal: ' + result.message);
            }
        }
    </script>
</body>
</html>