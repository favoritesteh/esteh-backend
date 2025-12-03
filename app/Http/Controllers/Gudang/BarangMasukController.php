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

    public function store(Request $request)
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

            $jumlah = $request->jumlah;

            $masuk = BarangMasuk::create([
                'bahan_id' => $request->bahan_id,
                'jumlah'   => $jumlah,
                'tanggal'  => now(),
                'supplier' => $request->supplier
            ]);

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
            $jumlahBaru = $request->jumlah;
            $bahanLama  = $barangMasuk->bahan_id;
            $bahanBaru  = $request->bahan_id;

            StokGudang::where('bahan_id', $bahanLama)->decrement('stok', $jumlahLama);

            StokGudang::updateOrCreate(
                ['bahan_id' => $bahanBaru],
                ['stok' => DB::raw('stok + ' . $jumlahBaru)]
            );

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

    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $barangMasuk = BarangMasuk::findOrFail($id);
            $jumlah      = $barangMasuk->jumlah;

            $barangMasuk->delete();

            StokGudang::where('bahan_id', $barangMasuk->bahan_id)
                      ->decrement('stok', $jumlah);

            return response()->json(['message' => 'Barang masuk berhasil dihapus!'], 200);

        } catch (\Exception $e) {
            Log::error('Barang Masuk Destroy Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }
}