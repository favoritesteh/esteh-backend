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
    public function index()
    {
        return PermintaanStok::with(['bahan', 'outlet'])->latest()->get();
    }

    public function show($id)
    {
        return PermintaanStok::with(['bahan', 'outlet'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            $request->validate([
                'status' => 'required|in:disetujui,ditolak,dikirim'
            ]);

            $permintaan = PermintaanStok::findOrFail($id);

            // LOGIKA 1: APPROVAL (Setuju/Tolak)
            // Stok belum berkurang di tahap ini
            if ($request->status === 'disetujui' || $request->status === 'ditolak') {
                $permintaan->update(['status' => $request->status]);
                return response()->json([
                    'message' => "Permintaan stok berhasil di-" . $request->status,
                    'data' => $permintaan
                ]);
            }

            // LOGIKA 2: PENGIRIMAN (Dikirim)
            // Stok gudang berkurang di tahap ini
            if ($request->status === 'dikirim') {
                // Syarat: Harus sudah disetujui sebelumnya
                if ($permintaan->status !== 'disetujui') {
                    return response()->json([
                        'error' => 'Validasi Gagal',
                        'message' => 'Barang tidak bisa dikirim karena belum disetujui oleh gudang.'
                    ], 400);
                }

                return DB::transaction(function () use ($permintaan) {
                    $stokGudang = StokGudang::where('bahan_id', $permintaan->bahan_id)->first();

                    // Cek ketersediaan stok di gudang
                    if (!$stokGudang || $stokGudang->stok < $permintaan->jumlah) {
                        return response()->json([
                            'error' => 'Stok Gagal',
                            'message' => 'Stok di gudang tidak mencukupi untuk permintaan ini.'
                        ], 400);
                    }

                    // 1. Kurangi stok gudang (sudah dalam satuan gram)
                    $stokGudang->decrement('stok', $permintaan->jumlah);

                    // 2. Buat record Barang Keluar secara otomatis
                    BarangKeluar::create([
                        'bahan_id' => $permintaan->bahan_id,
                        'outlet_id' => $permintaan->outlet_id,
                        'permintaan_id' => $permintaan->id,
                        'jumlah' => $permintaan->jumlah,
                        'tanggal_keluar' => now(),
                        'status' => 'dikirim'
                    ]);

                    // 3. Update status permintaan menjadi dikirim
                    $permintaan->update(['status' => 'dikirim']);

                    return response()->json([
                        'message' => 'Barang berhasil dikirim. Stok gudang telah berkurang.',
                        'data' => $permintaan->load('bahan')
                    ]);
                });
            }

        } catch (\Exception $e) {
            Log::error('UPDATE PERMINTAAN STOK GAGAL: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan sistem'], 500);
        }
    }
}