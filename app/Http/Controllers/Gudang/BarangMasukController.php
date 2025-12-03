<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\BarangMasuk;
use App\Models\StokGudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarangMasukController extends Controller
{
    public function index()
    {
        return BarangMasuk::with('bahan')->latest()->get();
    }

    public function show($id)
    {
        try {
            $barangMasuk = BarangMasuk::with('bahan')->findOrFail($id);
            return response()->json([
                'message' => 'Detail barang masuk ditemukan',
                'data' => $barangMasuk
            ], 200);
        } catch (\Exception $e) {
            Log::error('Barang Masuk Show Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * STORE – Tambah barang masuk
     */
    public function store(Request $request)
    {
        try {
            {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $request->validate([
                'bahan_id' => 'required|exists:bahan,id',
                'jumlah'   => 'required|numeric|min:0.001',
                'supplier' => 'required|string|max:255'
            ]);

            // HAPUS KODE KONVERSI ANEH INI SELAMANYA
            // $jumlah = floatval(str_replace('.', '', $request->jumlah)) / 1000;

            // Gunakan langsung nilai yang sudah divalidasi numeric
            $jumlah = $request->jumlah; // contoh: 1.500, 0.250, 15.000

            $masuk = BarangMasuk::create([
                'bahan_id' => $request->bahan_id,
                'jumlah'   => $jumlah,           // simpan apa adanya (1.500)
                'tanggal'  => now(),
                'supplier' => $request->supplier
            ]);

            // INI YANG BENAR & AMAN SELAMANYA
            StokGudang::updateOrCreate(
                ['bahan_id' => $request->bahan_id],
                ['stok' => DB::raw('stok + ' . $jumlah)]
            );

            return response()->json([
                'message' => 'Barang masuk berhasil dicatat!',
                'data'    => $masuk->load('bahan')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Barang Masuk Store Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * UPDATE – Edit barang masuk (bahkan kalau ganti bahan)
     */
    public function update(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $request->validate([
                'bahan_id' => 'required|exists:bahan,id',
                'jumlah'   => 'required|numeric|min:0.001',
                'supplier' => 'required|string|max:255'
            ]);

            $barangMasuk = BarangMasuk::findOrFail($id);

            $jumlahLama = $barangMasuk->jumlah;        // nilai lama (misal 2.000)
            $jumlahBaru = $request->jumlah;           // nilai baru (misal 3.500)
            $bahanLama  = $barangMasuk->bahan_id;
            $bahanBaru  = $request->bahan_id;

            // 1. Kurangi stok dari bahan lama
            StokGudang::where('bahan_id', $bahanLama)->decrement('stok', $jumlahLama);

            // 2. Tambahkan ke bahan baru (bisa sama atau beda)
            StokGudang::updateOrCreate(
                ['bahan_id' => $bahanBaru],
                ['stok' => DB::raw('stok + ' . $jumlahBaru)]
            );

            // 3. Update record
            $barangMasuk->update([
                'bahan_id' => $bahanBaru,
                'jumlah'   => $jumlahBaru,
                'supplier' => $request->supplier
            ]);

            return response()->json([
                'message' => 'Barang masuk berhasil diperbarui!',
                'data'    => $barangMasuk->load('bahan')
            ], 200);

        } catch (\Exception $e) {
            Log::error('Barang Masuk Update Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * DESTROY – Hapus barang masuk
     */
    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $barangMasuk = BarangMasuk::findOrFail($id);
            $jumlah      = $barangMasuk->jumlah;

            $barangMasuk->delete();

            // Kurangi stok gudang
            StokGudang::where('bahan_id', $barangMasuk->bahan_id)
                      ->decrement('stok', $jumlah);

            return response()->json(['message' => 'Barang masuk berhasil dihapus!'], 200);

        } catch (\Exception $e) {
            Log::error('Barang Masuk Destroy Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }
}