<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KasirController extends Controller
{
    public function index()
    {
        $menus = Menu::orderBy('kategori')->orderBy('nama_menu')->get();
        $laporan = Transaksi::with('details.menu')->latest()->take(10)->get();
        $omzetHariIni = Transaksi::whereDate('created_at', Carbon::today())->sum('total_bayar');

        return view('kasir', compact('menus', 'laporan', 'omzetHariIni'));
    }

    public function storeMenu(Request $request)
    {
        $request->validate([
            'nama_menu' => 'required|string|max:100',
            'harga' => 'required|integer|min:100',
            'stok' => 'required|integer|min:0',
            'kategori' => 'required|string',
        ]);

        Menu::create([
            'nama_menu' => $request->nama_menu,
            'harga' => $request->harga,
            'stok' => $request->stok,
            'kategori' => $request->kategori,
        ]);

        return redirect()->route('kasir.index')->with('success', 'Menu baru berhasil ditambahkan!');
    }

    public function updateStok(Request $request, $id)
    {
        $request->validate([
            'tambah_stok' => 'required|integer|min:1',
        ]);

        $menu = Menu::findOrFail($id);
        $menu->increment('stok', $request->tambah_stok);

        return redirect()->route('kasir.index')->with('success', "Stok {$menu->nama_menu} berhasil ditambah!");
    }

    public function store(Request $request)
    {
        $keranjang = $request->keranjang;
        $totalBayar = (int) $request->total_bayar;
        $uangDiterima = (int) $request->uang_diterima;
        $kembalian = $uangDiterima - $totalBayar;

        if (empty($keranjang)) {
            return response()->json(['status' => 'error', 'message' => 'Keranjang kosong!']);
        }

        if ($kembalian < 0) {
            return response()->json(['status' => 'error', 'message' => 'Uang pembayaran kurang!']);
        }

        DB::beginTransaction();
        try {
            $transaksi = Transaksi::create([
                'total_bayar' => $totalBayar,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
            ]);

            foreach ($keranjang as $item) {
                $menu = Menu::lockForUpdate()->find($item['id']);

                if (!$menu || $menu->stok < $item['qty']) {
                    throw new \Exception("Stok untuk {$item['nama']} tidak mencukupi!");
                }

                $menu->decrement('stok', $item['qty']);

                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'menu_id' => $menu->id,
                    'jumlah' => $item['qty'],
                    'subtotal' => $item['harga'] * $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'transaksi' => [
                    'no_struk' => '#TRX-' . str_pad($transaksi->id, 4, '0', STR_PAD_LEFT),
                    'waktu' => $transaksi->created_at->format('d/m/Y H:i'),
                    'items' => $keranjang,
                    'total_bayar' => $totalBayar,
                    'uang_diterima' => $uangDiterima,
                    'kembalian' => $kembalian,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}