<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| FIX CORS 405 (Preflight OPTIONS)
|--------------------------------------------------------------------------
|
| Ini memastikan semua request OPTIONS akan dijawab 200 OK,
| sehingga browser mengizinkan request utama (POST/GET/PUT/DELETE).
|
*/
// Route::options('/{any}', function () {
//     return response()->json(['status' => 'OK'], 200);
// })->where('any', '.*');


/*
|--------------------------------------------------------------------------
| AUTH TANPA MIDDLEWARE
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| AUTH SANCTUM
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ---------- Auth ----------
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ====================== OWNER & SUPERVISOR ======================
    Route::middleware('role:owner,supervisor')->group(function () {
        Route::apiResource('outlets', \App\Http\Controllers\OwnerSupervisor\OutletController::class);
        Route::apiResource('users', \App\Http\Controllers\OwnerSupervisor\UserController::class);
        Route::get('laporan/pendapatan', [\App\Http\Controllers\OwnerSupervisor\LaporanController::class, 'pendapatan']);
        Route::get('laporan/export', [\App\Http\Controllers\OwnerSupervisor\LaporanController::class, 'exportCsv']);
        Route::get('dashboard', [\App\Http\Controllers\OwnerSupervisor\DashboardController::class, 'index']);
        Route::get('stok-detail', [\App\Http\Controllers\OwnerSupervisor\DashboardController::class, 'stokDetail']);
    });

    // ====================== GUDANG ======================
    Route::prefix('gudang')->group(function () {

        Route::middleware('role:gudang')->group(function () {
            Route::apiResource('bahan', \App\Http\Controllers\Gudang\BahanController::class);
            Route::apiResource('barang-masuk', \App\Http\Controllers\Gudang\BarangMasukController::class);
            Route::apiResource('permintaan-stok', \App\Http\Controllers\Gudang\PermintaanStokController::class)
                ->only(['index', 'show', 'update']);
            Route::get('stok', [\App\Http\Controllers\Gudang\StokController::class, 'gudang']);

            Route::apiResource('barang-keluar', \App\Http\Controllers\Gudang\BarangKeluarController::class)
                ->except(['terima']);
        });

        // Endpoint terima untuk gudang dan karyawan
        Route::post('barang-keluar/{id}/terima', 
            [\App\Http\Controllers\Gudang\BarangKeluarController::class, 'terima']
        )->middleware('role:gudang,karyawan');
    });

    // ====================== KARYAWAN (KASIR) ======================
    Route::middleware('role:karyawan')->group(function () {
        Route::apiResource('produk', \App\Http\Controllers\Karyawan\ProdukController::class);
        Route::apiResource('transaksi', \App\Http\Controllers\Karyawan\TransaksiController::class);

        Route::apiResource('permintaan-stok', \App\Http\Controllers\Karyawan\PermintaanStokController::class)
            ->only(['index', 'store', 'show']);

        Route::get('stok/outlet', [\App\Http\Controllers\Karyawan\StokController::class, 'outlet']);
    });

});
