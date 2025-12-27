<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\BarangMasuk;
use App\Models\StokGudang;
use App\Models\Bahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BarangMasukController extends Controller
{
    public function index()
    {
        return BarangMasuk::with('bahan')->latest()->get();
    }

    public function show($id)
    {
        try {
            $data = BarangMasuk::with('bahan')->findOrFail($id);
            return response()->json([
                'message' => 'Detail barang masuk ditemukan',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validasi Role (Double Check)
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $request->validate([
                'bahan_id' => 'required|exists:bahan,id',
                'jumlah'   => 'required|numeric|min:0.001',
                'supplier' => 'required|string|max:255'
            ]);

            return DB::transaction(function () use ($request) {
                $bahan = Bahan::findOrFail($request->bahan_id);
                $jumlahInput = (float) $request->jumlah;

                // LOGIKA KONVERSI: (Input * (Isi per Satuan * Berat per Isi))
                // Misal: 1 Kotak * (24 Buah * 350 Gram) = 8.400 Gram
                $totalGram = $jumlahInput * ($bahan->isi_per_satuan * $bahan->berat_per_isi);

                // 1. Simpan riwayat barang masuk (tetap simpan angka unit asli '1' untuk laporan)
                $masuk = BarangMasuk::create([
                    'bahan_id' => $request->bahan_id,
                    'jumlah'   => $jumlahInput,
                    'tanggal'  => now(),
                    'supplier' => $request->supplier
                ]);

                // 2. Update Stok Gudang (Menambah dalam satuan GRAM)
                $stok = StokGudang::firstOrCreate(
                    ['bahan_id' => $request->bahan_id],
                    ['stok' => 0]
                );
                $stok->increment('stok', $totalGram);

                return response()->json([
                    'message' => 'Barang masuk berhasil dicatat dan dikonversi ke gram!',
                    'data'    => $masuk->load('bahan'),
                    'total_gram_ditambahkan' => $totalGram
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('BARANG MASUK GAGAL: ' . $e->getMessage());
            return response()->json([
                'error'   => 'Gagal menyimpan barang masuk',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'message' => 'Fitur update dinonaktifkan demi konsistensi stok. Silakan hapus dan input ulang jika ada kesalahan.'
        ], 405);
    }

    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            return DB::transaction(function () use ($id) {
                $barangMasuk = BarangMasuk::findOrFail($id);
                $bahan = Bahan::findOrFail($barangMasuk->bahan_id);

                // Hitung kembali berapa gram yang dulu ditambahkan untuk dikurangi
                $gramDihapus = $barangMasuk->jumlah * ($bahan->isi_per_satuan * $bahan->berat_per_isi);

                // Kurangi stok gudang
                StokGudang::where('bahan_id', $barangMasuk->bahan_id)->decrement('stok', $gramDihapus);
                
                $barangMasuk->delete();

                return response()->json(['message' => 'Barang masuk berhasil dihapus dan stok dikoreksi'], 200);
            });
        } catch (\Exception $e) {
            Log::error('DELETE BARANG MASUK GAGAL: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus data'], 500);
        }
    }
}