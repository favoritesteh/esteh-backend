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

                // Hitung gramasi: Input * (Isi * Berat)
                $totalGram = $jumlahInput * ($bahan->isi_per_satuan * $bahan->berat_per_isi);

                $masuk = BarangMasuk::create([
                    'bahan_id' => $request->bahan_id,
                    'jumlah'   => $jumlahInput,
                    'tanggal'  => now(),
                    'supplier' => $request->supplier
                ]);

                // Tambah Stok Gudang
                $stok = StokGudang::firstOrCreate(
                    ['bahan_id' => $request->bahan_id],
                    ['stok' => 0]
                );
                $stok->increment('stok', $totalGram);

                return response()->json([
                    'message' => 'Barang masuk berhasil dicatat!',
                    'data'    => $masuk->load('bahan')
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('BARANG MASUK STORE ERROR: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * UPDATE: Mengoreksi data barang masuk & menyesuaikan stok gudang otomatis.
     */
    public function update(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            // Validasi Input
            $request->validate([
                'bahan_id' => 'required|exists:bahan,id',
                'jumlah'   => 'required|numeric|min:0.001',
                'supplier' => 'required|string|max:255'
            ]);

            return DB::transaction(function () use ($request, $id) {
                // 1. Ambil Data Lama (Existing)
                $barangMasuk = BarangMasuk::findOrFail($id);
                
                // Ambil info bahan lama untuk hitung konversi mundur
                $bahanLama = Bahan::findOrFail($barangMasuk->bahan_id);
                $gramLama  = $barangMasuk->jumlah * ($bahanLama->isi_per_satuan * $bahanLama->berat_per_isi);

                // 2. KOREKSI NEGATIF: Kurangi stok gudang seolah transaksi lama tidak pernah terjadi
                // Kita gunakan decrement, biarkan negatif jika memang stok fisik sudah terpakai (audit trail)
                StokGudang::where('bahan_id', $barangMasuk->bahan_id)->decrement('stok', $gramLama);

                // ---------------------------------------------------------

                // 3. Siapkan Data Baru
                $bahanBaru = Bahan::findOrFail($request->bahan_id);
                $jumlahBaru = (float) $request->jumlah;
                $gramBaru  = $jumlahBaru * ($bahanBaru->isi_per_satuan * $bahanBaru->berat_per_isi);

                // 4. Update Record Barang Masuk
                $barangMasuk->update([
                    'bahan_id' => $request->bahan_id,
                    'jumlah'   => $jumlahBaru,
                    'supplier' => $request->supplier,
                    // Tanggal boleh diupdate atau dibiarkan (di sini kita biarkan tetap tanggal asli)
                ]);

                // 5. KOREKSI POSITIF: Tambahkan stok gudang berdasarkan data baru
                $stokGudangBaru = StokGudang::firstOrCreate(
                    ['bahan_id' => $request->bahan_id],
                    ['stok' => 0]
                );
                $stokGudangBaru->increment('stok', $gramBaru);

                return response()->json([
                    'message' => 'Barang masuk berhasil dikoreksi, stok telah disesuaikan!',
                    'data'    => $barangMasuk->load('bahan')
                ], 200);
            });

        } catch (\Exception $e) {
            Log::error('BARANG MASUK UPDATE ERROR: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal update: ' . $e->getMessage()], 500);
        }
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

                // Hitung berapa gram yang harus dibatalkan
                $gramDihapus = $barangMasuk->jumlah * ($bahan->isi_per_satuan * $bahan->berat_per_isi);

                // Kurangi stok gudang
                StokGudang::where('bahan_id', $barangMasuk->bahan_id)->decrement('stok', $gramDihapus);
                
                $barangMasuk->delete();

                return response()->json(['message' => 'Barang masuk dihapus & stok dikurangi'], 200);
            });
        } catch (\Exception $e) {
            Log::error('BARANG MASUK DESTROY ERROR: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus'], 500);
        }
    }
}