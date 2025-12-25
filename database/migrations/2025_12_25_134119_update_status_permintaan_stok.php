<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_stok', function (Blueprint $table) {
            // Kita tambahkan 'disetujui' dan 'ditolak' ke daftar enum
            $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'dikirim', 'diterima'])
                  ->default('diajukan')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_stok', function (Blueprint $table) {
            // Kembalikan ke enum awal jika migrasi di-rollback
            $table->enum('status', ['diajukan', 'diproses', 'dikirim', 'diterima'])
                  ->default('diajukan')
                  ->change();
        });
    }
};