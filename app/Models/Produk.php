<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk'; 
    protected $fillable = ['nama', 'kategori_id', 'harga', 'gambar', 'is_available'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function komposisi() 
    { 
        return $this->hasMany(Komposisi::class, 'produk_id'); 
    }

    public function itemTransaksi() 
    { 
        return $this->hasMany(ItemTransaksi::class, 'produk_id'); 
    }
}