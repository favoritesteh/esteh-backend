<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Komposisi;
use Illuminate\Http\Request;
// Gunakan Library Native Cloudinary (Manual Bypass)
use Cloudinary\Cloudinary;

class ProdukController extends Controller
{
    public function index()
    {
        return Produk::where('is_available', true)->with('komposisi.bahan')->get();
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'karyawan') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'nama' => 'required|string',
            'harga' => 'required|numeric',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:5048',
            'komposisi' => 'required|array|min:1',
            'komposisi.*.bahan_id' => 'required|exists:bahan,id',
            'komposisi.*.quantity' => 'required|numeric|min:0.001'
        ]);

        $gambarUrl = null;

        // --- BYPASS UPLOAD MANUAL (STORE) ---
        if ($request->hasFile('gambar')) {
            // Inisialisasi Manual (Pasti Berhasil)
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
                $request->file('gambar')->getRealPath(),
                ['folder' => 'produk']
            );
            
            $gambarUrl = $uploaded['secure_url'];
        }
        // ------------------------------------

        $produk = Produk::create([
            'nama' => $request->nama,
            'harga' => $request->harga,
            'gambar' => $gambarUrl
        ]);

        foreach ($request->komposisi as $k) {
            Komposisi::create([
                'produk_id' => $produk->id,
                'bahan_id' => $k['bahan_id'],
                'quantity' => $k['quantity']
            ]);
        }

        return response()->json($produk->load('komposisi.bahan'), 201);
    }

    public function show(Produk $produk)
    {
        return $produk->load('komposisi.bahan');
    }

    public function update(Request $request, Produk $produk)
    {
        if ($request->user()->role !== 'karyawan') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'nama' => 'required|string',
            'harga' => 'required|numeric',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:5048',
            'komposisi' => 'required|array|min:1',
            'komposisi.*.bahan_id' => 'required|exists:bahan,id',
            'komposisi.*.quantity' => 'required|numeric|min:0.001'
        ]);

        // --- BYPASS UPLOAD MANUAL (UPDATE) ---
        if ($request->hasFile('gambar')) {
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
                $request->file('gambar')->getRealPath(),
                ['folder' => 'produk']
            );

            $produk->gambar = $uploaded['secure_url'];
        }
        // -------------------------------------

        $produk->update([
            'nama' => $request->nama,
            'harga' => $request->harga,
            'gambar' => $produk->gambar
        ]);

        $produk->komposisi()->delete();
        foreach ($request->komposisi as $k) {
            Komposisi::create([
                'produk_id' => $produk->id,
                'bahan_id' => $k['bahan_id'],
                'quantity' => $k['quantity']
            ]);
        }

        return response()->json($produk->load('komposisi.bahan'));
    }

    public function destroy(Produk $produk, Request $request)
    {
        if ($request->user()->role !== 'karyawan') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $produk->delete();
        return response()->json(['message' => 'Produk berhasil dihapus!']);
    }
}