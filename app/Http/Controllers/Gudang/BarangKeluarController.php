<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use App\Models\StokGudang;
use App\Models\StokOutlet;
use App\Models\PermintaanStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// Import Class Cloudinary Native (v2)
use Cloudinary\Cloudinary;

class BarangKeluarController extends Controller
{
    public function index()
    {
        try {
            $barangKeluar = BarangKeluar::with(['bahan', 'outlet'])->latest()->get();
            return response()->json([
                'message' => 'Daftar barang keluar ditemukan',
                'data' => $barangKeluar
            ], 200);
        } catch (\Exception $e) {
            Log::error('Barang Keluar Index Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $barangKeluar = BarangKeluar::with(['bahan', 'outlet'])->findOrFail($id);
            return response()->json([
                'message' => 'Detail barang keluar ditemukan',
                'data' => $barangKeluar
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
                'permintaan_id' => 'required|exists:permintaan_stok,id'
            ]);

            return DB::transaction(function () use ($request) {
                $permintaan = PermintaanStok::findOrFail($request->permintaan_id);

                if ($permintaan->status !== 'diajukan') {
                    return response()->json(['message' => 'Permintaan sudah diproses'], 400);
                }

                $stokGudang = StokGudang::where('bahan_id', $permintaan->bahan_id)->first();

                if (!$stokGudang || $stokGudang->stok < $permintaan->jumlah) {
                    return response()->json(['message' => 'Stok gudang tidak cukup'], 400);
                }

                $keluar = BarangKeluar::create([
                    'bahan_id' => $permintaan->bahan_id,
                    'outlet_id' => $permintaan->outlet_id,
                    'permintaan_id' => $permintaan->id,
                    'jumlah' => $permintaan->jumlah,
                    'tanggal_keluar' => now(),
                    'status' => 'dikirim'
                ]);

                $stokGudang->decrement('stok', $permintaan->jumlah);
                $permintaan->update(['status' => 'dikirim']);

                return response()->json([
                    'message' => 'Barang keluar berhasil dicatat!',
                    'data' => $keluar->load(['bahan', 'outlet'])
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Barang Keluar Store Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'Fitur update dinonaktifkan demi konsistensi stok'], 405);
    }

    public function destroy(Request $request, $id)
    {
        try {
            if ($request->user()->role !== 'gudang') {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }
            
            $barangKeluar = BarangKeluar::findOrFail($id);
            
            StokGudang::where('bahan_id', $barangKeluar->bahan_id)->increment('stok', $barangKeluar->jumlah);
            $barangKeluar->delete();

            return response()->json(['message' => 'Barang keluar dibatalkan/dihapus'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus'], 500);
        }
    }

    /**
     * Menerima barang keluar.
     * UPDATE: FIX ID COLLISION
     * Prioritas pencarian dibalik: Cek ID Permintaan DULU, baru Cek ID Barang Keluar.
     */
    public function terima(Request $request, $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                // -----------------------------------------------------------
                // 1. PRIORITAS UTAMA: Cari berdasarkan 'permintaan_id'
                // -----------------------------------------------------------
                // Logikanya: Frontend/User kemungkinan besar mengirimkan ID Permintaan
                // karena itu yang tampil di daftar 'Pesanan Saya'.
                // Kita cari record BarangKeluar yang punya permintaan_id = $id
                $keluar = BarangKeluar::where('permintaan_id', $id)->first();

                // -----------------------------------------------------------
                // 2. BACKUP PLAN: Cari berdasarkan 'id' Barang Keluar
                // -----------------------------------------------------------
                // Jika tidak ketemu lewat permintaan_id, baru kita anggap user mengirim ID asli BarangKeluar.
                // Ini berguna untuk admin gudang atau testing via Postman pakai ID tabel asli.
                if (!$keluar) {
                    $keluar = BarangKeluar::find($id);
                }

                // Jika masih tidak ketemu juga di kedua tabel, throw error 404
                if (!$keluar) {
                    return response()->json([
                        'error' => 'Data Tidak Ditemukan',
                        'message' => 'Tidak ditemukan data pengiriman dengan ID Permintaan atau ID Barang Keluar: ' . $id
                    ], 404);
                }
                // -----------------------------------------------------------

                // Validasi akses karyawan
                if ($request->user()->role === 'karyawan') {
                    if (!$request->user()->outlet_id || $keluar->outlet_id !== $request->user()->outlet_id) {
                        return response()->json(['message' => 'Anda hanya bisa menerima barang untuk outlet Anda'], 403);
                    }
                }

                // Cek status, pastikan 'dikirim'
                if ($keluar->status !== 'dikirim') {
                    // Beri pesan lebih spesifik agar user tidak bingung
                    $statusMsg = $keluar->status === 'diterima' ? 'Barang ini SUDAH diterima sebelumnya.' : 'Barang belum dikirim oleh gudang.';
                    return response()->json(['message' => $statusMsg], 400);
                }

                // --- CLOUDINARY UPLOAD (MANUAL BYPASS) ---
                if ($request->hasFile('bukti_foto')) {
                    try {
                        $cloudinary = new Cloudinary([
                            'cloud' => [
                                'cloud_name' => 'duh9v4hyi',
                                'api_key'    => '839695134185465',
                                'api_secret' => 'TnOly4DFI4JbYvdARmEQjIatvZc',
                            ],
                            'url' => [
                                'secure' => true
                            ]
                        ]);

                        $uploaded = $cloudinary->uploadApi()->upload(
                            $request->file('bukti_foto')->getRealPath(), 
                            ['folder' => 'bukti_penerimaan']
                        );
                        
                        $keluar->bukti_foto = $uploaded['secure_url'];
                    } catch (\Exception $e) {
                        Log::error('Upload Gagal: ' . $e->getMessage());
                        // Opsional: return error atau lanjut tanpa foto
                    }
                }
                // -----------------------------------------

                // Update Status Barang Keluar
                $keluar->status = 'diterima';
                $keluar->save();

                // Update Status di Permintaan Stok juga (Sync)
                if ($keluar->permintaan_id) {
                    PermintaanStok::where('id', $keluar->permintaan_id)->update(['status' => 'diterima']);
                }

                // Tambah Stok Outlet
                $stokOutlet = StokOutlet::firstOrCreate(
                    ['outlet_id' => $keluar->outlet_id, 'bahan_id' => $keluar->bahan_id],
                    ['stok' => 0]
                );
                $stokOutlet->increment('stok', $keluar->jumlah);

                return response()->json([
                    'message' => 'Barang berhasil diterima dan stok outlet diperbarui!',
                    'data' => $keluar->load(['bahan', 'outlet'])
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Barang Keluar Terima Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}