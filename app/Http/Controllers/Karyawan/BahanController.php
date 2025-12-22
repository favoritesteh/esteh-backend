<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use Illuminate\Http\Request;

class BahanController extends Controller
{
    /**
     * Menampilkan daftar referensi bahan yang ada di gudang.
     * Endpoint ini Read-Only khusus untuk Karyawan.
     *
     * Data ini biasanya digunakan untuk dropdown saat karyawan
     * ingin mengajukan permintaan stok.
     */
    public function index()
    {
        // Kita ambil ID, Nama, dan Satuan saja agar lebih ringan & aman
        $data = Bahan::select('id', 'nama', 'satuan')->orderBy('nama', 'asc')->get();

        return response()->json([
            'message' => 'Daftar referensi bahan berhasil diambil',
            'data' => $data
        ]);
    }
}