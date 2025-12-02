<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // HAPUS 'password' DARI SINI!
    protected $fillable = ['username', 'role', 'outlet_id'];

    protected $hidden = ['password'];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'karyawan_id');
    }
}