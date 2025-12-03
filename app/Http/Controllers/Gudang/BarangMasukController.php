<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\BarangMasuk;
use App\Models\StokGudang;
use Illuminate\Http\Request;
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
            $data = BarangMasuk::with('bahan')->findOrFail($id);
            return response()->json([
                'message' => 'Detail barang masuk ditemukan',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }
    }

    /**
     * POST /api/gudang/barang-masuk
     * SUDAH 100% JALAN DI MySQL, PostgreSQL, SQLite
     */
    public function store(Request $request)
    {
        try {
            // Cek role
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            // Validasi
            $request->validate([
                'bahan_id' => 'required|exists:bahan,id',
                'jumlah'   => 'required|required|numeric|min:0.001',
                'supplier' => 'required|string|max:255'
            ]);

            $jumlah = (float) $request->jumlah;

            // Simpan barang masuk
            $masuk = BarangMasuk::create([
                'bahan_id' => $request->bahan_id,
                'jumlah'   => $jumlah,
                'tanggal'  => now(),
                'supplier' => $request->supplier
            ]);

            // CARA PALING AMAN & UNIVERSAL — JALAN DI SEMUA DATABASE
            $stok = StokGudang::firstOrCreate(
                ['bahan_id' => $request->bahan_id],
                ['stok' => 0]
            );

            $stok->increment('stok', $jumlah);

            return response()->json([
                'message' => 'Barang masuk berhasil dicatat!',
                'data'    => $masuk->load('bahan')
            ], 201);

        } catch (\Exception $e) {
            Log::error('BARANG MASUK GAGAL: ' . $e->getMessage());
            return response()->json([
                'error'   => 'Gagal menyimpan barang masuk',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * UPDATE barang masuk (bahkan ganti bahan sekalipun)
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

            $jumlahLama = $barangMasuk->jumlah;
            $jumlahBaru = (float) $request->jumlah;
            $bahanLama  = $barangMasuk->bahan_id;
            $bahanBaru  = $request->bahan_id;

            // 1. Kurangi stok dari bahan lama
            StokGudang::where('bahan_id', $bahanLama)->decrement('stok', $jumlahLama);

            // 2. Tambah stok ke bahan baru
            $stokBaru = StokGudang::firstOrCreate(
                ['bahan_id' => $bahanBaru],
                ['stok' => 0]
            );
            $stokBaru->increment('stok', $jumlahBaru);

            // 3. Update record barang masuk
            $barangMasuk->update([
                'bahan_id' => $bahanBaru,
                'jumlah'   => $jumlahBaru,
                'supplier' => $request->supplier
            ]);

            return response()->json([
                'message' => 'Barang masuk berhasil diperbarui!',
                'data'    => $barangMasuk->fresh()->load('bahan')
            ], 200);

        } catch (\Exception $e) {
            Log::error('UPDATE BARANG MASUK GAGAL: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal update'], 500);
        }
    }

    /**
     * DELETE barang masuk
     */
    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $barangMasuk = BarangMasuk::findOrFail($id);
            $jumlah = $barangMasuk->jumlah;
            $bahanId = $barangMasuk->bahan_id;

            $barangMasuk->delete();

            // Kurangi stok gudang
            StokGudang::where('bahan_id', $bahanId)->decrement('stok', $jumlah);

            return response()->json(['message' => 'Barang masuk berhasil dihapus!'], 200);

        } catch (\Exception $e) {
            Log::error('DELETE BARANG MASUK GAGAL: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus'], 500);
        }
    }
}