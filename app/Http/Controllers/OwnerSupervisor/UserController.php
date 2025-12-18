<?php

namespace App\Http\Controllers\OwnerSupervisor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return User::with('outlet')->latest()->get();
    }

    public function store(Request $request)
    {
        if (!in_array($request->user()->role, ['owner', 'supervisor'])) {
            return response()->json(['message' => 'Akses ditolak. Hanya owner/supervisor!'], 403);
        }

        $request->validate([
            'username'   => 'required|string|unique:users,username|max:255',
            'password'   => 'required|string|min:6',
            'role'       => 'required|in:karyawan,gudang,supervisor',
            'outlet_id'  => 'required_if:role,karyawan|nullable|exists:outlets,id'
        ]);

        $user = User::create([
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'outlet_id' => $request->outlet_id
        ]);

        return response()->json([
            'message' => 'User berhasil ditambahkan!',
            'data' => $user->load('outlet')
        ], 201);
    }

    public function show(User $user)
    {
        return $user->load('outlet');
    }

    public function update(Request $request, User $user)
    {
        if (!in_array($request->user()->role, ['owner', 'supervisor'])) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'username'   => 'sometimes|required|string|unique:users,username,'.$user->id,
            'password'   => 'sometimes|required|string|min:6',
            'role'       => 'sometimes|required|in:karyawan,gudang,supervisor',
            'outlet_id'  => 'required_if:role,karyawan|nullable|exists:outlets,id'
        ]);

        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        $user->update($request->all());

        return response()->json([
            'message' => 'User berhasil diupdate!',
            'data' => $user->load('outlet')
        ]);
    }

    public function destroy(User $user, Request $request)
    {
        if (!in_array($request->user()->role, ['owner', 'supervisor'])) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun sendiri!'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus!']);
    }
}