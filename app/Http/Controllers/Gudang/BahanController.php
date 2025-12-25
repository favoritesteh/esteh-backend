<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use Illuminate\Http\Request;

class BahanController extends Controller
{
    public function index()
    {
        return Bahan::all();
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'gudang') {
            return response()->json(['message' => 'Akses ditolak. Hanya gudang!'], 403);
        }

        $validated = $request->validate([
            'nama' => 'required|string|unique:bahan,nama|max:255',
            'satuan' => 'required|string',
            'isi_per_satuan' => 'required|integer|min:1', // Menentukan isi (misal: 24 buah)
            'berat_per_isi' => 'required|numeric|min:0',  // Menentukan berat (misal: 350 gram)
            'stok_minimum_gudang' => 'required|numeric|min:0',
            'stok_minimum_outlet' => 'required|numeric|min:0',
        ]);

        $bahan = Bahan::create($validated);

        return response()->json([
            'message' => 'Bahan berhasil ditambahkan dengan standar konversi!',
            'data' => $bahan
        ], 201);
    }

    public function show(Bahan $bahan)
    {
        return $bahan;
    }

    public function update(Request $request, Bahan $bahan)
    {
        if ($request->user()->role !== 'gudang') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:bahan,nama,' . $bahan->id,
            'satuan' => 'required|string',
            'isi_per_satuan' => 'required|integer|min:1',
            'berat_per_isi' => 'required|numeric|min:0',
            'stok_minimum_gudang' => 'required|numeric|min:0',
            'stok_minimum_outlet' => 'required|numeric|min:0',
        ]);

        $bahan->update($validated);

        return response()->json([
            'message' => 'Bahan dan data konversi berhasil diupdate!',
            'data' => $bahan
        ]);
    }

    public function destroy(Bahan $bahan, Request $request)
    {
        if ($request->user()->role !== 'gudang') {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $bahan->delete();
        return response()->json(['message' => 'Bahan berhasil dihapus!']);
    }
}