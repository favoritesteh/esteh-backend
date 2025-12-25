<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\PermintaanStok;
use App\Models\StokGudang;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PermintaanStokController extends Controller
{
    /**
     * Menampilkan semua daftar permintaan stok dari outlet.
     */
    public function index()
    {
        return PermintaanStok::with(['bahan', 'outlet'])->latest()->get();
    }

    /**
     * Menampilkan detail permintaan stok tertentu.
     */
    public function show($id)
    {
        return PermintaanStok::with(['bahan', 'outlet'])->findOrFail($id);
    }

    /**
     * Update status permintaan stok (Approval & Pengiriman Otomatis).
     */
    public function update(Request $request, $id)
    {
        try {
            // Pastikan hanya role gudang yang bisa mengakses
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            // Validasi status yang dikirim
            $request->validate([
                'status' => 'required|in:disetujui,ditolak,dikirim'
            ]);

            $permintaan = PermintaanStok::with('bahan')->findOrFail($id);

            // LOGIKA 1: PROSES APPROVAL (Disetujui atau Ditolak)
            // Pada tahap ini stok gudang belum berkurang.
            if ($request->status === 'disetujui' || $request->status === 'ditolak') {
                $permintaan->update(['status' => $request->status]);
                return response()->json([
                    'message' => "Permintaan stok berhasil di-" . $request->status,
                    'data' => $permintaan
                ]);
            }

            // LOGIKA 2: PROSES PENGIRIMAN (Dikirim)
            // Stok gudang akan berkurang otomatis dalam satuan GRAM di tahap ini.
            if ($request->status === 'dikirim') {
                // Syarat: Harus sudah disetujui sebelumnya
                if ($permintaan->status !== 'disetujui') {
                    return response()->json([
                        'error' => 'Validasi Gagal',
                        'message' => 'Barang tidak bisa dikirim karena belum disetujui oleh gudang.'
                    ], 400);
                }

                return DB::transaction(function () use ($permintaan) {
                    $bahan = $permintaan->bahan;
                    
                    // HITUNG TOTAL GRAM OTOMATIS: (Jumlah Unit * (Isi * Berat))
                    // Contoh: Minta 2 (bungkus) * (1 * 500g) = 1000g
                    $totalGramKeluar = $permintaan->jumlah * ($bahan->isi_per_satuan * $bahan->berat_per_isi);

                    $stokGudang = StokGudang::where('bahan_id', $permintaan->bahan_id)->first();

                    // Cek ketersediaan stok di gudang dalam satuan GRAM
                    if (!$stokGudang || $stokGudang->stok < $totalGramKeluar) {
                        return response()->json([
                            'error' => 'Stok Gagal',
                            'message' => 'Stok di gudang tidak mencukupi. Tersedia: ' . ($stokGudang->stok ?? 0) . 'g, Dibutuhkan: ' . $totalGramKeluar . 'g'
                        ], 400);
                    }

                    // 1. Kurangi stok gudang dalam satuan GRAM
                    $stokGudang->decrement('stok', $totalGramKeluar);

                    // 2. Buat record Barang Keluar secara otomatis (Simpan angka GRAM untuk outlet)
                    BarangKeluar::create([
                        'bahan_id' => $permintaan->bahan_id,
                        'outlet_id' => $permintaan->outlet_id,
                        'permintaan_id' => $permintaan->id,
                        'jumlah' => $totalGramKeluar, // Nilai gramasi dikirim ke outlet
                        'tanggal_keluar' => now(),
                        'status' => 'dikirim'
                    ]);

                    // 3. Update status permintaan menjadi 'dikirim'
                    $permintaan->update(['status' => 'dikirim']);

                    return response()->json([
                        'message' => 'Barang berhasil dikirim dan dikonversi otomatis ke gram!',
                        'total_gram_dikirim' => $totalGramKeluar,
                        'data' => $permintaan->load('bahan')
                    ]);
                });
            }

        } catch (\Exception $e) {
            // Log error asli agar bisa dicek di console Railway
            Log::error('UPDATE PERMINTAAN STOK GAGAL: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan sistem',
                'message' => $e->getMessage() 
            ], 500);
        }
    }
}