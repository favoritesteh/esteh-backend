<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Membuat Tabel Kategori (CRUD)
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // Contoh: Teh, Kopi, Susu
            $table->timestamps();
        });

        // 2. Menambahkan Relasi Kategori ke Tabel Produk
        Schema::table('produk', function (Blueprint $table) {
            // Menambahkan foreignId yang merujuk ke tabel kategoris
            $table->foreignId('kategori_id')
                  ->nullable()
                  ->after('nama')
                  ->constrained('kategoris')
                  ->nullOnDelete(); // Jika kategori dihapus, produk tetap ada
        });

        // 3. Menambahkan Kolom Konversi ke Tabel Bahan
        Schema::table('bahan', function (Blueprint $table) {
            // isi_per_satuan: jumlah biji/unit dalam satu satuan beli (misal: 24 buah mangga dlm 1 kotak)
            $table->integer('isi_per_satuan')->default(1)->after('satuan');
            
            // berat_per_isi: berat per satu biji/unit dalam gram (misal: 350 gram per mangga)
            $table->decimal('berat_per_isi', 10, 2)->default(0)->after('isi_per_satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Menghapus foreign key dan kolom pada tabel produk
        Schema::table('produk', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropColumn('kategori_id');
        });

        // Menghapus kolom konversi pada tabel bahan
        Schema::table('bahan', function (Blueprint $table) {
            $table->dropColumn(['isi_per_satuan', 'berat_per_isi']);
        });

        // Menghapus tabel kategoris
        Schema::dropIfExists('kategoris');
    }
};