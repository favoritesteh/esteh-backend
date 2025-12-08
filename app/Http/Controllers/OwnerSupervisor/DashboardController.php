<?php

namespace App\Http\Controllers\OwnerSupervisor;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\Outlet;
use App\Models\User;
use App\Models\StokGudang;
use App\Models\StokOutlet;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $hariIni = Carbon::today();

            // Ambil semua stok gudang + nama bahan + status kritis
            $stokGudang = StokGudang::with('bahan')
                ->get()
                ->map(function ($item) {
                    $stok = floatval(str_replace(['.', ','], ['', '.'], $item->stok));
                    $minimum = $item->bahan?->stok_minimum_gudang ?? 0;

                    $item->stok_aktual = $stok;
                    $item->is_kritis = $stok <= $minimum;
                    $item->status = $stok <= $minimum ? 'Kritis' : 'Aman';

                    return $item;
                })
                ->sortBy('status')
                ->values();

            $data = [
                'total_outlet' => Outlet::count(),
                'total_karyawan' => User::where('role', 'karyawan')->count(),
                'total_gudang' => User::where('role', 'gudang')->count(),
                'pendapatan_hari_ini' => Transaksi::whereDate('tanggal', $hariIni)->sum('total'),
                'transaksi_hari_ini' => Transaksi::whereDate('tanggal', $hariIni)->count(),
                'stok_gudang' => $stokGudang,
                'jumlah_stok_kritis' => $stokGudang->where('is_kritis', true)->count(),
                'permintaan_pending' => \App\Models\PermintaanStok::where('status', 'diajukan')->count(),
            ];

            return response()->json([
                'message' => 'Data dashboard berhasil diambil',
                'data' => $data,
                'last_updated' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function stokDetail()
    {
        try {
            $user = auth()->user();
            if (!in_array($user->role, ['owner', 'supervisor'])) {
                return response()->json(['message' => 'Akses ditolak'], 403);
            }

            // Fetch warehouse stock
            $stokGudang = StokGudang::with('bahan')
                ->get()
                ->map(function ($item) {
                    $stok = floatval(str_replace(['.', ','], ['', '.'], $item->stok));
                    $minimum = $item->bahan?->stok_minimum_gudang ?? 0;

                    return [
                        'bahan_id' => $item->bahan_id,
                        'nama_bahan' => $item->bahan?->nama ?? 'Unknown',
                        'stok' => $stok,
                        'satuan' => $item->bahan?->satuan ?? 'N/A',
                        'stok_minimum' => $minimum,
                        'status' => $stok <= $minimum ? 'Kritis' : 'Aman',
                    ];
                });

            // Fetch outlet stock
            $stokOutlet = Outlet::with(['stokOutlet.bahan'])
                ->get()
                ->map(function ($outlet) {
                    $stok = $outlet->stokOutlet->map(function ($item) {
                        $stok = floatval(str_replace(['.', ','], ['', '.'], $item->stok));
                        $minimum = $item->bahan?->stok_minimum_outlet ?? 0;

                        return [
                            'bahan_id' => $item->bahan_id,
                            'nama_bahan' => $item->bahan?->nama ?? 'Unknown',
                            'stok' => $stok,
                            'satuan' => $item->bahan?->satuan ?? 'N/A',
                            'stok_minimum' => $minimum,
                            'status' => $stok <= $minimum ? 'Kritis' : 'Aman',
                        ];
                    });

                    return [
                        'outlet_id' => $outlet->id,
                        'nama_outlet' => $outlet->nama,
                        'alamat' => $outlet->alamat,
                        'stok' => $stok,
                        'jumlah_stok_kritis' => $stok->where('status', 'Kritis')->count(),
                    ];
                });

            return response()->json([
                'message' => 'Data stok berhasil diambil',
                'data' => [
                    'stok_gudang' => $stokGudang,
                    'stok_outlet' => $stokOutlet,
                    'total_stok_kritis_gudang' => $stokGudang->where('status', 'Kritis')->count(),
                    'total_stok_kritis_outlet' => $stokOutlet->sum('jumlah_stok_kritis'),
                ],
                'last_updated' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Stok Detail Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}