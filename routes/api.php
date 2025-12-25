<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::options('/{any}', function (Request $request) {
    return response()->json(['status' => 'OK'], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
})->where('any', '.*');

Route::middleware('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // === ROLE: OWNER & SUPERVISOR ===
        Route::middleware('role:owner,supervisor')->group(function () {
            Route::apiResource('outlets', \App\Http\Controllers\OwnerSupervisor\OutletController::class);
            Route::apiResource('users', \App\Http\Controllers\OwnerSupervisor\UserController::class);
            Route::get('dashboard', [\App\Http\Controllers\OwnerSupervisor\DashboardController::class, 'index']);
            Route::get('stok-detail', [\App\Http\Controllers\OwnerSupervisor\DashboardController::class, 'stokDetail']);
            Route::get('laporan/pendapatan', [\App\Http\Controllers\OwnerSupervisor\LaporanController::class, 'pendapatan']);
            Route::get('laporan/transaksi-detail', [\App\Http\Controllers\OwnerSupervisor\LaporanController::class, 'transaksiDetail']);
        });

        // === ROLE: GUDANG ===
        Route::prefix('gudang')->group(function () {
            Route::middleware('role:gudang')->group(function () {
                Route::apiResource('kategori', \App\Http\Controllers\Gudang\KategoriController::class); // CRUD Kategori baru
                Route::apiResource('bahan', \App\Http\Controllers\Gudang\BahanController::class);
                Route::apiResource('barang-masuk', \App\Http\Controllers\Gudang\BarangMasukController::class);
                Route::get('stok', [\App\Http\Controllers\Gudang\StokController::class, 'gudang']);
                Route::apiResource('permintaan-stok', \App\Http\Controllers\Gudang\PermintaanStokController::class);
                Route::apiResource('barang-keluar', \App\Http\Controllers\Gudang\BarangKeluarController::class);
            });
        });

        // === ROLE: KARYAWAN ===
        Route::middleware('role:karyawan')->group(function () {
            Route::apiResource('produk', \App\Http\Controllers\Karyawan\ProdukController::class);
            Route::apiResource('transaksi', \App\Http\Controllers\Karyawan\TransaksiController::class);
            Route::get('stok/outlet', [\App\Http\Controllers\Karyawan\StokController::class, 'outlet']);
            Route::apiResource('permintaan-stok', \App\Http\Controllers\Karyawan\PermintaanStokController::class);
            Route::get('bahan-gudang', [\App\Http\Controllers\Karyawan\BahanController::class, 'index']);
        });
    });
});