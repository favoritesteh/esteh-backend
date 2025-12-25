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
     * Update status permintaan stok (Approval & Pengiriman).
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

            $permintaan = PermintaanStok::findOrFail($id);

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
            // Stok gudang akan berkurang di tahap ini.
            if ($request->status === 'dikirim') {
                // Syarat: Harus sudah disetujui sebelumnya (Validasi Alur)
                if ($permintaan->status !== 'disetujui') {
                    return response()->json([
                        'error' => 'Validasi Gagal',
                        'message' => 'Barang tidak bisa dikirim karena belum disetujui oleh gudang.'
                    ], 400);
                }

                return DB::transaction(function () use ($permintaan) {
                    $stokGudang = StokGudang::where('bahan_id', $permintaan->bahan_id)->first();

                    // Cek ketersediaan stok di gudang (satuan sudah dalam gram)
                    if (!$stokGudang || $stokGudang->stok < $permintaan->jumlah) {
                        return response()->json([
                            'error' => 'Stok Gagal',
                            'message' => 'Stok di gudang tidak mencukupi untuk permintaan ini.'
                        ], 400);
                    }

                    // 1. Kurangi stok gudang secara otomatis
                    $stokGudang->decrement('stok', $permintaan->jumlah);

                    // 2. Buat record di tabel Barang Keluar secara otomatis sebagai bukti pengiriman
                    BarangKeluar::create([
                        'bahan_id' => $permintaan->bahan_id,
                        'outlet_id' => $permintaan->outlet_id,
                        'permintaan_id' => $permintaan->id,
                        'jumlah' => $permintaan->jumlah,
                        'tanggal_keluar' => now(),
                        'status' => 'dikirim'
                    ]);

                    // 3. Update status permintaan menjadi 'dikirim'
                    $permintaan->update(['status' => 'dikirim']);

                    return response()->json([
                        'message' => 'Barang berhasil dikirim. Stok gudang telah berkurang otomatis.',
                        'data' => $permintaan->load('bahan')
                    ]);
                });
            }

        } catch (\Exception $e) {
            // Log error untuk pengecekan di terminal/console Railway
            Log::error('UPDATE PERMINTAAN STOK GAGAL: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan sistem',
                'message' => $e->getMessage() // Membantu debugging jika ada error database
            ], 500);
        }
    }
}