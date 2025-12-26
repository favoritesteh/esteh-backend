<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::orderBy('nama', 'asc')->get();

        return response()->json([
            'message' => 'Data kategori berhasil diambil',
            'data' => $kategori
        ]);
    }
}