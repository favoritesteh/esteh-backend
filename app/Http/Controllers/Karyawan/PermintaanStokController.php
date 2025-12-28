<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\PermintaanStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermintaanStokController extends Controller
{
    /**
     * Menampilkan daftar permintaan stok untuk outlet pengguna
     */
    public function index()
    {
        return PermintaanStok::where('outlet_id', auth()->user()->outlet_id)
            ->with('bahan')
            ->latest() // Biar yang terbaru muncul di atas
            ->get();
    }

    /**
     * Membuat permintaan stok baru
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->outlet_id) {
                return response()->json(['error' => 'Pengguna tidak memiliki outlet yang valid'], 403);
            }

            $request->validate([
                'bahan_id' => 'required|exists:bahan,id',
                'jumlah' => 'required|numeric|min:0.001'
            ]);

            $permintaan = PermintaanStok::create([
                'outlet_id' => $user->outlet_id,
                'bahan_id' => $request->bahan_id,
                'jumlah' => $request->jumlah,
                'status' => 'diajukan'
            ]);

            return response()->json($permintaan, 201);
        } catch (\Exception $e) {
            Log::error('Permintaan Stok Store Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan detail permintaan stok tertentu
     */
    public function show($id)
    {
        try {
            $permintaan = PermintaanStok::where('outlet_id', auth()->user()->outlet_id)
                ->with('bahan')
                ->findOrFail($id);

            return response()->json($permintaan);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Data tidak ditemukan atau bukan milik outlet Anda'], 404);
        }
    }

    /**
     * Update permintaan stok (Hanya jika status masih 'diajukan')
     * INI YANG TADI BIKIN ERROR 500 KARENA HILANG
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $permintaan = PermintaanStok::where('outlet_id', $user->outlet_id)->findOrFail($id);

            // Proteksi: Kalau sudah diproses gudang, jangan boleh diedit!
            if ($permintaan->status !== 'diajukan') {
                return response()->json([
                    'message' => 'Tidak bisa mengubah permintaan yang sudah diproses atau dikirim.'
                ], 403);
            }

            $request->validate([
                'bahan_id' => 'sometimes|exists:bahan,id',
                'jumlah'   => 'sometimes|numeric|min:0.001'
            ]);

            $permintaan->update($request->only(['bahan_id', 'jumlah']));

            return response()->json([
                'message' => 'Permintaan stok berhasil diperbarui',
                'data' => $permintaan->load('bahan')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Permintaan Stok Update Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan server'], 500);
        }
    }

    /**
     * Hapus permintaan stok (Hanya jika status masih 'diajukan')
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $permintaan = PermintaanStok::where('outlet_id', $user->outlet_id)->findOrFail($id);

            // Proteksi: Kalau sudah diproses, jangan boleh dihapus sembarangan
            if ($permintaan->status !== 'diajukan') {
                return response()->json([
                    'message' => 'Tidak bisa membatalkan permintaan yang sudah diproses.'
                ], 403);
            }

            $permintaan->delete();

            return response()->json(['message' => 'Permintaan stok berhasil dibatalkan/dihapus']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Permintaan Stok Destroy Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan server'], 500);
        }
    }
}