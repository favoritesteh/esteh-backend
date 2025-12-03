<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    protected $table = 'stok_gudang';
    protected $fillable = ['bahan_id', 'stok'];

    // TAMBAHKAN BARIS INI (double protection)
    protected $attributes = [
        'stok' => 0
    ];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
}