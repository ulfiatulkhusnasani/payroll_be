<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Registrasi User baru
     */
    public function register(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'nama_karyawan' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6',
        ]);

        // Buat user baru
        $user = User::create([
            'nama_karyawan' => $validated['nama_karyawan'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Hash password
        ]);

        // Buat token untuk user baru
        // $token = $user->createToken('auth_token')->plainTextToken;
       

        // Kembalikan response sukses beserta token
        return response()->json([
            'message' => 'User berhasil didaftarkan',
            'data' => $user,
            // 'token' => $token // Token ditambahkan ke response
        ], 201);
    }

    /**
     * Login dan menghasilkan token untuk user
     */
    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Cek kredensial login
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login sukses.',
                'token' => $token // Pastikan token tidak null
            ], 200);
        } else {
            return response()->json(['message' => 'Login gagal.'], 401);
        }
    }

    /**
     * Menampilkan informasi user yang sedang login
     */
    public function user(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    /**
     * Logout dan hapus token
     */
    public function logout(Request $request)
    {
        // Menghapus semua token milik user
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ], 200);
    }
}