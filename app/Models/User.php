<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Field yang boleh diisi via mass assignment
     */
    protected $fillable = [
        'username',
        'password',
        'role',
        'outlet_id'
    ];

    /**
     * Field yang disembunyikan saat return JSON
     */
    protected $hidden = [
        'password'
    ];

    /**
     * Hash password otomatis
     */
    public function setPasswordAttribute($value)
    {
        // Kalau password sudah ter-hash, jangan hash ulang
        if (strlen($value) === 60 && password_get_info($value)['algo']) {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Relasi: User → Outlet
     */
    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Relasi: Karyawan → Transaksi
     */
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'karyawan_id');
    }
}
