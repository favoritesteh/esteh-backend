<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| GLOBAL OPTIONS HANDLER (EXPRESS-LIKE CORS)
|--------------------------------------------------------------------------
| Ini MENIRU app.use(cors()) di Express
| Menangani semua preflight OPTIONS
|--------------------------------------------------------------------------
*/
Route::options('/{any}', function (Request $request) {
    return response()->json(['status' => 'OK'], 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
})->where('any', '.*');


/*
|--------------------------------------------------------------------------
| API ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('api')->group(function () {

    // ================= AUTH TANPA LOGIN =================
    Route::post('/login', [AuthController::class, 'login']);

    // ================= AUTH SANCTUM =================
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // OWNER & SUPERVISOR
        Route::middleware('role:owner,supervisor')->group(function () {
            Route::apiResource('outlets', \App\Http\Controllers\OwnerSupervisor\OutletController::class);
            Route::apiResource('users', \App\Http\Controllers\OwnerSupervisor\UserController::class);
            Route::get('laporan/pendapatan', [\App\Http\Controllers\OwnerSupervisor\LaporanController::class, 'pendapatan']);
            Route::get('laporan/export', [\App\Http\Controllers\OwnerSupervisor\LaporanController::class, 'exportCsv']);
            Route::get('dashboard', [\App\Http\Controllers\OwnerSupervisor\DashboardController::class, 'index']);
            Route::get('stok-detail', [\App\Http\Controllers\OwnerSupervisor\DashboardController::class, 'stokDetail']);
        });

        // GUDANG
        Route::prefix('gudang')->group(function () {
            Route::middleware('role:gudang')->group(function () {
                Route::apiResource('bahan', \App\Http\Controllers\Gudang\BahanController::class);
                Route::apiResource('barang-masuk', \App\Http\Controllers\Gudang\BarangMasukController::class);
                Route::get('stok', [\App\Http\Controllers\Gudang\StokController::class, 'gudang']);
            });
        });

        // KARYAWAN
        Route::middleware('role:karyawan')->group(function () {
            Route::apiResource('produk', \App\Http\Controllers\Karyawan\ProdukController::class);
            Route::apiResource('transaksi', \App\Http\Controllers\Karyawan\TransaksiController::class);
            Route::get('stok/outlet', [\App\Http\Controllers\Karyawan\StokController::class, 'outlet']);
        });
    });
});
