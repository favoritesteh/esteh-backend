<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    // Melihat semua kategori
    public function index()
    {
        return Kategori::all();
    }

    // Menambah kategori baru (Hanya Gudang)
    public function store(Request $request)
    {
        if ($request->user()->role !== 'gudang') {
            return response()->json(['message' => 'Akses ditolak!'], 403);
        }

        $validated = $request->validate([
            'nama' => 'required|string|unique:kategoris,nama|max:255'
        ]);

        $kategori = Kategori::create($validated);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan!',
            'data' => $kategori
        ], 201);
    }

    // Update kategori (Hanya Gudang)
    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'gudang') {
            return response()->json(['message' => 'Akses ditolak!'], 403);
        }

        $kategori = Kategori::findOrFail($id);
        
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:kategoris,nama,' . $id
        ]);

        $kategori->update($validated);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui!',
            'data' => $kategori
        ]);
    }

    // Hapus kategori (Hanya Gudang)
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'gudang') {
            return response()->json(['message' => 'Akses ditolak!'], 403);
        }

        $kategori = Kategori::findOrFail($id);
        $kategori->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus!']);
    }
}