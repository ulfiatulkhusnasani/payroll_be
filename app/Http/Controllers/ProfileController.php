<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'nama_karyawan' => 'required|string|max:255',
            'current_password' => 'sometimes|required_with:new_password',
            'new_password' => 'nullable|min:7',
        ], [
            'current_password.required_with' => 'Password saat ini wajib diisi jika ingin mengubah password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Update nama
            $user->nama_karyawan = $request->nama_karyawan;

            // Update password jika diisi
            if ($request->filled('current_password') && $request->filled('new_password')) {
                // Verifikasi password saat ini
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Password saat ini salah'
                    ], 422);
                }

                $user->password = Hash::make($request->new_password);
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'data' => [
                    'id' => $user->id,
                    'nama_karyawan' => $user->nama_karyawan,
                    'email' => $user->email,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}