<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $users = User::select('id', 'nama_karyawan as nama_user_data', 'email', 'role', 'created_at', 'updated_at')
                ->orderBy('id', 'desc')
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_user_data' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:admin,user,manager',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()->first()

            ], 422);
        }

        try {
            $user = User::create([
                'nama_karyawan' => $request->nama_user_data,
                'email' => $request->email,
                'role' => $request->role,
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'message' => 'Karyawan berhasil ditambahkan',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menambahkan karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_user_data' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|string|in:admin,user,manager',
            'password' => 'nullable|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()->first()
            ], 422);
        }

        try {
            $updateData = [
                'nama_karyawan' => $request->nama_user_data,
                'email' => $request->email,
                'role' => $request->role
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'message' => 'Karyawan berhasil diperbarui',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'Data karyawan tidak ditemukan'
            ], 404);
        }

        try {
            $user->delete();

            return response()->json([
                'message' => 'Karyawan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}