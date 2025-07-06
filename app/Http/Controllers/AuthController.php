<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registrasi User baru
     */
    public function register(Request $request)
{
    // Validasi manual agar bisa menangkap error
    $validator = Validator::make($request->all(), [
        'nama_karyawan' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email|max:255',
        'password' => 'required|string|min:6',
    ]);

    // Jika validasi gagal, kembalikan response JSON
    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validasi gagal ' . $validator->errors()->first(),
        ], 400);
    }

    // Ambil data valid
    $validated = $validator->validated();

    // Buat user baru
    $user = User::create([
        'nama_karyawan' => $validated['nama_karyawan'],
        'email' => $validated['email'],
        'role' => 'user',
        'password' => Hash::make($validated['password']),
    ]);

    // Jika berhasil buat user, insert data ke tabel karyawans
    if ($user) {
        DB::table('karyawans')->insert([
            'nip' => $request->nip,
            'nik' => $request->nik,
            'email' => $request->email,
            'no_handphone' => $request->no_handphone,
            'alamat' => $request->alamat,
            'status' => "nonactive"
        ]);
    }

    return response()->json([
        'message' => 'User berhasil didaftarkan',
        'data' => $user,
    ], 201);
}

    /**
     * Login dan menghasilkan token untuk user
     */
    public function login(Request $request)
    {
        try {

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'Email atau user tidak ditemukan'], 400);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['message' => 'Password salah'], 400);
            }

            $karyawan = DB::table('karyawans')->where('email', $user->email)->where('status', 'active')->exists();


            if (!$karyawan && $user->role != 'admin') {
                return response()->json(['message' => 'Email atau user belum aktif sebagai karyawan, silahkan hubungi manager anda!'], 400);
            }

            $accessToken = $user->createToken('auth_token');
            $accessToken->accessToken->update([
                'expires_at' => now()->addHours(7)
            ]);

            $token = $accessToken->plainTextToken;

            return response()->json([
                'message' => 'Login sukses.',
                'user' => [
                    'email' => $user->email,
                    'name' => $user->nama_karyawan,
                    'role' => $user->role,
                    'token' => $token
                ],
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Login gagal'.$th->getMessage()], 500);
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
