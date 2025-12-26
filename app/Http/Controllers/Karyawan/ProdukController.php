<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Komposisi;
use Illuminate\Http\Request;
use Cloudinary\Cloudinary;

class ProdukController extends Controller
{
    /**
     * Menampilkan daftar produk beserta kategorinya (Read-Only)
     */
    public function index()
    {
        // Menampilkan produk berserta kategorinya
        return Produk::where('is_available', true)
            ->with(['komposisi.bahan', 'kategori'])
            ->get();
    }

    /**
     * Menambahkan produk baru (Create)
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'karyawan') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'nama' => 'required|string',
            'kategori_id' => 'required|exists:kategoris,id',
            'harga' => 'required|numeric',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:5048',
            'komposisi' => 'required|array|min:1',
            'komposisi.*.bahan_id' => 'required|exists:bahan,id',
            'komposisi.*.quantity' => 'required|numeric|min:0.001'
        ]);

        $gambarUrl = null;
        if ($request->hasFile('gambar')) {
            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => 'duh9v4hyi',
                    'api_key'    => '839695134185465',
                    'api_secret' => 'TnOly4DFI4JbYvdARmEQjIatvZc',
                ],
                'url' => ['secure' => true]
            ]);

            $uploaded = $cloudinary->uploadApi()->upload(
                $request->file('gambar')->getRealPath(),
                ['folder' => 'produk']
            );
            $gambarUrl = $uploaded['secure_url'];
        }

        $produk = Produk::create([
            'nama' => $request->nama,
            'kategori_id' => $request->kategori_id,
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

        return response()->json($produk->load('komposisi.bahan', 'kategori'), 201);
    }

    /**
     * Menampilkan detail satu produk (Read One)
     * Perbaikan: Method ini sebelumnya tidak ada dan menyebabkan Error 500
     */
    public function show($id)
    {
        $produk = Produk::with(['komposisi.bahan', 'kategori'])->find($id);

        if (!$produk) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json($produk);
    }

    /**
     * Memperbarui data produk (Update)
     */
    public function update(Request $request, Produk $produk)
    {
        if ($request->user()->role !== 'karyawan') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'nama' => 'required|string',
            'kategori_id' => 'required|exists:kategoris,id',
            'harga' => 'required|numeric',
            'komposisi' => 'required|array|min:1'
        ]);

        if ($request->hasFile('gambar')) {
            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => 'duh9v4hyi',
                    'api_key'    => '839695134185465',
                    'api_secret' => 'TnOly4DFI4JbYvdARmEQjIatvZc',
                ],
                'url' => ['secure' => true]
            ]);

            $uploaded = $cloudinary->uploadApi()->upload(
                $request->file('gambar')->getRealPath(),
                ['folder' => 'produk']
            );
            $produk->gambar = $uploaded['secure_url'];
        }

        $produk->update([
            'nama' => $request->nama,
            'kategori_id' => $request->kategori_id,
            'harga' => $request->harga,
            'gambar' => $produk->gambar
        ]);

        // Reset komposisi lama dan buat yang baru
        $produk->komposisi()->delete();
        foreach ($request->komposisi as $k) {
            Komposisi::create([
                'produk_id' => $produk->id,
                'bahan_id' => $k['bahan_id'],
                'quantity' => $k['quantity']
            ]);
        }

        return response()->json($produk->load('komposisi.bahan', 'kategori'));
    }

    /**
     * Menghapus produk (Delete)
     * Perbaikan: Method ini sebelumnya tidak ada dan menyebabkan Error 500
     */
    public function destroy(Request $request, Produk $produk)
    {
        if ($request->user()->role !== 'karyawan') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        // Hapus produk (Komposisi akan terhapus otomatis jika ada cascade di database, 
        // atau bisa dihapus manual $produk->komposisi()->delete() jika perlu)
        $produk->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }
}