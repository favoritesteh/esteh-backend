<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Isi semua stok yang NULL menjadi 0 (penting kalau sudah ada data)
        DB::table('stok_gudang')
            ->whereNull('stok')
            ->update(['stok' => 0]);

        // 2. Ubah definisi kolom jadi default 0 dan tidak boleh null
        Schema::table('stok_gudang', function (Blueprint $table) {
            $table->decimal('stok', 10, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stok_gudang', function (Blueprint $table) {
            $table->decimal('stok', 10, 3)->nullable()->default(null)->change();
        });
    }
};