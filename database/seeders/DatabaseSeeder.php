<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Outlet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // === 1 OUTLET DULUAN (WAJIB!) ===
        $outlet = Outlet::create([
            'nama'      => 'Outlet Utama',
            'alamat'    => 'Jl. Contoh No. 123',
            'is_active' => true,
        ]);

        // === USER YANG BOLEH NULL outlet_id ===
        User::create([
            'username'  => 'owner',
            'password'  => bcrypt('owner123'),
            'role'      => 'owner',
            'outlet_id' => null,   // ← tambahkan ini!
        ]);

        User::create([
            'username'  => 'supervisor',
            'password'  => bcrypt('super123'),
            'role'      => 'supervisor',
            'outlet_id' => null,   // ← tambahkan ini!
        ]);

        User::create([
            'username'  => 'gudang',
            'password'  => bcrypt('gudang123'),
            'role'      => 'gudang',
            'outlet_id' => null,   // ← tambahkan ini!
        ]);

        // === KARYAWAN (WAJIB punya outlet_id) ===
        User::create([
            'username'  => 'karyawan1',
            'password'  => bcrypt('karyawan123'),
            'role'      => 'karyawan',
            'outlet_id' => $outlet->id,
        ]);
    }
}