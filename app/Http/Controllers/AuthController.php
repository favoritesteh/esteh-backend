<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        // Hapus session login, kita pakai Sanctum token (stateless & cocok untuk API + Railway)
        // Auth::login($user);                    ← JANGAN DIPAKAI
        // $request->session()->regenerate();     ← JANGAN DIPAKAI

        // Buat token Sanctum
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil!',
            'user'    => [
                'id'         => $user->id,
                'username'   => $user->username,
                'role'       => $user->role,
                'outlet_id'  => $user->outlet_id,
                'outlet'     => $user->outlet ? [
                    'id'   => $user->outlet->id,
                    'nama' => $user->outlet->nama,
                ] : null,
            ],
            'token'   => $token,
        ]);
    }

    /**
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('outlet');

        return response()->json([
            'id'         => $user->id,
            'username'   => $user->username,
            'role'       => $user->role,
            'outlet_id'  => $user->outlet_id,
            'outlet'     => $user->outlet ? [
                'id'   => $user->outlet->id,
                'nama' => $user->outlet->nama,
            ] : null,
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}